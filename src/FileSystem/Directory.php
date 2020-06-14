<?php
	namespace STEIN197\FileSystem;

	class Directory extends Descriptor {

		public function __construct(string $path, int $resolution = Path::PATH_CWD) {
			$this->path = new Path($path, $resolution);
		}

		public function create(): void {
			if ($this->exists())
				throw new ExistanceException($this);
			if (!\mkdir($this->path->getAbsolute(), 0777, true))
				throw new DescriptorException($this, 'Can\'t create directory');
		}

		public function delete(): void {} // TODO
		public function move(Directory $dir, ?string $name = null): void {} // TODO
		public function copy(Directory $dir, ?string $name = null): Descriptor {} // TODO
		public function getDirectory(): Directory {} // TODO
		public function rename(string $name): void {} // TODO
		public function getSize(): int {} // TODO

		public function scanDir(int $order = \SCANDIR_SORT_ASCENDING): array {
			// $fn = ($val) => !\in_array($val, ['.', '..']);
			return
				\array_filter(
					\scandir(
						$this->path->getAbsolute(),
						$order
					),
					fn($val) => !\in_array($val, ['.', '..'])
				);
		}
		public function empty(): bool {} // TODO
		public function clear(): void {} // TODO

		public function exists(): bool {
			$absPath = $this->path->getAbsolute();
			return \file_exists($absPath) && \is_dir($absPath);
		}
		
		public static function changeDirectory(Directory $dir): Directory {
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
