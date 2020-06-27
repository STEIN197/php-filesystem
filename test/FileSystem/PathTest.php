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

		public function test_Construct_WhenDocumentRootIsAbsent_ThrowsException(): void {
			$this->expectException(\UnexpectedValueException::class);
			new Path('.', Path::PATH_DOCUMENT_ROOT);
		}

		public function test_Construct_WhenDocumentRootIsPresent_CreatesObject(): void {
			$_SERVER['DOCUMENT_ROOT'] = getcwd();
			new Path('/', Path::PATH_DOCUMENT_ROOT);
			$this->assertTrue(true);
		}

		/**
		 * @dataProvider data_getAbsolute_AlwaysStartsWithSlashOrDrive
		 */
		public function test_getAbsolute_AlwaysStartsWithSlashOrDrive(string $path): void {
			$p = new Path($path);
			$expectedDrive = explode(\DIRECTORY_SEPARATOR, getcwd())[0].\DIRECTORY_SEPARATOR;
			$actualDrive = explode(\DIRECTORY_SEPARATOR, $p->getAbsolute())[0].\DIRECTORY_SEPARATOR;
			$this->assertEquals($expectedDrive, $actualDrive, "Resulting path: '{$p->getAbsolute()}'");
		}

		/**
		 * @dataProvider data_getDocRootPath_ResolvesRelativeToDocRoot
		 */
		public function test_getDocRootPath_ResolvesRelativeToDocRoot(string $path, ?string $expected): void {
			$_SERVER['DOCUMENT_ROOT'] = getcwd();
			$p = new Path($path, Path::PATH_DOCUMENT_ROOT);
			$this->assertEquals($expected, $p->getDocRootPath());
		}

		/**
		 * @dataProvider data_RelativePaths_ResolveRelativeToCwd
		 */
		public function test_RelativePaths_ResolveRelativeToCwd(string $path): void {
			$p = new Path($path);
			$this->assertEquals((new Path(getcwd().\DIRECTORY_SEPARATOR.$path))->getAbsolute(), $p->getAbsolute());
		}

		/**
		 * @dataProvider data_getAbsolute_DoesNotContainLastSlash
		 */
		public function test_getAbsolute_DoesNotContainLastSlash(string $path): void {
			$this->assertFalse(in_array(substr((new Path($path))->getAbsolute(), -1), ['/', '\\']));
		}

		/**
		 * @dataProvider data_getAbsolute_DoesNotContainParentJumps
		 */
		public function test_getAbsolute_DoesNotContainParentJumps(string $path): void {} // TODO
		public function test_getAbsolute_DoesNotContainCwd(): void {} // TODO
		public function test_getAbsolute_DoesNotContainMultipleSlashes(): void {} // TODO
		public function test_getAbsolute_ReplacesDirectorySeparators(): void {} // TODO
		public function test_toString_IsCorrect(): void {} // TODO

		public function test_EmptyPaths_EqualsToDot(): void {
			$emptyPath = new Path('');
			$dotPath = new Path('.');
			$this->assertEquals($dotPath->getAbsolute(), $emptyPath->getAbsolute());
		}

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

		public function data_getAbsolute_AlwaysStartsWithSlashOrDrive(): array {
			return [
				[
					'/'
				],
				[
					'C:\\'
				],
				[
					'.'
				],
				[
					'..'
				],
				[
					'var'
				],
				[
					'/var'
				],
				[
					'C:/Windows'
				],
				[
					'../.'
				],
				[
					'\\'
				]
			];
		}

		public function data_getDocRootPath_ResolvesRelativeToDocRoot(): array {
			return [
				[
					'/css', '/css'
				],
				[
					'/css/', '/css'
				],
				[
					'/..', null
				],
				[
					'..', null
				],
				[
					'.', '/'
				],
				[
					'css', '/css'
				],
				[
					'\\', '/'
				],
				[
					'\\index.html', '/index.html'
				],
			];
		}

		public function data_RelativePaths_ResolveRelativeToCwd(): array {
			return [
				[
					'.'
				],
				[
					'..'
				],
				[
					'css'
				],
				[
					'./css'
				],
				[
					'..\\css'
				]
			];
		}

		public function data_getAbsolute_DoesNotContainLastSlash(): array {
			return [
				[
					'/'
				],
				[
					'C:/'
				],
				[
					'..'
				],
				[
					'.'
				],
				[
					'css/'
				],
				[
					'index.php/css/'
				],
			];
		}

		public function data_getAbsolute_DoesNotContainParentJumps(): array {
			return [
				[
					'..'
				],
				[
					'./..'
				],
				[
					'css/..'
				],
				[
					'/home/user/../'
				],
				[
					'C:\\Windows\\../'
				],
			];
		}
	}
	// TODO: Find a way to test relative paths
	// TODO: Test for whitespaces
