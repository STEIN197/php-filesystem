<?php
	namespace STEIN197\FileSystem;

	use \LogicException;
	use \Exception;

	class File extends Descriptor {

		public function __construct(string $path, int $resolution = Path::PATH_CWD) {
			$this->path = new Path($path, $resolution);
			if ($this->exists() && !is_file($this->path->getAbsolute()))
				throw new LogicException("Cannot instantiate file class. The path '{$this}' points to non-file entity.");
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

		public function copy(Directory $dir, ?string $name = null): File {
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
				throw new ExistanceException($this, "Cannot copy '{$this}' to '{$newPath}'. File with this name already exists");
			if (!copy($this->path->getAbsolute(), $newPath->getAbsolute()))
				throw new DescriptorException($this, "Cannot copy '{$this}' file");
			return new static($newPath->getAbsolute());
		}

		public function move(Directory $dir, ?string $name = null): void {
			if (!$dir->exists())
				throw new NotFoundException($dir);
			if (!$this->exists())
				throw new NotFoundException($this);
			if ($name && !Descriptor::nameIsValid($name))
				throw new InvalidArgumentException('Name cannot contain slashes and non-printable characters');
			$newPath = new Path($dir.DIRECTORY_SEPARATOR.($name ?? $this->getName()));
			if ($this->path->getAbsolute() === $newPath->getAbsolute())
				return;
			if (file_exists($newPath->getAbsolute()))
				throw new ExistanceException($this, "Cannot move '{$this}' to '{$benewPath}'. File with this name already exists");
			if (!rename($this->path->getAbsolute(), $newPath->getAbsolute()))
				throw new DescriptorException($this, "Cannot rename '{$this}'");
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
	}
	// TODO: Mb create ExtensionException?
	// TODO: Code duplication in move and copy methods
	// TODO: Don't forget to change path variable after renaming operations
