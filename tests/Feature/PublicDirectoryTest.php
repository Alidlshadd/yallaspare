<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicDirectoryTest extends TestCase
{
    /**
     * Design previews are built to be looked at, which makes dropping them in
     * public/ the obvious thing to do and the wrong one: everything in that
     * directory is served to anyone who guesses the filename, with no login in
     * front of it. Eight admin and email mockups sat there for months.
     *
     * They belong somewhere the web server does not serve — the scratch
     * directory, or a branch.
     */
    public function test_no_design_preview_is_left_where_the_web_server_serves_it(): void
    {
        $previews = glob(public_path('*-preview.html')) ?: [];

        $this->assertSame(
            [],
            array_map('basename', $previews),
            'Design previews in public/ are reachable by anyone who guesses the URL.'
        );
    }
}
