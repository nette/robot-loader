<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Loaders;

use Nette;
use SplFileInfo;


/**
 * Nette auto loader is responsible for loading classes and interfaces.
 *
 * <code>
 * $loader = new Nette\Loaders\RobotLoader;
 * $loader->addDirectory('app');
 * $loader->excludeDirectory('app/exclude');
 * $loader->setTempDirectory('temp');
 * $loader->register();
 * </code>
 */
class RobotLoader
{
	use Nette\SmartObject;

	private const RETRY_LIMIT = 3;

	/** @var array */
	public $ignoreDirs = ['.*', '*.old', '*.bak', '*.tmp', 'temp'];

	/** @var array */
	public $acceptFiles = ['*.php'];

	/** @var bool */
	private $autoRebuild = true;

	/** @var bool */
	private $reportParseErrors = true;

	/** @var array */
	private $scanPaths = [];

	/** @var array */
	private $excludeDirs = [];

	/** @var array of class => [file, time] */
	private $classes = [];

	/** @var bool */
	private $refreshed = false;

	/** @var array of missing classes */
	private $missing = [];

	/** @var string|null */
	private $tempDirectory;


	public function __construct()
	{
		if (!extension_loaded('tokenizer')) {
			throw new Nette\NotSupportedException('PHP extension Tokenizer is not loaded.');
		}
	}


	/**
	 * Register autoloader.
	 */
	public function register(bool $prepend = false): self
	{
		$this->loadCache();
		spl_autoload_register([$this, 'tryLoad'], true, $prepend);
		return $this;
	}


	/**
	 * Handles autoloading of classes, interfaces or traits.
	 */
	public function tryLoad(string $type): void
	{
		$type = ltrim($type, '\\'); // PHP namespace bug #49143
		$info = $this->classes[$type] ?? null;

		if ($this->autoRebuild) {
			if (!$info || !is_file($info['file'])) {
				$missing = &$this->missing[$type];
				$missing++;
				if (!$this->refreshed && $missing <= self::RETRY_LIMIT) {
					$this->refreshClasses();
					$this->saveCache();
				} elseif ($info) {
					unset($this->classes[$type]);
					$this->saveCache();
				}

			} elseif (!$this->refreshed && filemtime($info['file']) !== $info['time']) {
				$this->updateFile($info['file']);
				if (empty($this->classes[$type])) {
					$this->missing[$type] = 0;
				}
				$this->saveCache();
			}
			$info = $this->classes[$type] ?? null;
		}

		if ($info) {
			(function ($file) { require $file; })($info['file']);
		}
	}


	/**
	 * Add path or paths to list.
	 * @param  string  ...$paths  absolute path
	 */
	public function addDirectory(...$paths): self
	{
		if (is_array($paths[0])) {
			trigger_error(__METHOD__ . '() use variadics ...$paths to add an array of paths.', E_USER_WARNING);
			$paths = $paths[0];
		}
		$this->scanPaths = array_merge($this->scanPaths, $paths);
		return $this;
	}


	public function reportParseErrors(bool $on = true): self
	{
		$this->reportParseErrors = (bool) $on;
		return $this;
	}


	/**
	 * Excludes path or paths from list.
	 * @param  string  ...$paths  absolute path
	 */
	public function excludeDirectory(...$paths): self
	{
		if (is_array($paths[0])) {
			trigger_error(__METHOD__ . '() use variadics ...$paths to add an array of paths.', E_USER_WARNING);
			$paths = $paths[0];
		}
		$this->excludeDirs = array_merge($this->excludeDirs, $paths);
		return $this;
	}


	/**
	 * @return array of class => filename
	 */
	public function getIndexedClasses(): array
	{
		$res = [];
		foreach ($this->classes as $class => $info) {
			$res[$class] = $info['file'];
		}
		return $res;
	}


	/**
	 * Rebuilds class list cache.
	 */
	public function rebuild(): void
	{
		$this->classes = $this->missing = [];
		$this->refreshClasses();
		if ($this->tempDirectory) {
			$this->saveCache();
		}
	}


	/**
	 * Refreshes class list cache.
	 */
	public function refresh(): void
	{
		$this->loadCache();
		if (!$this->refreshed) {
			$this->refreshClasses();
			$this->saveCache();
		}
	}


