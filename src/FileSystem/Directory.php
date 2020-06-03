<?php
	namespace STEIN197\FileSystem;

	class Directory extends Descriptor {

		public function __construct(string $path, int $resolution = Path::PATH_CWD) {
			$this->path = new Path($path, $resolution);
		}

		public function create(int $mode = 0777): void {} // TODO
		public function delete(): void {} // TODO
		public function move(Directory $dir, ?string $name = null): void {} // TODO
		public function copy(Directory $dir, ?string $name = null): Descriptor {} // TODO
		public function getDirectory(): Directory {} // TODO
		public function rename(string $name): void {} // TODO
		public function getSize(): int {} // TODO
		public function scanDir(): array {} // TODO
		
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
