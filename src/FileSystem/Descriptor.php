<?php
	namespace STEIN197\FileSystem;

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
		abstract public function move(Directory $dir, ?string $name = null): void;
		abstract public function copy(Directory $dir, ?string $name = null): Descriptor;
		abstract public function rename(string $name): void;
		abstract public function getSize(): int;
		abstract public function changeMode(int $mode): int;

		/**
		 * Return parent directory of this resource.
		 * If the resource is root then `null` is returned.
		 * @return Directory Parent directory or `null` if resource
		 *                   is root (like '/', '/var' or 'C:\Users', 'C:').
		 */
		public function getDirectory(): ?Directory {
			$parts = explode(\DIRECTORY_SEPARATOR, $this->path->getAbsolute());
			if (sizeof($parts) <= 2)
				return null;
			array_pop($parts);
			return new Directory(join(\DIRECTORY_SEPARATOR, $parts));
		}

		/**
		 * Check if resource at given path exists. In case of
		 * symbolic links it checks if the link exists and
		 * not a resource it points to.
		 * @return bool True if resource exists.
		 */
		public function exists(): bool {
			return \file_exists($this->path->getAbsolute());
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
			$parts = explode(\DIRECTORY_SEPARATOR, $this->path->getAbsolute());
			return array_pop($parts);
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
	}

	// TODO: Chmod and others functions