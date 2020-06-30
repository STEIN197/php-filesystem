<?php
	namespace STEIN197\FileSystem;

	use \LogicException;
	use \Exception;

	class File extends Descriptor {

		protected $resource;

		public function __construct(string $path, int $resolution = Path::PATH_CWD) {
			$this->path = new Path($path, $resolution);
			if ($this->exists() && !is_file($this->path->getAbsolute()))
				throw new LogicException("Cannot instantiate file class: '{$this}' is not file");
		}

		public function create(): void {
			$this->getDirectory()->create();
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
			$result = filesize($this->path->getAbsolute());
			if ($result === false)
				throw new DescriptorException($this, "Cannot retrieve size for file '{$this}'");
			return $result;
		}

		public function copy(Directory $dir, ?string $name = null): File {
			$newPath = $this->getNewPath($dir, $name);
			if ($this->path == $newPath)
				return $this;
			$this->checkForMoveOrCopy($dir, $newPath, $name);
			if (!copy($this->path->getAbsolute(), $newPath->getAbsolute()))
				throw new DescriptorException($this, "Cannot copy '{$this}' file to '{$newPath}'");
			return new static($newPath->getAbsolute());
		}

		public function move(Directory $dir, ?string $name = null): void {
			$newPath = $this->getNewPath($dir, $name);
			if ($this->path == $newPath)
				return;
			$this->checkForMoveOrCopy($dir, $newPath, $name);
			if (!rename($this->path->getAbsolute(), $newPath->getAbsolute()))
				throw new DescriptorException($this, "Cannot move '{$this}' file to '{$newPath}'");
			$this->path = $newPath;
		}

		// TODO: Mb use finfo_file()?
		public function getMimeType(): string {
			if (!extension_loaded('fileinfo'))
				throw new Exception("Extension 'fileinfo' is not loaded");
			$result = mime_content_type($this->path->getAbsolute());
			if ($result === false)
				throw new DescriptorException($this, 'Cannot retrieve MIME type for file');
			return $result;
		}

		public function truncate(): void {} // TODO
		public function open(): void {} // TODO
		public function close(): void {} // TODO

		private function checkForMoveOrCopy(Directory $dir, Path $newPath, ?string $name = null): void {
			if (!$dir->exists())
				throw new NotFoundException($dir);
			if (!$this->exists())
				throw new NotFoundException($this);
			if ($name && !Descriptor::nameIsValid($name))
				throw new InvalidArgumentException('Name cannot contain slashes and non-printable characters');
			if (file_exists($newPath->getAbsolute()))
				throw new ExistanceException($this, "Cannot move/copy '{$this}' to '{$newPath}'. File with this name already exists");
		}

		private function getNewPath(Directory $dir, ?string $name = null): Path {
			return new Path($dir.DIRECTORY_SEPARATOR.($name ?? $this->getName()));
		}
	}
	// TODO: Mb create ExtensionException?
	// TODO: Code duplication in move and copy methods
	// TODO: Don't forget to change path variable after renaming operations
