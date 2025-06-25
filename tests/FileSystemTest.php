<?php

use PHPUnit\Framework\TestCase;

// Adjust the path based on actual file structure and autoloader if any
require_once __DIR__ . '/../libs/geograph/filesystem.class.php';
// S3.php is required by FileSystem.class.php. Ensure it can be found.
// If an autoloader is used in the project, this might not be necessary.
// For now, assuming direct require is needed for the testing environment.
// The FileSystem class itself does: require_once "3rdparty/S3.php";
// This path seems relative to FileSystem.class.php, so it should be:
// require_once __DIR__ . '/../libs/geograph/3rdparty/S3.php';
// However, FileSystem.class.php includes it with a relative path "3rdparty/S3.php".
// This means PHP's include_path must be set up correctly for FileSystem.class.php to find S3.php.
// For the test to run, we might need to ensure the include_path allows finding libs/3rdparty/S3.php
// or mock/stub S3 class if its methods are directly called by FileSystem's constructor or other non-static methods.
// For now, the FileSystem class itself handles requiring S3.php.

// Mock the S3 class methods that FileSystem::glob will interact with.
// We are primarily interested in S3::getBucket() which is static.
// PHPUnit's createMock can't mock static methods directly in the same way as instance methods.
// We might need to use a more advanced mocking library or refactor FileSystem to allow S3 dependency injection.
// A simpler approach for now, if FileSystem directly calls parent::getBucket(),
// is to make our test class extend FileSystem and override getBucket for testing purposes,
// or use a test helper that can redefine static methods if the testing framework supports it.

// Given FileSystem extends S3, and glob calls parent::getBucket,
// we can create a partial mock of FileSystem and mock its *own* getBucket method
// if it were to call $this->getBucket(). But it calls parent::getBucket().

// Let's assume for now we can find a way to control what S3::getBucket() returns.
// One common way is to have a test-specific S3 class or use a library that allows static method mocking.
// If not, tests will be harder to isolate.

// For now, let's write the structure and then figure out mocking S3::getBucket.

class FileSystemTest extends TestCase
{
    private $fileSystemMock;
    private $originalBuckets;

    protected function setUp(): void
    {
        // We need to mock FileSystem to control its dependencies or behavior.
        // Specifically, we need to control getBucketPath and potentially S3::getBucket.

        // Mocking getBucketPath to control if a path is treated as S3 or local
        $this->fileSystemMock = $this->getMockBuilder(FileSystem::class)
            ->disableOriginalConstructor() // Avoid issues with S3 constructor if it makes real calls
            ->onlyMethods(['getBucketPath', 'getObjectInfo']) // Add other methods we need to control
            ->getMock();

        // Preserve and restore global or static states if necessary, e.g., $CONF
        // For S3 buckets configuration in FileSystem constructor:
        // $this->buckets["{$_SERVER['BASE_DIR']}/public_html/"] = $CONF['s3_photos_bucket_path'];
        // This part is tricky without a proper DI or service locator.
        // The constructor of FileSystem tries to connect to AWS.
        // By disabling original constructor, we avoid this. We'll need to manually set bucket paths if needed.

        // To control bucket mapping for getBucketPath:
        // Let's assume getBucketPath will be mocked to return specific bucket_name and s3_path.
    }

    protected function tearDown(): void
    {
        // Clean up any static mocks or restore states
        // Mockery::close(); // If using Mockery
    }

    // Helper to assert array contents regardless of order for filename-only lists
    private function assertArrayContentsEqual(array $expected, array $actual, string $message = ''): void
    {
        sort($expected);
        sort($actual);
        $this->assertEquals($expected, $actual, $message);
    }

