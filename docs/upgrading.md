# Upgrading nette/robot-loader

## 4.1.3

- **Renamed** `Nette\Loaders\RobotLoader::setTempDirectory()` → `Nette\Loaders\RobotLoader::setCacheDirectory()`; the old name is silently deprecated (b86bf5ae)

## 4.0.2

- **Renamed** `$on` → `$state` of `Nette\Loaders\RobotLoader::reportParseErrors()`; affects calls using named arguments; the old name was removed (43b8f76f)
- **Renamed** `$on` → `$state` of `Nette\Loaders\RobotLoader::setAutoRefresh()`; affects calls using named arguments; the old name was removed (43b8f76f)
- **Changed** `Nette\Loaders\RobotLoader::setTempDirectory()` now throws `Nette\InvalidArgumentException` when `$dir` is not an absolute path; previously a relative path was accepted (9b464fde)

## 4.0.1

- **Changed** `Nette\Loaders\RobotLoader` no longer uses `Nette\SmartObject`; reading an undefined property or calling an undefined method no longer throws `Nette\MemberAccessException` with a hint and extension methods no longer work (7d88ff36)

## 4.0.0

- **Removed** support for the `netterobots.txt` file in scanned directories: exclude paths with `Nette\Loaders\RobotLoader::excludeDirectory()` instead (c36d4ee1)
- **Renamed** `Nette\Loaders\RobotLoader::getCacheKey()` → `Nette\Loaders\RobotLoader::generateCacheKey()`; affects subclasses overriding it; the old name was removed (b190f111)
- **Signature** `Nette\Loaders\RobotLoader::$acceptFiles` is now typed `array`; previously untyped, and a comma- or space-separated string was split into masks while triggering `E_USER_WARNING` (since 3.2.0); assigning a string now throws `TypeError` (00ba7742, 87498fb9)
- **Signature** `Nette\Loaders\RobotLoader::$ignoreDirs` is now typed `array`; previously untyped, and a comma- or space-separated string was split into masks while triggering `E_USER_WARNING` (since 3.2.0); assigning a string now throws `TypeError` (00ba7742, 87498fb9)
- **Signature** `Nette\Loaders\RobotLoader::addDirectory()` now takes `string ...$paths`; previously an array of paths passed as the single argument still worked while triggering `E_USER_WARNING` (since 3.2.0), now it throws `TypeError`; unpack the array with `...` (ce89b6a5, 87498fb9)
- **Signature** `Nette\Loaders\RobotLoader::excludeDirectory()` now takes `string ...$paths`; previously an array of paths passed as the single argument still worked while triggering `E_USER_WARNING` (since 3.2.0), now it throws `TypeError`; unpack the array with `...` (ce89b6a5, 87498fb9)
- **Signature** the return type of `Nette\Loaders\RobotLoader::register()`, `Nette\Loaders\RobotLoader::reportParseErrors()`, `Nette\Loaders\RobotLoader::setAutoRefresh()`, `Nette\Loaders\RobotLoader::setTempDirectory()`, `Nette\Loaders\RobotLoader::addDirectory()` and `Nette\Loaders\RobotLoader::excludeDirectory()` is now `static`; previously `self`; affects subclasses overriding them (ce89b6a5)

## 3.4.0

- **Changed** `Nette\Loaders\RobotLoader::getIndexedClasses()` now returns file paths resolved with `realpath()`, with symlinks and relative segments expanded; previously the paths kept the form of the directory passed to `Nette\Loaders\RobotLoader::addDirectory()`; the same resolved paths appear in exception messages (266c0c09)

## 3.2.1

- **Removed** support for the `//netteloader=Class1,Class2` comment in a scanned PHP file: the class list is always taken from parsing the file (21c3c6ac)
- **Changed** a file included by `Nette\Loaders\RobotLoader::tryLoad()` is required from a static closure, so `$this` is unbound inside it; previously top-level `$this` in an autoloaded file referred to the `Nette\Loaders\RobotLoader` instance (d2a100e1)

## 3.2.0

- **Removed** `Nette\Loaders\RobotLoader::RETRY_LIMIT`: the constant is private now, there is no replacement (1db3c10e)
- **Signature** `Nette\Loaders\RobotLoader::addDirectory()` now takes variadic `...$paths`; previously one `$path` that was a string or an array; an array passed as the single argument still works but triggers `E_USER_WARNING` (0712a0e3)
- **Signature** `Nette\Loaders\RobotLoader::excludeDirectory()` now takes variadic `...$paths`; previously one `$path` that was a string or an array; an array passed as the single argument still works but triggers `E_USER_WARNING` (0712a0e3)
- **Signature** native parameter and return types were added across the class; affects subclasses overriding `Nette\Loaders\RobotLoader::tryLoad()`, `Nette\Loaders\RobotLoader::refresh()`, `Nette\Loaders\RobotLoader::rebuild()`, `Nette\Loaders\RobotLoader::getIndexedClasses()` or `Nette\Loaders\RobotLoader::getCacheKey()`, and callers passing a non-`bool` to `Nette\Loaders\RobotLoader::register()`, `Nette\Loaders\RobotLoader::reportParseErrors()` or `Nette\Loaders\RobotLoader::setAutoRefresh()` and a non-`string` to `Nette\Loaders\RobotLoader::tryLoad()` or `Nette\Loaders\RobotLoader::setTempDirectory()`, which were coerced before (458ca5ce)
- **Changed** `Nette\Loaders\RobotLoader::$acceptFiles` now triggers `E_USER_WARNING` when it holds a comma- or space-separated string; previously such a string was split into masks silently; assign an array of masks (44ed9592)
- **Changed** `Nette\Loaders\RobotLoader::$ignoreDirs` now triggers `E_USER_WARNING` when it holds a comma- or space-separated string; previously such a string was split into masks silently; assign an array of masks (44ed9592)
