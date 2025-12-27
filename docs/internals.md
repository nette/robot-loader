# RobotLoader internals

One class (`Nette\Loaders\RobotLoader`), one coherent mechanism (scan → index →
cache → autoload), so one file. Most of it is a straightforward token scan; the
expensive-to-reconstruct parts are the **request-scoped refresh guards**, the
**three-part persisted state**, and above all the **cross-platform cache
atomicity**, which is where an edit will silently break production.

## Persisted state is three maps, saved and reset together

The cache file is a **PHP file returning an array** of three maps (written with
`var_export`, so opcache serves it — deliberately not `serialize`):

- `$classes`: `class => [file, mtime]` — the index.
- `$missingClasses`: `class => retryCounter` — classes that failed to resolve.
- `$emptyFiles`: `file => mtime` — files that contain no class-likes.

The latter two are non-obvious optimizations, not incidental: `$emptyFiles` lets
a rescan skip files known to define nothing, and `$missingClasses` **caps rescans
at `RetryLimit` (3)** so a class that never resolves does not trigger a full
directory scan on every autoload. All three are loaded, saved, and cleared as a
unit; a change to the tuple shape must bump the cache-key version (see below).

## Autoload flow and the once-per-request guards

`tryLoad()` is the `spl_autoload_register` callback. Its dev-mode behavior is a
small state machine with **two guards that are the key invariants**:

- **`$refreshed` allows at most one full/partial rescan per request.** Set at the
  top of `refreshClasses()`, it prevents `tryLoad` from re-scanning again later in
  the same request. Without it, a batch of missing classes would each trigger a
  scan.
- **`$missingClasses[$type] >= RetryLimit` short-circuits.** Once a class has been
  looked for 3 times without success, `tryLoad` returns immediately — the guard
  against infinite rescans for a genuinely absent class.

On a miss with `autoRebuild` on: if the file is unknown or gone → full
`refreshClasses()`; if the file's mtime changed → `updateFile()` (single file);
still missing → bump the retry counter. Mutations set `$needSave`, and the cache
is flushed **once in `__destruct`** (not per class).

## Incremental scan reuses mtimes; ambiguity is checked live

`refreshClasses()` rebuilds a `file => mtime` / `file => classes` view from the
existing cache, then for each scanned file **skips `scanPhp` when the mtime is
unchanged**, reusing the cached class list. The same class found in two files
throws `InvalidStateException`.

`updateFile()` carries a subtle correctness step: before declaring an ambiguity,
if the class's previously recorded file has a **changed mtime**, it re-scans that
file first (the class may have *moved out* of it). This avoids a false
"ambiguous class" error when a class is relocated between files.

## `scanPhp`: only top-level declarations, brace-level tracked

The token scan (`\PhpToken::tokenize(..., TOKEN_PARSE)`) records a class/interface/
trait/enum name **only when the current brace `$level` equals `$minLevel`**.
`$minLevel` is 0 for a file-level namespace and **1 for a braced `namespace { }`**,
so declarations are captured at the namespace's base level and **anything nested
inside a function or conditional is ignored**. A `ParseError` is rethrown with the
offending file path patched in (via reflection on the exception's `file`), unless
`reportParseErrors` is off.

## Cache atomicity: the platform split (highest-value, most "why")

This subsystem exists to make concurrent cache reads/writes safe on both Linux and
Windows, and the two paths are genuinely different. Getting it wrong corrupts the
cache or deadlocks production, so the reasoning is load-bearing:

- **Linux read path takes no lock.** `loadCache()` `@include`s the cache file
  **directly, without a shared lock** — to minimize IO and because the directory
  may not be writable at all. Correctness there rests entirely on the writer
  creating the file by **atomic `rename`**.
- **Windows read path must lock.** A file **cannot be renamed-to while open**, and
  `include` holds it open, so on Windows `loadCache` first acquires a **shared
  lock** (`LOCK_SH`) before reading.
- **Cache-stampede prevention is double-checked locking.** On a miss, the shared
  lock is released, an **exclusive lock** (`LOCK_EX`) acquired, and the cache
  **re-read** — because another thread may have built it while we waited. Only if
  it is still absent does this thread scan and `saveCache()` under the held lock.
  So under a cold cache, exactly one thread builds and the rest reuse.
- **`atomicWrite` = tmp + rename, with a Windows retry loop and double opcache
  invalidation.** It writes a `.tmp`, invalidates opcache **before and after**,
  and `rename`s. On Windows a rename over a momentarily-locked target fails
  intermittently, so the rename gets **up to 3 attempts, 100 ms apart**; on Linux
  a single failure is fatal.
- **Lock files are never deleted** (on any platform) — on Windows concurrent
  create/delete of the same lock file yields "permission denied", so the `.lock`
  file is left in place on purpose.

Platform detection is `Nette\Utils\Helpers::IsWindows`.

If you touch any of this, preserve the asymmetry: **no read lock on Linux, shared
lock on Windows, exclusive lock + double-check to write, atomic rename to publish.**

## Cache key = scan configuration + format version

The cache file name is `hash('xxh128', serialize(generateCacheKey()))`, where the
key is `[ignoreDirs, acceptFiles, scanPaths, excludeDirs, 'v2']`. Changing scan
configuration therefore addresses a **different** cache file (no stale reuse), and
the trailing `'v2'` is the format-version tag to bump when the persisted tuple
changes. `generateCacheKey()` is `protected` — the extension point for subclasses.

## Navigation map

| Concern | Where |
|---|---|
| Autoload callback, retry/refresh guards | `tryLoad`, `RetryLimit`, `$refreshed` |
| Incremental scan, ambiguity, move detection | `refreshClasses`, `updateFile` |
| Token scan, brace-level rule | `scanPhp` |
| Read path, stampede lock dance | `loadCache` |
| Atomic publish, Windows retry | `saveCache`, `atomicWrite`, `acquireLock` |
| Cache identity / versioning | `generateCacheFileName`, `generateCacheKey` |
