<?php


namespace Tests\Feature;

use App\Services\CommentService\CommentFileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test; // Import the Test attribute

class CommentFileServiceTest extends TestCase
{
    protected CommentFileService $commentFileService;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public'); // Use fake public disk
        $this->commentFileService = new CommentFileService();
    }

    #[Test] // Updated from @test
    public function it_returns_an_empty_array_if_no_files_are_provided(): void
    {
        $uploadedFilesData = $this->commentFileService->uploadFiles([]);
        $this->assertCount(0, $uploadedFilesData);
        $this->assertEquals([], $uploadedFilesData);
    }

    #[Test] // Updated from @test
    public function filenames_are_unique_and_hashed_correctly(): void // Renamed for clarity
    {
        $file1 = UploadedFile::fake()->image('test_image.jpg');
        $originalExtension1 = $file1->getClientOriginalExtension();
        // If getClientOriginalExtension() is empty for some fakes, use hashName's extension part
        if (empty($originalExtension1)) {
            $originalExtension1 = Str::afterLast($file1->hashName(), '.');
        }


        $file2 = UploadedFile::fake()->image('another_image.png');
        $originalExtension2 = $file2->getClientOriginalExtension();
        if (empty($originalExtension2)) {
            $originalExtension2 = Str::afterLast($file2->hashName(), '.');
        }


        $uploadedData1 = $this->commentFileService->uploadFiles($file1);
        $uploadedData2 = $this->commentFileService->uploadFiles($file2);

        $this->assertNotEmpty($uploadedData1, 'Uploaded data for file 1 should not be empty.');
        $this->assertArrayHasKey('filename', $uploadedData1[0], 'Filename key missing in uploaded data for file 1.');
        $filename1 = $uploadedData1[0]['filename'];

        $this->assertNotEmpty($uploadedData2, 'Uploaded data for file 2 should not be empty.');
        $this->assertArrayHasKey('filename', $uploadedData2[0], 'Filename key missing in uploaded data for file 2.');
        $filename2 = $uploadedData2[0]['filename'];

        $this->assertNotEquals($file1->getClientOriginalName(), $filename1);
        $this->assertNotEquals($filename1, $filename2);

        $baseName1 = Str::beforeLast($filename1, '.');
        $extension1 = Str::afterLast($filename1, '.');

        // Adjusted assertion to expect 36 characters for the base name
        $this->assertEquals(36, Str::length($baseName1), "Hashed part of filename1 should be 36 characters (e.g., a UUID).");
        $this->assertEquals($originalExtension1, $extension1, "Extension of filename1 does not match original.");

        $baseName2 = Str::beforeLast($filename2, '.');
        $extension2 = Str::afterLast($filename2, '.');

        // Adjusted assertion to expect 36 characters for the base name
        $this->assertEquals(36, Str::length($baseName2), "Hashed part of filename2 should be 36 characters (e.g., a UUID).");
        $this->assertEquals($originalExtension2, $extension2, "Extension of filename2 does not match original.");
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset Carbon
        parent::tearDown();
    }
}
