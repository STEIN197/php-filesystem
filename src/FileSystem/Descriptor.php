<?php
	namespace STEIN197\FileSystem;

	use \InvalidArgumentException;

	/**
	 * This class contains common tasks that could be applied to
	 * a file, directory or symbolic link.
	 */
	abstract class Descriptor {

		/** @var int Returns modification time when passed to {@see Descriptor::getTimestamp(int)}. Check out `filemtime()` */
		public const TIME_MODIFICATION = 0;
		/** @var int Returns access time when passed to {@see Descriptor::getTimestamp(int)}. Check out `fileatime()` */
		public const TIME_ACCESS = 1;
		/** @var int Returns change time when passed to {@see Descriptor::getTimestamp(int)}. Check out `filectime()` */
		public const TIME_CHANGE = 2;
		
		/** @var Path Absolute path to this object. */
		protected Path $path;

		public function __construct(string $path, int $resolution = Path::PATH_CWD) {
			$this->path = new Path($path, $resolution);
		}

		/**
		 * Creates a file/directory/link.
		 * If the path points to existing file/directory,
		 * then does nothing.
		 */
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
			return new Directory(dirname($this->path->getAbsolute()));
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
		 * Return specified timestamp this descriptor.
		 * @param int $type Type of timestamp to return.
		 *                  One of self::TIME_* constants
		 * @return int Last modified time.
		 * @throws InvalidArgumentException If the passed argument is not one of the self::TIME_* constants.
		 * @throws NotFoundException If descriptor points to nonexistent source.
		 * @throws DescriptorException In other cases.
		 */
		public function getTimestamp(int $type): int {
			if (!$this->exists())
				throw new NotFoundException($this);
			$result = false;
			$absPath = $this->path->getAbsolute();
			switch ($type) {
				case self::TIME_ACCESS:
					$result = fileatime($absPath);
					break;
				case self::TIME_MODIFICATION:
					$result = filemtime($absPath);
					break;
				case self::TIME_CHANGE:
					$result = filectime($absPath);
					break;
				default:
					throw new InvalidArgumentException("Invalid type parameter value: {$type}");
			}
			if ($result === false)
				throw new DescriptorException($this, 'Can\'t get file timestamp ');
			return $result;
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

		protected function checkForMoveOrCopy(Directory $dir, ?string $name): void {
			if (!$dir->exists())
				throw new NotFoundException($dir);
			if (!$this->exists())
				throw new NotFoundException($this);
			if ($name && !self::nameIsValid($name))
				throw new InvalidArgumentException('Name cannot contain slashes or non-printable characters');
			$newPath = $this->newPath($dir, $name);
			if (file_exists($newPath->getAbsolute())) {
				$msg = sprintf("Cannot move/copy '{$this}' to '{$newPath}'. %1\$s with this name already exists", $this instanceof Directory ? 'Directory' : 'File');
				throw new ExistanceException($this, $msg);
			}
		}

		protected function newPath(Directory $dir, ?string $name): Path {
			return new Path($dir.DIRECTORY_SEPARATOR.($name ?? $this->getName()));
		}

		protected static function nameIsValid(string $name): bool {
			return !preg_match('/[\/\\\\]/', $name) && ctype_print($name);
		}
	}

	// TODO: Chmod and other functions
	// TODO: setTimestamp(int) ?
