<?php

namespace Tests\Feature;

use App\Services\AuthService\AvatarService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AvatarServiceTest extends TestCase
{
    protected AvatarService $avatarService;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public'); 
        $this->avatarService = new AvatarService();
    }

    /** @test */
    public function it_uploads_profile_picture_if_file_is_provided_in_request()
    {
        $originalFileName = 'test_avatar';
        $extension = 'jpg';
        $fakeFile = UploadedFile::fake()->image($originalFileName . '.' . $extension)->size(150);

        $request = new Request();
        $request->files->set('profile_picture_url', $fakeFile);
        

        $storedPath = $this->avatarService->generate($request);

        $this->assertNotNull($storedPath);
        $this->assertIsString($storedPath);
        $this->assertTrue(Str::startsWith($storedPath, 'avatars/'), "Path should start with 'avatars/'. Path: " . $storedPath);
        $this->assertTrue(Str::endsWith($storedPath, '.' . $extension), "Path should end with '.{$extension}'. Path: " . $storedPath);

        $filenameInPath = basename($storedPath);
        $this->assertTrue(Str::startsWith($filenameInPath, $originalFileName . '_'), "Filename should start with original name + underscore. Filename: " . $filenameInPath);
        $expectedUuidPartLength = 36;
        $nameAndUnderscoreLength = Str::length($originalFileName . '_');
        $extensionLength = Str::length($extension);
        $uuidPart = Str::substr($filenameInPath, $nameAndUnderscoreLength, $expectedUuidPartLength);

        
        $this->assertEquals($expectedUuidPartLength, Str::length($uuidPart), "UUID part of the filename seems incorrect. Filename: " . $filenameInPath);
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $uuidPart, "Filename does not contain a valid UUID part.");


        Storage::disk('public')->assertExists($storedPath);
    }

    /** @test */
    public function it_generates_initials_avatar_with_default_name_if_no_file_or_full_name_is_provided()
    {
        $request = new Request(); // No files, no input for 'full_name'

        $storedPath = $this->avatarService->generate($request);

        $this->assertNotNull($storedPath);
        $this->assertIsString($storedPath);

        $this->assertTrue(Str::startsWith($storedPath, 'avatars/'), "Path should start with 'avatars/'. Path: " . $storedPath);
        $this->assertTrue(Str::endsWith($storedPath, '.png'), "Generated avatar should be a .png file for default name. Path: " . $storedPath);

        $filenameInPath = basename($storedPath);
        $defaultNameSlug = Str::slug('User'); // Default name is 'User'
        $this->assertTrue(Str::startsWith($filenameInPath, $defaultNameSlug . '_'), "Filename should start with default slugified name 'user' + underscore. Filename: " . $filenameInPath);

        // Check for UUID part
        $expectedUuidPartLength = 36;
        $slugAndUnderscoreLength = Str::length($defaultNameSlug . '_');
        $uuidPart = Str::substr($filenameInPath, $slugAndUnderscoreLength, $expectedUuidPartLength);

        $this->assertEquals($expectedUuidPartLength, Str::length($uuidPart), "UUID part of the filename seems incorrect for default avatar. Filename: " . $filenameInPath);
         $this->assertMatchesRegularExpression('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $uuidPart, "Filename does not contain a valid UUID part for default avatar.");


        Storage::disk('public')->assertExists($storedPath);
        $content = Storage::disk('public')->get($storedPath);
        $this->assertNotEmpty($content, "Generated default avatar file is empty.");
        $this->assertTrue(strpos($content, "\x89PNG\r\n\x1a\n") === 0, "Default avatar file does not appear to be a valid PNG.");
    }
}