    public function testGlobS3SingleWildcardFullMetadata()
    {
        $path = "/var/www/geograph/public_html/photos/12345_a4b*";
        $expectedPrefix = "photos/12345_a4b"; // Path inside bucket
        $s3Objects = [
            'photos/12345_a4b_file1.jpg' => ['name' => 'photos/12345_a4b_file1.jpg', 'size' => 100, 'time' => 1678886400],
            'photos/12345_a4b_file2.png' => ['name' => 'photos/12345_a4b_file2.png', 'size' => 200, 'time' => 1678886401],
            'photos/12345_another.jpg' => ['name' => 'photos/12345_another.jpg', 'size' => 300, 'time' => 1678886402], // Should not match prefix
        ];
        $expectedResults = [
            'photos/12345_a4b_file1.jpg' => ['name' => 'photos/12345_a4b_file1.jpg', 'size' => 100, 'time' => 1678886400],
            'photos/12345_a4b_file2.png' => ['name' => 'photos/12345_a4b_file2.png', 'size' => 200, 'time' => 1678886401],
        ];

        $this->fileSystemMock->method('getBucketPath')
            ->willReturn(['my-s3-bucket', $expectedPrefix . '*']); // Simulate s3_path construction

        // This is the hard part: Mocking S3::getBucket (static method of parent)
        // For now, this test will not pass without a proper way to mock static parent::getBucket
        // One workaround: If we can't mock S3::getBucket directly, we might need to
        // create a test-specific class that extends FileSystem and overrides getBucket,
        // or use a library like AspectMock or Patchwork.
        // Let's assume we have a mechanism (placeholder for now)
        // S3::staticExpects('getBucket')
        //    ->with('my-s3-bucket', $expectedPrefix) // Only prefix is passed to getBucket
        //    ->andReturn($s3Objects);

        // Due to limitations with PHPUnit and static parent calls, this test case is more of a blueprint.
        // Actual execution would require a more sophisticated test setup for S3::getBucket.
        // For the purpose of this exercise, I will write the test logic as if S3::getBucket could be mocked.
        // If this were a real project, addressing this mocking challenge would be a priority.

        // $actualResults = $this->fileSystemMock->glob($path, true);
        // $this->assertEquals($expectedResults, $actualResults, "Failed S3 glob with full metadata.");
        $this->markTestIncomplete('Requires static method mocking for S3::getBucket or FileSystem refactoring for DI.');
    }

    public function testGlobS3SingleWildcardFileNamesOnly()
    {
        $path = "/var/www/geograph/public_html/photos/12345_a4b*";
        $expectedPrefix = "photos/12345_a4b";
        $s3Objects = [
            'photos/12345_a4b_file1.jpg' => ['name' => 'photos/12345_a4b_file1.jpg', /* ... */],
            'photos/12345_a4b_file2.png' => ['name' => 'photos/12345_a4b_file2.png', /* ... */],
        ];
        $expectedResults = [
            'photos/12345_a4b_file1.jpg',
            'photos/12345_a4b_file2.png',
        ];

        $this->fileSystemMock->method('getBucketPath')
            ->willReturn(['my-s3-bucket', $expectedPrefix . '*']);

        // S3::staticExpects('getBucket')->andReturn($s3Objects); // Placeholder for mocking

        // $actualResults = $this->fileSystemMock->glob($path, false);
        // $this->assertArrayContentsEqual($expectedResults, $actualResults, "Failed S3 glob with filenames only.");
        $this->markTestIncomplete('Requires static method mocking for S3::getBucket.');
    }

    public function testGlobS3WildcardWithSuffixFiltering()
    {
        // $path = "/var/www/geograph/public_html/photos/12345_a4b*_specific.jpg";
        // $expectedS3Path = "photos/12345_a4b*_specific.jpg";
        // $expectedPrefix = "photos/12345_a4b";
        // $expectedSuffix = "_specific.jpg";

        $path = "/var/www/geograph/public_html/photos/prefix*_suffix.jpg";
        $expectedS3Path = "photos/prefix*_suffix.jpg"; // This is what getBucketPath would transform path to
        $expectedBucketPrefix = "photos/prefix"; // This is what glob passes to S3::getBucket
        $expectedSuffixFilter = "_suffix.jpg";  // This is what glob uses to filter results

        $this->fileSystemMock->method('getBucketPath')
            ->willReturn(['my-s3-bucket', $expectedS3Path]);

        $s3ObjectsReturnedByGetBucket = [
            // These would be returned by S3::getBucket('my-s3-bucket', 'photos/prefix')
            'photos/prefix_file1_suffix.jpg' => ['name' => 'photos/prefix_file1_suffix.jpg'],
            'photos/prefix_file2.jpg'        => ['name' => 'photos/prefix_file2.jpg'], // Should be filtered out by suffix
            'photos/prefix_another_suffix.jpg' => ['name' => 'photos/prefix_another_suffix.jpg'],
            'photos/prefix_yet_another_suffix.png' => ['name' => 'photos/prefix_yet_another_suffix.png'], // Wrong extension
        ];

        $expectedResultsFullMeta = [
            'photos/prefix_file1_suffix.jpg' => ['name' => 'photos/prefix_file1_suffix.jpg'],
            'photos/prefix_another_suffix.jpg' => ['name' => 'photos/prefix_another_suffix.jpg'],
        ];
        $expectedResultsNamesOnly = [
            'photos/prefix_file1_suffix.jpg',
            'photos/prefix_another_suffix.jpg',
        ];

        // S3::staticExpects('getBucket')
        //    ->with('my-s3-bucket', $expectedBucketPrefix)
        //    ->andReturn($s3ObjectsReturnedByGetBucket); // Placeholder

        // $actualResultsMeta = $this->fileSystemMock->glob($path, true);
        // $this->assertEquals($expectedResultsFullMeta, $actualResultsMeta, "Failed S3 glob with suffix filter (full metadata).");

        // $actualResultsNames = $this->fileSystemMock->glob($path, false);
        // $this->assertArrayContentsEqual($expectedResultsNamesOnly, $actualResultsNames, "Failed S3 glob with suffix filter (names only).");
        $this->markTestIncomplete('Requires static method mocking for S3::getBucket.');
    }


