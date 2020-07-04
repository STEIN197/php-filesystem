<?php
	namespace STEIN197\FileSystem;

	use \LogicException;
	use \InvalidArgumentException;
	use \Exception;
	use \Iterator;

	class Directory extends Descriptor implements Iterator {

		private int $iterationPosition = 0;
		private ?array $iterationList = null;

		/**
		 * Creates wrapper around directory.
		 * @param string $path Path to a directory.
		 * @param int $resolution Which resilution strategy to use.
		 * @throws LogicException If the path points to non-directory.
		 * @see Path::PATH_CWD
		 * @see Path::PATH_DOCUMENT_ROOT
		 */
		public function __construct(string $path, int $resolution = Path::PATH_CWD) {
			parent::__construct($path, $resolution);
			if ($this->exists() && !is_dir($this->path->getAbsolute()))
				throw new LogicException("Cannot instantiate directory class: '{$this}' is not directory");
		}

		public function create(): void {
			if (!$this->exists() && !mkdir($this->path->getAbsolute(), 0777, true))
				throw new DescriptorException($this, 'Can\'t create directory');
		}

		public function delete(): void {
			$this->clear();
			if (!rmdir($this->path->getAbsolute()))
				throw new DescriptorException($this, 'Can\'t delete directory');
		}

		public function clear(): void {
			$absPath = $this->path->getAbsolute();
			foreach ($this->scanDir() as $name) {
				$curPath = $absPath.DIRECTORY_SEPARATOR.$name;
				if (is_dir($curPath)) {
					(new Directory($curPath))->delete();
				} else {
					if (!unlink($curPath))
						throw new DescriptorException($this, "Cannot delete directory. Cause: '{$curPath}'");
				}
			}
		}

		public function copy(Directory $dir, ?string $name = null): Directory {
			$this->checkForMoveOrCopy($dir, $name);
			$newPath = $this->newPath($dir, $name);
			foreach ($this->getAllFiles() as $path) {
				$relativePath = (new Path($path))->getRelative($this);
				$newPathStr = $newPath.DIRECTORY_SEPARATOR.$relativePath;
				if (is_dir($path)) {
					if (!mkdir($newPathStr, 0777, true)) // TODO: Save folder and file rights
						throw new DescriptorException($this, "Cannot copy directory '{$this}' to '{$newPath}'");
				} else {
					$tmpDirStr = dirname($newPathStr);
					if (!file_exists($tmpDirStr) && !mkdir($tmpDirStr, 0777, true)) // TODO: Save folder and file rights
						throw new DescriptorException($this, "Cannot copy directory '{$this}' to '{$newPath}'");
					if (!copy($path, $newPathStr))
						throw new DescriptionException($this, "Cannot copy directory '{$this}' to '{$newPath}'");
				}
			}
			return new self($newPath->getAbsolute());
		}

		public function move(Directory $dir, ?string $name = null): void {
			$parent = $this->getDirectory();
			if (!$parent)
				throw new DescriptorException("Root directory movement is not allowed");
			$this->checkForMoveOrCopy($dir, $name);
			$newPath = $this->newPath($dir, $name);
			if (strpos($newPath->getAbsolute(), $this->newPath($parent, $name)->getAbsolute()) === 0)
				throw new InvalidArgumentException('Cannot move directory in itself');
			if (!rename($this->path->getAbsolute(), $newPath->getAbsolute()))
				throw new DescriptorException($this, "Cannot move directory");
			$this->path = $newPath;
		}
	
		public function getAllFiles(bool $includeEmptyDirs = true): array {
			$result = [];
			$absPath = rtrim($this->path->getAbsolute(), DIRECTORY_SEPARATOR);
			foreach ($this->scanDir() as $file) {
				$curPath = $absPath.DIRECTORY_SEPARATOR.$file;
				if (is_dir($curPath)) {
					$tmpDir = new self($curPath);
					if ($includeEmptyDirs && $tmpDir->empty()) {
						$result[] = $curPath;
					}
					$result = array_merge($result, $tmpDir->getAllFiles($includeEmptyDirs));
				} else {
					$result[] = $curPath;
				}
			}
			return $result;
		}

		public function getSize(): int {
			$totalSize = 0;
			$absPath = $this->path->getAbsolute();
			foreach ($this->scanDir() as $name) {
				$curPath = $absPath.DIRECTORY_SEPARATOR.$name;
				if (is_dir($curPath))
					$totalSize += (new Directory($curPath))->getSize();
				else
					$totalSize += filesize($curPath);
			}
			return $totalSize;
		}

		public function scanDir(int $order = \SCANDIR_SORT_ASCENDING): array {
			if (!$this->exists())
				throw new NotFoundException($this);
			return
				array_values(
					array_filter(
						scandir(
							$this->path->getAbsolute(),
							$order
						),
						fn($val) => !in_array($val, ['.', '..'])
					)
				);
		}

		public function empty(): bool {
			if (!$this->exists())
				throw new NotFoundException($this);
			return sizeof($this->scanDir()) === 0;
		}

		public function glob(string $pattern, int $flags = 0): array {
			$paths = glob($this->path->getAbsolute().DIRECTORY_SEPARATOR.$pattern, $flags);
			$result = [];
			$parent = $this->getDirectory();
			foreach ($paths as $path) {
				switch (true) {
					case is_dir($path):
						$item = new self($path);
						break;
					case is_link($path):
						$item = new SymLink($path);
						break;
					case is_file($path):
						$item = new File($path);
						break;
				}
				$isCurDir = $item->path == $this->path;
				$isParentDir = $parent && $parent->path == $item->path;
				if ($isCurDir || $isParentDir)
					continue;
				$result[] = $item;
			}
			return $result;
		}

		public function rewind(): void {
			$this->iterationPosition = 0;
			$this->iterationList = null;
		}

		public function current(): Descriptor {
			$curPath = $this->path->getAbsolute().DIRECTORY_SEPARATOR.$this->iterationList[$this->iterationPosition];
			switch (true) {
				case is_dir($curPath):
					return new self($curPath);
				case is_link($curPath):
					return new SymLink($curPath);
			}
			return new File($curPath);
		}

		public function key(): int {
			return $this->iterationPosition;
		}

		public function next(): void {
			$this->iterationPosition++;
		}

		public function valid(): bool {
			if ($this->iterationList === null)
				$this->iterationList = $this->scanDir();
			$isValid = isset($this->iterationList[$this->iterationPosition]);
			if (!$isValid)
				$this->iterationList = null;
			return $isValid;
		}
		
		public static function chDir(Directory $dir): Directory {
			$old = self::getCwd();
			if (!$dir->exists())
				throw new NotFoundException($dir);
			chdir($dir->path->getAbsolute());
			return $old;
		}

		public static function getCwd(): Directory {
			return new self(getcwd());
		}

		public static function getDocumentRoot(): ?Directory {
			if (isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'])
				return new self($_SERVER['DOCUMENT_ROOT']);
			return null;
		}
	}
