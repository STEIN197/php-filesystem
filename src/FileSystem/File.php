<?php
	namespace STEIN197\FileSystem;

	class File extends Descriptor {

		public function __construct(string $path, int $resolution = Path::PATH_CWD) {
			$this->path = new Path($path, $resolution);
		}

		public function create(): void {
			if ($this->exists())
				throw new ExistanceException($this);
			if (!\touch($this->path->getAbsolute()))
				throw new DescriptorException($this, 'Can\'t create file');
		}

		public function delete(): void {
			if (!$this->exists())
				throw new NotFoundException($this);
			if (!\unlink($this->path->getAbsolute()))
				throw new DescriptorException($this, 'Can\'t delete file');
		}

		public function exists(): bool {
			$absPath = $this->path->getAbsolute();
			return \file_exists($absPath) && \is_file($absPath);
		}
	}