    public function testGlobS3NoWildcardFileExists()
    {
        $path = "/var/www/geograph/public_html/photos/exactfile.jpg";
        $s3Path = "photos/exactfile.jpg";
        $objectInfo = ['name' => 'photos/exactfile.jpg', 'size' => 123, 'time' => 1678886400, 'type' => 'image/jpeg'];

        $this->fileSystemMock->method('getBucketPath')
            ->willReturn(['my-s3-bucket', $s3Path]);

        $this->fileSystemMock->method('getObjectInfo')
            ->with('my-s3-bucket', $s3Path)
            ->willReturn($objectInfo);

        $actualResultsMeta = $this->fileSystemMock->glob($path, true);
        $this->assertEquals([$s3Path => $objectInfo], $actualResultsMeta, "Failed S3 glob no wildcard, file exists (full metadata).");

        $actualResultsNames = $this->fileSystemMock->glob($path, false);
        $this->assertEquals([$s3Path], $actualResultsNames, "Failed S3 glob no wildcard, file exists (names only).");
    }

    public function testGlobS3NoWildcardFileDoesNotExist()
    {
        $path = "/var/www/geograph/public_html/photos/nonexistent.jpg";
        $s3Path = "photos/nonexistent.jpg";

        $this->fileSystemMock->method('getBucketPath')
            ->willReturn(['my-s3-bucket', $s3Path]);

        $this->fileSystemMock->method('getObjectInfo')
            ->with('my-s3-bucket', $s3Path)
            ->willReturn(false); // Simulate file not found

        $actualResultsMeta = $this->fileSystemMock->glob($path, true);
        $this->assertEquals([], $actualResultsMeta, "Failed S3 glob no wildcard, file does not exist (full metadata).");

        $actualResultsNames = $this->fileSystemMock->glob($path, false);
        $this->assertEquals([], $actualResultsNames, "Failed S3 glob no wildcard, file does not exist (names only).");
    }

    public function testGlobS3WildcardNoMatches()
    {
        $path = "/var/www/geograph/public_html/photos/nomatch_*";
        $expectedS3Path = "photos/nomatch_*";
        $expectedBucketPrefix = "photos/nomatch_";

        $this->fileSystemMock->method('getBucketPath')
            ->willReturn(['my-s3-bucket', $expectedS3Path]);

        // S3::staticExpects('getBucket')
        //    ->with('my-s3-bucket', $expectedBucketPrefix)
        //    ->andReturn([]); // No objects found by S3

        // $actualResultsMeta = $this->fileSystemMock->glob($path, true);
        // $this->assertEquals([], $actualResultsMeta, "Failed S3 glob with wildcard, no matches (full metadata).");

        // $actualResultsNames = $this->fileSystemMock->glob($path, false);
        // $this->assertEquals([], $actualResultsNames, "Failed S3 glob with wildcard, no matches (names only).");
        $this->markTestIncomplete('Requires static method mocking for S3::getBucket.');
    }

    // It might be complex to test the local glob fallback accurately without a real filesystem
    // or more intricate mocking of global functions like glob() and stat().
    // For now, focusing on S3 logic.
    public function testGlobLocalPath()
    {
        // This test would require mocking global `glob` and `stat` or a virtual filesystem.
        // $path = "/tmp/local_test_file*";
        // $this->fileSystemMock->method('getBucketPath')
        //     ->willReturn([null, $path]); // Indicates a local path

        // Mock global glob() and stat() here if possible with the test framework.
        // Example (pseudo-code, actual mocking depends on tools like uopz or php-test-helpers):
        // mock_function('glob', function($pattern) { return ["/tmp/local_test_file1.txt"]; });
        // mock_function('stat', function($filename) { return ['size' => 100, 'mtime' => 123]; });

        // $actualResults = $this->fileSystemMock->glob($path, false);
        // $this->assertEquals(["/tmp/local_test_file1.txt"], $actualResults);
        $this->markTestIncomplete('Local path testing requires global function mocking or VFS.');
    }
}

// Placeholder for S3 static method mocking if a library like Mockery with static mocking capabilities were used.
// class S3 { // Simplified mock for demonstration
//     public static $mocks = [];
//     public static function getBucket($bucket, $prefix = null) {
//         if (isset(self::$mocks['getBucket'])) {
//             $closure = self::$mocks['getBucket'];
//             return $closure($bucket, $prefix);
//         }
//         return []; // Default mock behavior
//     }
//     public static function staticExpects($methodName) {
//         // Fluent interface for setting up expectations
//         // return new StaticExpectation(self::class, $methodName);
//     }
// }
?>
