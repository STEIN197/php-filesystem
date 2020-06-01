<?php
	namespace STEIN197\FileSystem;

	final class Path {
		
		/** @var int Relative paths are resolved relative to getcwd() function. */
		public const PATH_CWD = 0;
		/** @var int Relative paths (and paths that start with /) are resolved relative to $_SERVER['DOCUMENT_ROOT']. */
		public const PATH_DOCUMENT_ROOT = 1;
		public const PATH_DECLARATION = 2; // TODO resolve relative to declaration file.

		/** @var string Passed path value to the constructor. */
		private string $path;
		/** @var string Represents absolute path to local resource. */
		private string $absolutePath;
		/** @var int Which resolution strategy to use. */
		private int $resolution;

		/**
		 * Creates path wrapper around passed path string.
		 * @param $path A path around which wrapper is created.
		 * @param $resolution Which resolution strategy to use.
		 *                    Available are PATH_* constants that represented in this class.
		 * @throws \InvalidArgumentException If $path contains illegal characters.
		 */
		public function __construct(string $path, int $resolution = self::PATH_CWD) {
			if (!\ctype_print($path))
				throw new \InvalidArgumentException('Path cannot have non-printable characters or be empty', 0);
			if ($resolution === self::PATH_DOCUMENT_ROOT && !self::hasDocumentRoot())
				throw new \UnexpectedValueException('Can\'t create path with DOCUMENT_ROOT resolution. DOCUMENT_ROOT is not set');
			$this->path = $path;
			$this->resolution = $resolution;
			$this->makeAbsolute();
			$this->normalize();
		}

		public function getAbsolute(): string {
			return $this->absolutePath;
		}
		// public function getRelative(): string {} // TODO
		// public function getDocRootPath(): ?string {} // TODO
		public function __toString() {
			return $this->absolutePath;
		} // TODO Mb use doc root relative path

		private static function hasDocumentRoot(): bool {
			return isset($_SERVER['DOCUMENT_ROOT']) && strlen($_SERVER['DOCUMENT_ROOT']) > 0;
		}

		/**
		 * Check if $path is absolute (starts with '/' or 'C:\' etc.).
		 * @param $path Path to check.
		 * @return bool True if path is absolute.
		 */
		private function isAbsolute(): bool {
			return \preg_match('/^(?:\/|[a-z]+:[\\\/])/i', $this->path);
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
					$this->absolutePath = getcwd().DIRECTORY_SEPARATOR.$this->path;
				}
			}
		}

		/**
		 * Removes any references like '.' and '..'.
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
			\array_unshift($result, $drive);
			$this->absolutePath = join(\DIRECTORY_SEPARATOR, $result);
		}
	}
