<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiNotFoundResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_missing_record_does_not_name_the_model_behind_it(): void
    {
        $response = $this->getJson('/api/mobile/products/does-not-exist');

        $response->assertNotFound();
        $response->assertExactJson(['message' => 'Not found.']);
        $this->assertStringNotContainsString('Models', (string) $response->getContent());
    }

    public function test_a_missing_page_still_gets_the_designed_error_page(): void
    {
        $this->get('/shop/products/does-not-exist')
            ->assertNotFound()
            ->assertSee('404');
    }
}
