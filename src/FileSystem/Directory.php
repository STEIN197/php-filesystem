<?php
	namespace STEIN197\FileSystem;

	class Directory extends Descriptor {

		public function __construct(string $path, int $resolution = Path::PATH_CWD) {
			$this->path = new Path($path, $resolution);
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
				array_filter(
					scandir(
						$this->path->getAbsolute(),
						$order
					),
					fn($val) => !in_array($val, ['.', '..'])
				);
		}

		public function empty(): bool {
			if (!$this->exists())
				throw new NotFoundException($this);
			return sizeof($this->scanDir()) === 0;
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