	/**
	 * Refreshes $classes.
	 */
	private function refreshClasses(): void
	{
		$this->refreshed = true; // prevents calling refreshClasses() or updateFile() in tryLoad()
		$files = [];
		foreach ($this->classes as $class => $info) {
			$files[$info['file']]['time'] = $info['time'];
			$files[$info['file']]['classes'][] = $class;
		}

		$this->classes = [];
		foreach ($this->scanPaths as $path) {
			$iterator = is_file($path) ? [new SplFileInfo($path)] : $this->createFileIterator($path);
			foreach ($iterator as $file) {
				$file = $file->getPathname();
				if (isset($files[$file]) && $files[$file]['time'] == filemtime($file)) {
					$classes = $files[$file]['classes'];
				} else {
					$classes = $this->scanPhp($file);
				}
				$files[$file] = ['classes' => [], 'time' => filemtime($file)];

				foreach ($classes as $class) {
					$info = &$this->classes[$class];
					if (isset($info['file'])) {
						throw new Nette\InvalidStateException("Ambiguous class $class resolution; defined in {$info['file']} and in $file.");
					}
					$info = ['file' => $file, 'time' => filemtime($file)];
					unset($this->missing[$class]);
				}
			}
		}
	}


	/**
	 * Creates an iterator scaning directory for PHP files, subdirectories and 'netterobots.txt' files.
	 * @throws Nette\IOException if path is not found
	 */
	private function createFileIterator(string $dir): Nette\Utils\Finder
	{
		if (!is_dir($dir)) {
			throw new Nette\IOException("File or directory '$dir' not found.");
		}

		if (!is_array($ignoreDirs = $this->ignoreDirs)) {
			trigger_error(__CLASS__ . ': $ignoreDirs must be an array.', E_USER_WARNING);
			$ignoreDirs = preg_split('#[,\s]+#', $ignoreDirs);
		}
		$disallow = [];
		foreach (array_merge($ignoreDirs, $this->excludeDirs) as $item) {
			if ($item = realpath($item)) {
				$disallow[str_replace('\\', '/', $item)] = true;
			}
		}

		if (!is_array($acceptFiles = $this->acceptFiles)) {
			trigger_error(__CLASS__ . ': $acceptFiles must be an array.', E_USER_WARNING);
			$acceptFiles = preg_split('#[,\s]+#', $acceptFiles);
		}

		$iterator = Nette\Utils\Finder::findFiles($acceptFiles)
			->filter(function (SplFileInfo $file) use (&$disallow) {
				return !isset($disallow[str_replace('\\', '/', $file->getRealPath())]);
			})
			->from($dir)
			->exclude($ignoreDirs)
			->filter($filter = function (SplFileInfo $dir) use (&$disallow) {
				$path = str_replace('\\', '/', $dir->getRealPath());
				if (is_file("$path/netterobots.txt")) {
					foreach (file("$path/netterobots.txt") as $s) {
						if (preg_match('#^(?:disallow\\s*:)?\\s*(\\S+)#i', $s, $matches)) {
							$disallow[$path . rtrim('/' . ltrim($matches[1], '/'), '/')] = true;
						}
					}
				}
				return !isset($disallow[$path]);
			});

		$filter(new SplFileInfo($dir));
		return $iterator;
	}


	private function updateFile(string $file): void
	{
		foreach ($this->classes as $class => $info) {
			if (isset($info['file']) && $info['file'] === $file) {
				unset($this->classes[$class]);
			}
		}

		$classes = is_file($file) ? $this->scanPhp($file) : [];
		foreach ($classes as $class) {
			$info = &$this->classes[$class];
			if (isset($info['file']) && @filemtime($info['file']) !== $info['time']) { // @ file may not exists
				$this->updateFile($info['file']);
				$info = &$this->classes[$class];
			}
			if (isset($info['file'])) {
				throw new Nette\InvalidStateException("Ambiguous class $class resolution; defined in {$info['file']} and in $file.");
			}
			$info = ['file' => $file, 'time' => filemtime($file)];
		}
	}


