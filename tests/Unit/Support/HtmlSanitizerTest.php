<?php

namespace Tests\Unit\Support;

use App\Support\HtmlSanitizer;
use Tests\TestCase;

class HtmlSanitizerTest extends TestCase
{
    private HtmlSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new HtmlSanitizer();
    }

    public function test_strips_script_tags(): void
    {
        $dirty = '<p>Hello</p><script>alert(1)</script>';
        $clean = $this->sanitizer->clean($dirty);
        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringContainsString('Hello', $clean);
    }

    public function test_strips_inline_event_handlers(): void
    {
        $dirty = '<img src="x" onerror="alert(1)">';
        $this->assertStringNotContainsString('onerror', $this->sanitizer->clean($dirty));
    }

    public function test_strips_iframe(): void
    {
        $dirty = '<iframe src="https://evil.test"></iframe>';
        $this->assertStringNotContainsString('iframe', $this->sanitizer->clean($dirty));
    }

    public function test_keeps_basic_formatting(): void
    {
        $dirty = '<p><strong>Bold</strong> and <em>italic</em></p>';
        $clean = $this->sanitizer->clean($dirty);
        $this->assertStringContainsString('<strong>Bold</strong>', $clean);
        $this->assertStringContainsString('<em>italic</em>', $clean);
    }

    public function test_keeps_safe_anchors_with_rel_and_target(): void
    {
        $dirty = '<a href="https://yallaspare.com" target="_blank" rel="noopener">link</a>';
        $clean = $this->sanitizer->clean($dirty);
        $this->assertStringContainsString('href="https://yallaspare.com"', $clean);
        $this->assertStringContainsString('target="_blank"', $clean);
    }

    public function test_strips_javascript_protocol_in_href(): void
    {
        $dirty = '<a href="javascript:alert(1)">click</a>';
        $this->assertStringNotContainsString('javascript:', $this->sanitizer->clean($dirty));
    }

    public function test_keeps_lists_and_headings(): void
    {
        $dirty = '<h2>Title</h2><ul><li>A</li><li>B</li></ul>';
        $clean = $this->sanitizer->clean($dirty);
        $this->assertStringContainsString('<h2>Title</h2>', $clean);
        $this->assertStringContainsString('<li>A</li>', $clean);
    }
}
