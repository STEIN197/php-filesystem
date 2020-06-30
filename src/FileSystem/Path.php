<?php
	namespace STEIN197\FileSystem;

	/**
	 * This class creates a wrapper around any passed path strings.
	 * A wrapper is used by other FileSystem classes to conveniently manage
	 * paths in filesystem. All passed paths are converted to absolute form.
	 * Absolute paths start with forward slash (any OS) or drive and slash (any),
	 * separated by a colon (Windows). The following paths are absolute:
	 * ```
	 * /home
	 * C:/Windows
	 * D:\Data
	 * ```
	 * If the passed path is absolute then the wrapper just normalizes it:
	 * removes parent jumps, dot-paths, unnecessary multiple slashes,
	 * replaces all slashes with OS-specific.
	 * If path is relative, then it converts to an absolute by these two strategies:
	 * {@see Path::PATH_CWD} and {@see Path::PATH_DOCUMENT_ROOT}. If the first one is used,
	 * then the path is resolved relative to the current working director.
	 * For instance - if passed path is
	 * ```php
	 * 'vendor/autoload.php';
	 * ```
	 * and `getcwd()` returns `'/home'` then path `'/home/vendor/autoload.php'` is the result.
	 * If the second strategy is used then the path is resolved relative to `$_SERVER['DOCUMENT_ROOT']`.
	 * Wrapper cannot be created if this strategy is used and `$_SERVER['DOCUMENT_ROOT']` is absent
	 * (code is executed from CLI) - then an exception is thrown (see in the constructor doc).
	 * There is only one exception for second type of paths - they can start with forward slash like:
	 * ```
	 * /images
	 * /css/styles.css
	 * ```
	 * Paths use OS-dependent directory separators. On Windows it is `\`
	 * and otherwise on the other platforms. However, {@see Path::getDocRootPath()} returns a path
	 * with forward slashes. Paths never end in slashes except for Windows root drives.
	 */
	final class Path {
		
		/** @var int Relative paths are resolved relative to getcwd() function. */
		public const PATH_CWD = 0;
		/** @var int Relative paths (and paths that start with /) are resolved relative to $_SERVER['DOCUMENT_ROOT']. */
		public const PATH_DOCUMENT_ROOT = 1;

		/** @var string Temporary holds raw path value passed to the constructor. */
		private string $path;
		/** @var string Represents absolute path to local resource. */
		private string $absolutePath;
		/** @var int Which resolution strategy to use. */
		private int $resolution;

		/**
		 * Creates path wrapper around passed path string.
		 * @param $path A path around which wrapper is created.
		 *              Empty strings are equal to dot (current directory).
		 * @param $resolution Which resolution strategy to use.
		 *                    Available are PATH_* constants that represented in this class.
		 * @throws \InvalidArgumentException If $path contains illegal characters.
		 * @throws \UnexpectedValueException If PATH_DOCUMENT_ROOT resolution is used and $_SERVER['DOCUMENT_ROOT'] is absent
		 */
		public function __construct(string $path = '.', int $resolution = self::PATH_CWD) {
			if ($path === '')
				$path = '.';
			elseif (!ctype_print($path))
				throw new \InvalidArgumentException('Path cannot contain non-printable characters or be empty', 0);
			if ($resolution === self::PATH_DOCUMENT_ROOT && !self::hasDocumentRoot())
				throw new \UnexpectedValueException('Can\'t create path with DOCUMENT_ROOT resolution. DOCUMENT_ROOT is not set');
			if ($path === '')
				$this->path = '.';
			else
				$this->path = $path;
			$this->resolution = $resolution;
			$this->makeAbsolute();
			$this->normalize();
			$this->path = null;
		}

		/**
		 * Return normalized absolute path to resource relative
		 * to system root directory/drive. In normalized path
		 * there are no dot transitions like '..' or '.'. Also -
		 * all paths start with forward slash (Unix) or drive and
		 * backslash (Windows). The returned string does not
		 * contain last trailing slash.
		 */
		public function getAbsolute(): string {
			return $this->absolutePath;
		}

		/**
		 * Returns path relative to current working directory.
		 * @return string Path relative to current working directory.
		 */
		public function getRelative(): ?string {
			$cwdStr = (string) Directory::getCwd();
			if (strpos($this->absolutePath, $cwdStr) !== 0)
				return null;
			$path = substr($this->absolutePath, strlen($cwdStr));
			$path = ltrim($path, \DIRECTORY_SEPARATOR);
			return $path;
		} // TODO: Make tests
		
		/**
		 * Return path relative to document root.
		 * In this method dorward slash is always used.
		 * If document root is absent or path
		 * does not contain document root then null is returned.
		 */
		public function getDocRootPath(): ?string {
			if (!self::hasDocumentRoot())
				return null;
			$docRoot = (new self($_SERVER['DOCUMENT_ROOT']))->getAbsolute();
			if (strpos($this->absolutePath, $docRoot) === 0) {
				$path = substr($this->absolutePath, strlen($docRoot));
				$path = \DIRECTORY_SEPARATOR.ltrim($path, \DIRECTORY_SEPARATOR);
				if (\DIRECTORY_SEPARATOR !== '/')
					$path = str_replace(\DIRECTORY_SEPARATOR, '/', $path);
				return $path;
			} else {
				return null;
			}
		}

		public function isRoot(): bool {
			return $this->absolutePath === dirname($this->absolutePath);
		}

		/**
		 * Same as {@see Path::getAbsolute()}
		 */
		public function __toString() {
			return $this->absolutePath;
		}

		private static function hasDocumentRoot(): bool {
			return isset($_SERVER['DOCUMENT_ROOT']) && strlen($_SERVER['DOCUMENT_ROOT']) > 0;
		}

		/**
		 * Check if $path is absolute (starts with '/' or 'C:\' etc.).
		 * @param $path Path to check.
		 * @return bool True if path is absolute.
		 */
		private function isAbsolute(): bool {
			return \preg_match('/^(?:\\\\|\/|[a-z]+:[\\\\\/]?)/i', $this->path);
		}

		/**
		 * Create absolute path to resource.
		 * See all resolution rules above.
		 */
		private function makeAbsolute(): void {
			if ($this->resolution === self::PATH_DOCUMENT_ROOT) {
				$this->absolutePath = $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.$this->path;
			} else {
				if ($this->isAbsolute()) {
					$this->absolutePath = $this->path;
				} else {
					$this->absolutePath = Directory::getCwd().DIRECTORY_SEPARATOR.$this->path;
				}
			}
		}

		/**
		 * Removes any references like '.' and '..'
		 * and replaces all slashes with OS-dependent ones.
		 * @throws \DomainException If path contains too many parent jumps
		 *                          like '../../../' and root directory is already reached.
		 */
		private function normalize(): void {
			$this->absolutePath = preg_replace('/[\/\\\\]+/', \DIRECTORY_SEPARATOR, $this->absolutePath);
			$parts = explode(\DIRECTORY_SEPARATOR, rtrim($this->absolutePath, \DIRECTORY_SEPARATOR));
			$drive = \array_shift($parts);
			$result = [];
			foreach ($parts as $part) {
				if ($part === '.')
					continue;
				if ($part === '..') {
					$pop = array_pop($result);
					if (!$pop)
						throw new \DomainException("Path '{$this->absolutePath}' has too many parent jumps", 0);
				} else {
					$result[] = $part;
				}
			}
			if (!$drive && \DIRECTORY_SEPARATOR === '\\')
				$drive = \str_replace(\DIRECTORY_SEPARATOR, '', realpath('\\'));
			\array_unshift($result, $drive);
			if (DIRECTORY_SEPARATOR === '\\' && sizeof($result) === 1)
				$result[] = '';
			$this->absolutePath = join(\DIRECTORY_SEPARATOR, $result);
		}
	}
	// TODO: PATH_DECLARATION = 2 - resolve relative to declaration file.
