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
		
		public function test_Construct_WhenPathHasIllegalCharacters_ThrowsException(): void {}
		public function test_Construct_WhenDocumentRootIsAbsent_ThrowsException(): void {}
		public function test_Construct_WhenDocumentRootIsPresent_CreatesObject(): void {}
		public function test_getAbsolute_IsCorrect(): void {}

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
					'/../../../../../../../../../../../../../../../'
				]
			];
		}
	}