	/**
	 * Searches classes, interfaces and traits in PHP file.
	 * @return string[]
	 */
	private function scanPhp(string $file): array
	{
		$code = file_get_contents($file);
		$expected = false;
		$namespace = '';
		$level = $minLevel = 0;
		$classes = [];

		if (preg_match('#//nette' . 'loader=(\S*)#', $code, $matches)) {
			foreach (explode(',', $matches[1]) as $name) {
				$classes[] = $name;
			}
			return $classes;
		}


		try {
			$tokens = token_get_all($code, TOKEN_PARSE);
		} catch (\ParseError $e) {
			if ($this->reportParseErrors) {
				$rp = new \ReflectionProperty($e, 'file');
				$rp->setAccessible(true);
				$rp->setValue($e, $file);
				throw $e;
			}
			$tokens = [];
		}

		foreach ($tokens as $token) {
			if (is_array($token)) {
				switch ($token[0]) {
					case T_COMMENT:
					case T_DOC_COMMENT:
					case T_WHITESPACE:
						continue 2;

					case T_NS_SEPARATOR:
					case T_STRING:
						if ($expected) {
							$name .= $token[1];
						}
						continue 2;

					case T_NAMESPACE:
					case T_CLASS:
					case T_INTERFACE:
					case T_TRAIT:
						$expected = $token[0];
						$name = '';
						continue 2;
					case T_CURLY_OPEN:
					case T_DOLLAR_OPEN_CURLY_BRACES:
						$level++;
				}
			}

			if ($expected) {
				switch ($expected) {
					case T_CLASS:
					case T_INTERFACE:
					case T_TRAIT:
						if ($name && $level === $minLevel) {
							$classes[] = $namespace . $name;
						}
						break;

					case T_NAMESPACE:
						$namespace = $name ? $name . '\\' : '';
						$minLevel = $token === '{' ? 1 : 0;
				}

				$expected = null;
			}

			if ($token === '{') {
				$level++;
			} elseif ($token === '}') {
				$level--;
			}
		}
		return $classes;
	}


	/********************* caching ****************d*g**/


	/**
	 * Sets auto-refresh mode.
	 */
	public function setAutoRefresh(bool $on = true): self
	{
		$this->autoRebuild = (bool) $on;
		return $this;
	}


	/**
	 * Sets path to temporary directory.
	 */
	public function setTempDirectory(string $dir): self
	{
		Nette\Utils\FileSystem::createDir($dir);
		$this->tempDirectory = $dir;
		return $this;
	}


	/**
	 * Loads class list from cache.
	 */
	private function loadCache(): void
	{
		$file = $this->getCacheFile();
		[$this->classes, $this->missing] = @include $file; // @ file may not exist
		if (is_array($this->classes)) {
			return;
		}

		$handle = fopen("$file.lock", 'c+');
		if (!$handle || !flock($handle, LOCK_EX)) {
			throw new \RuntimeException("Unable to create or acquire exclusive lock on file '$file.lock'.");
		}

		[$this->classes, $this->missing] = @include $file; // @ file may not exist
		if (!is_array($this->classes)) {
			$this->rebuild();
		}

		flock($handle, LOCK_UN);
		fclose($handle);
		@unlink("$file.lock"); // @ file may become locked on Windows
	}


	/**
	 * Writes class list to cache.
	 */
	private function saveCache(): void
	{
		$file = $this->getCacheFile();
		$tempFile = $file . uniqid('', true) . '.tmp';
		$code = "<?php\nreturn " . var_export([$this->classes, $this->missing], true) . ";\n";
		if (file_put_contents($tempFile, $code) !== strlen($code) || !rename($tempFile, $file)) {
			@unlink($tempFile); // @ - file may not exist
			throw new \RuntimeException("Unable to create '$file'.");
		}
		if (function_exists('opcache_invalidate')) {
			@opcache_invalidate($file, true); // @ can be restricted
		}
	}


	private function getCacheFile(): string
	{
		if (!$this->tempDirectory) {
			throw new \LogicException('Set path to temporary directory using setTempDirectory().');
		}
		return $this->tempDirectory . '/' . md5(serialize($this->getCacheKey())) . '.php';
	}


	protected function getCacheKey(): array
	{
		return [$this->ignoreDirs, $this->acceptFiles, $this->scanPaths, $this->excludeDirs];
	}
}
