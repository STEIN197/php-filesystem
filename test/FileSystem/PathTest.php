<?php
	namespace STEIN197\FileSystem;

	use PHPUnit\Framework\TestCase;

	class PathTest extends TestCase {
		
		/**
		 * @dataProvider data_Construct_WhenPathHasManyParentJumps_ThrowsException
		 */
		public function test_Construct_WhenPathHasManyParentJumps_ThrowsException(string $path): void {
			$this->expectException(\DomainException::class);
			new Path($path);
		}
		
		/**
		 * @dataProvider data_Construct_WhenPathHasIllegalCharacters_ThrowsException
		 */
		public function test_Construct_WhenPathHasIllegalCharacters_ThrowsException(string $path): void {
			$this->expectException(\InvalidArgumentException::class);
			new Path($path);
		}

		/**
		 * @dataProvider data_Construct_WhenDocumentRootIsAbsent_ThrowsException
		 */
		public function test_Construct_WhenDocumentRootIsAbsent_ThrowsException(string $path): void {
			$this->expectException(\UnexpectedValueException::class);
			new Path($path, PATH::PATH_DOCUMENT_ROOT);
		}

		public function test_Construct_WhenDocumentRootIsPresent_CreatesObject(): void {} // TODO
		public function test_getAbsolute_DoesNotContainLastSlash(): void {} // TODO
		public function test_getAbsolute_AlwaysStartsWithSlashOrDrive(): void {} // TODO
		public function test_getAbsolute_DoesNotContainParentJumps(): void {} // TODO
		public function test_getAbsolute_DoesNotContainCwd(): void {} // TODO
		public function test_getAbsolute_DoesNotContainMultipleSlashes(): void {} // TODO
		public function test_getAbsolute_ReplacesDirectorySeparators(): void {} // TODO

		public function data_Construct_WhenPathHasManyParentJumps_ThrowsException(): array {
			return [
				[
					'C:/..'
				],
				[
					'G:\..'
				],
				[
					'G:\..\\'
				],
				[
					'd:\../'
				],
				[
					'/..',
				],
				[
					'../../../../../../../../../../../../../../../'
				],
				[
					'..\\..\\..\\..\\..\\..\\..\\..\\..\\..\\..\\..\\'
				]
			];
		}

		public function data_Construct_WhenPathHasIllegalCharacters_ThrowsException(): array {
			return [
				[
					"C:\n\\.."
				],
				[
					"C:\n"
				],
				[
					"./bin\r"
				],
				[
					"/bin\r"
				]
			];
		}

		public function data_Construct_WhenDocumentRootIsAbsent_ThrowsException(): array {
			return [
				[
					'css'
				],
				[
					'/css'
				],
				[
					'\css'
				],
				[
					'../css'
				],
				[
					'./css'
				],
				[
					'.'
				],
				[
					'..'
				]
			];
		}
	}
	// TODO: Find a way to test relative paths
