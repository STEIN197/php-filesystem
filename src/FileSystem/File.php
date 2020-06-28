<?php
	namespace STEIN197\FileSystem;

	class File extends Descriptor {

		public function __construct(string $path, int $resolution = Path::PATH_CWD) {
			$this->path = new Path($path, $resolution);
		}

		public function create(): void {
			if (!$this->exists() && !touch($this->path->getAbsolute()))
				throw new DescriptorException($this, 'Can\'t create file');
		}

		public function delete(): void {
			if ($this->exists() && !unlink($this->path->getAbsolute()))
				throw new DescriptorException($this);
		}

		public function getSize(): int {
			if (!$this->exists())
				throw new ExistanceException($this);
			return filesize($this->path->getAbsolute());
		}
	}
	// TODO: Don't forget to change path variable after renaming operations
