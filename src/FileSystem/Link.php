<?php
	namespace STEIN197\FileSystem;

	class Link extends Descriptor {

		public const LINK_SOFT = 0;
		public const LINK_HARD = 1;

		public function __construct(string $path, int $resolution = Path::PATH_CWD, ?File $file = null) {} // TODO
		public function link(Descriptor $file, int $type = self::LINK_SOFT): ?Descriptor {} // TODO
		public function read(): ?Descriptor {} // TODO
	}
