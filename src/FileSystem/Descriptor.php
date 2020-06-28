<?php
	namespace STEIN197\FileSystem;

	use \InvalidArgumentException;

	/**
	 * This class contains common tasks that could be applied to
	 * a file, directory or symbolic link.
	 */
	abstract class Descriptor {

		/** @var int Used by {@see Descriptor::getTime(int)} function. */
		private const TIME_MODIFICATION = 0;
		/** @var int Used by {@see Descriptor::getTime(int)} function. */
		private const TIME_ACCESS = 1;
		
		/** @var Path Absolute path to this object. */
		protected Path $path;

		abstract public function create(): void;
		abstract public function delete(): void;
		abstract public function copy(Directory $dir, ?string $name = null): Descriptor;
		abstract public function move(Directory $dir, ?string $name = null): void;
		abstract public function getSize(): int;

		/**
		 * Return parent directory of this resource.
		 * If the resource is root then `null` is returned.
		 * @return Directory Parent directory or `null` if resource
		 *                   is root (like '/' or 'C:\').
		 */
		public function getDirectory(): ?Directory {
			if ($this->path->isRoot())
				return null;
			return new Directory($dirname);
		}

		/**
		 * Rename directory/file with specified name.
		 * @param string $name New name.
		 * @return string Old name or null if the renaming didn't happen.
		 * @throws InvalidArgumentException If new name is empty or contains
		 *                                  invalid characters/slashes.
		 * @throws DescriptorException If there was an attempt to rename the root directory.
		 * @throws ExistanceException If file with the specified name already exists.
		 */
		public function rename(string $name): ?string {
			if ($this->getName() === $name)
				return null;
			if (!$name)
				throw new InvalidArgumentException('Name cannot be empty', 0);
			if (!self::nameIsValid($name))
				throw new InvalidArgumentException('Name cannot contain slashes and non-printable characters', 1);
			$parent = $this->getDirectory();
			if (!$parent)
				throw new DescriptorException($this, 'Cannot rename root directory', 2);
			$newPath = new Path($parent.DIRECTORY_SEPARATOR.trim($name));
			if (file_exists($newPath->getAbsolute()))
				throw new ExistanceException($this, "Cannot rename '{$this}' to '{$newPath}'. File with this name already exists", 3);
			$oldName = $this->getName();
			if (file_exists($this->path->getAbsolute()))
				if (!rename($this->path->getAbsolute(), $newPath->getAbsolute()))
					throw new DescriptorException($this, "Cannot rename '{$this}'");
			$this->path = $newPath;
			return $oldName;
		}

		/**
		 * Check if resource at given path exists. In case of
		 * symbolic links it checks if the link exists and
		 * not a resource it points to.
		 * @return bool True if resource exists.
		 */
		public function exists(): bool {
			return file_exists($this->path->getAbsolute());
		}

		/**
		 * Return last modified time for this descriptor.
		 * @return int Last modified time.
		 * @throws NotFoundException If descriptor points to nonexistent source.
		 * @throws DescriptorException In other cases.
		 */
		public function getModifiedTime(): int {
			return $this->getTime(self::TIME_MODIFICATION);
		}

		/**
		 * Return last access time for this descriptor.
		 * @return int Last access time.
		 * @throws NotFoundException If descriptor points to nonexistent source.
		 * @throws DescriptorException In other cases.
		 */
		public function getAccessTime(): int {
			return $this->getTime(self::TIME_ACCESS);
		}

		/**
		 * Return name of descriptor, i.e. last part of path.
		 * @return string Name of descriptor.
		 */
		public function getName(): string {
			return basename($this->path->getAbsolute());
		}

		public function getPath(): Path {
			return $this->path;
		}

		public function __toString() {
			return (string) $this->path;
		}

		/**
		 * @see Descriptor::getModifiedTime()
		 */
		protected final function getTime(int $type): int {
			if (!$this->exists())
				throw new NotFoundException($this);
			$result = false;
			$absPath = $this->path->getAbsolute();
			switch ($type) {
				case self::TIME_ACCESS:
					$result = \fileatime($absPath);
					break;
				case self::TIME_MODIFICATION:
					$result = \filemtime($absPath);
					break;
			}
			if ($result === false)
				throw new DescriptorException($this, 'Can\'t get file time');
			return $result;
		}

		protected static function nameIsValid(string $name): bool {
			return !preg_match('/[\/\\\\]/', $name) && ctype_print($name);
		}
	}

	// TODO: Chmod and other functions