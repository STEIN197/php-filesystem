<?php
	namespace STEIN197\FileSystem;

	use \LogicException;

	class SymLink extends Descriptor {

		public function __construct(string $path, int $resolution = Path::PATH_CWD, ?File $file = null) {
			parent::__construct($path, $resolution);
			if ($this->exists() && !is_link($this->path->getAbsolute()))
				throw new LogicException("Cannot instantiate link class: '{$this}' is not link");
		}

		// FIXME: links cannot be created
		public function create(): void {}

		public function delete(): void {
			if (!$this->exists())
				return;
			$result = @unlink($this->path->getAbsolute()) || @rmdir($this->path->getAbsolute());
			if (!$result)
				throw new DescriptorException($this);
		}

		public function copy(Directory $dir, ?string $name = null): Descriptor {
			$newPath = $this->newPath($dir, $name);
			if ($this->path == $newPath)
				return $this;
			$this->checkForMoveOrCopy($dir, $name);
			if (!copy($this->path->getAbsolute(), $newPath->getAbsolute()))
				throw new DescriptorException($this, "Cannot copy '{$this}' link to '{$newPath}'");
			return new static($newPath->getAbsolute());
		}

		public function move(Directory $dir, ?string $name = null): void {
			$newPath = $this->newPath($dir, $name);
			if ($this->path == $newPath)
				return;
			$this->checkForMoveOrCopy($dir, $name);
			if (!rename($this->path->getAbsolute(), $newPath->getAbsolute()))
				throw new DescriptorException($this, "Cannot move '{$this}' link to '{$newPath}'");
			$this->path = $newPath;
		}
		
		public function getSize(): int {
			if (!$this->exists())
				throw new ExistanceException($this);
			$result = filesize($this->path->getAbsolute());
			if ($result === false)
				throw new DescriptorException($this, "Cannot retrieve size for link '{$this}'");
			return $result;
		}

		public function exists(): bool {
			return parent::exists() || is_link($this->path->getAbsolute());
		}

		public function link(Descriptor $target): ?Descriptor {
			$old = $this->read();
			$this->delete();
			if (!symlink($target->path->getAbsolute(), $this->path->getAbsolute()))
				throw new DescriptorException($this);
			// TODO: Return old descriptor, check if link is hard or not before replacing
			return $old;
		}

		public function read(): ?Descriptor {
			$path = readlink($this->path->getAbsolute());
			if (!file_exists($path) || !$path)
				return null;
			if (is_dir($path))
				return new Directory($path);
			elseif (is_link($path))
				return new self($path);
			return new File($path);
		}
	}
