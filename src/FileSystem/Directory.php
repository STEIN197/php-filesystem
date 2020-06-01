<?php
	namespace STEIN197\FileSystem;

	class Directory extends Descriptor {
		
		public static function chDir(Directory $dir) {}
		public static function getCwd(): Directory {}
	}
