<?php

namespace Tests\Feature\Admin\EmailCenter;

use App\Mail\BroadcastMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BroadcastMailRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_mail_renders_subject_and_body(): void
    {
        $mailable = new BroadcastMail(
            subjectLine: 'Test Broadcast',
            bodyHtml: '<p>Hello <strong>world</strong></p>',
            broadcastAttachments: [],
        );

        $mailable->assertHasSubject('Test Broadcast');
        $mailable->assertSeeInHtml('Hello');
        $mailable->assertSeeInHtml('<strong>world</strong>', false);
    }

    public function test_mail_includes_attachments(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('email-attachments/sample.pdf', '%PDF-1.4');

        $mailable = new BroadcastMail(
            subjectLine: 'With File',
            bodyHtml: '<p>See attached</p>',
            broadcastAttachments: [['path' => 'email-attachments/sample.pdf', 'original_name' => 'doc.pdf', 'mime' => 'application/pdf', 'size' => 8]],
        );

        // Directly verify the mailable declares the attachment with the expected metadata.
        $attachments = $mailable->attachments();
        $this->assertCount(1, $attachments);
        $this->assertSame('doc.pdf', $attachments[0]->as);
        $this->assertSame('application/pdf', $attachments[0]->mime);
    }
}
