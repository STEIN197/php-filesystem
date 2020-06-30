<?php
	namespace STEIN197\FileSystem;

	use \LogicException;
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
			$this->path = new Path($path, $resolution);
			if ($this->exists() && !is_dir($this->path->getAbsolute()))
				throw new LogicException("Cannot instantiate directory class: '{$this}' is not file");
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

		// TODO: May be make recursive copy without checking
		// TODO: Checks for move/copy root directories/inside itself/child
		// TODO
		public function copy(Directory $dir, ?string $name = null): Directory {
			// if ($this->path->isRoot()) {}
			if (!$dir->exists())
				throw new NotFoundException($dir);
			if (!$this->exists())
				throw new NotFoundException($this);
			if ($name && !Descriptor::nameIsValid($name))
				throw new InvalidArgumentException('Name cannot contain slashes and non-printable characters');
			$newPath = new Path($dir.DIRECTORY_SEPARATOR.($name ?? $this->getName()));
			if ($this->path->getAbsolute() === $newPath->getAbsolute())
				return $this;
			if (file_exists($newPath->getAbsolute()))
				throw new ExistanceException($this, "Cannot copy '{$this}' to '{$newPath}'. Directory/file with this name already exists");
			(new Directory($newPath->getAbsolute()))->create();
			foreach ($this->scanDir() as $file) {
				$curPath = $this->path->getAbsolute().DIRECTORY_SEPARATOR.$file; // TODO: Move getAbsolute() out of the loop
				$newPathStr = $newPath->getAbsolute().DIRECTORY_SEPARATOR.$file; // TODO: Move getAbsolute() out of the loop
				if (is_dir($curPath)) {
					$d = new Directory($newPathStr);
					$d->create();
					$d1 = new Directory($curPath);
					$d1->copy($d);
					// ...
				} else {
					if (!copy($curPath, $newPathStr))
						throw new DescriptorException($this, "Cannot copy '{$this}' directory");
				}
			}
			return new static($newPath->getAbsolute());
		}

		public function move(Directory $dir, ?string $name = null): void {} // TODO

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
						$item = new Link($path);
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
					return new Link($curPath);
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
	}
