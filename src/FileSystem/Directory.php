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

		public function move(Directory $dir, ?string $name = null): void {} // TODO
		public function copy(Directory $dir, ?string $name = null): Descriptor {} // TODO

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
