<?php

namespace Tests\Feature\Admin\EmailCenter;

use App\Support\SecureImageStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AttachmentValidationTest extends TestCase
{
    public function test_jpeg_is_accepted(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->image('photo.jpg', 600, 400);

        $path = SecureImageStorage::storeAttachment($file, 'email-attachments');

        $this->assertStringStartsWith('email-attachments/', $path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_svg_is_rejected(): void
    {
        Storage::fake('local');
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>';
        $file = UploadedFile::fake()->createWithContent('a.svg', $svg);

        $this->expectException(HttpException::class);
        SecureImageStorage::storeAttachment($file, 'email-attachments');
    }

    public function test_pdf_with_valid_magic_bytes_is_accepted(): void
    {
        Storage::fake('local');
        $pdf = "%PDF-1.4\n%test\n";
        $file = UploadedFile::fake()->createWithContent('doc.pdf', $pdf);

        $path = SecureImageStorage::storeAttachment($file, 'email-attachments');

        $this->assertStringEndsWith('.pdf', $path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_fake_pdf_without_magic_bytes_is_rejected(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('fake.pdf', 'this is not pdf');

        $this->expectException(HttpException::class);
        SecureImageStorage::storeAttachment($file, 'email-attachments');
    }
}
