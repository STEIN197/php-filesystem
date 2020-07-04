<?php
	namespace STEIN197\FileSystem;

	use \LogicException;

	class Link extends Descriptor {

		public const LINK_SOFT = 0;
		public const LINK_HARD = 1;

		public function __construct(string $path, int $resolution = Path::PATH_CWD, ?File $file = null) {
			$this->path = new Path($path, $resolution);
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

		public function link(Descriptor $target, int $type = self::LINK_SOFT): ?Descriptor {
			if ($type == self::LINK_HARD && $target instanceof Directory)
				throw new LogicException('Hard links cannot be assotiated with directories');
			if ($type == self::LINK_SOFT)
				$result = symlink($target->path->getAbsolute(), $this->path->getAbsolute());
			else
				$result = link($target->path->getAbsolute(), $this->path->getAbsolute());
			if (!$result)
				throw new DescriptorException($this);
			// TODO: Return old descriptor, check if link is hard or not before replacing
			return null;
		}

		// TODO
		// public function isHard(): bool {}
		// public function isSoft(): bool {}

		public function read(): ?Descriptor {
			$path = readlink($this->path->getAbsolute());
			if (!$path)
				throw new DescriptorException($this, "Cannot read link '{$this}'");
			if (!file_exists($path))
				return null;
			switch (true) {
				case is_dir($path):
					return new Directory($path);
				case is_link($path):
					return new self($path);
			}
			return new File($path);
		}
	}
