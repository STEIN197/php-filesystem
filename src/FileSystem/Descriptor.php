<?php
	namespace STEIN197\FileSystem;

	abstract class Descriptor {
		
		private Directory $directory;

		abstract public function create(): void;
		abstract public function delete(): void;
		abstract public function exists(): bool;
		abstract public function getName(): string;
		abstract public function move(Directory $dir, ?string $name = null): void;
		abstract public function copy(Directory $dir, ?string $name = null): Descriptor;
		abstract public function getDirectory(): Directory;
		abstract public function rename(string $name): void;
		abstract public function getSize(): int;
		abstract public function getModifiedTime(): int;
		abstract public function getAccessTime(): int;
	}
