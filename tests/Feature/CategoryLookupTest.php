<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_numeric_category_identifier_resolves_by_primary_key(): void
    {
        $category = Category::factory()->create(['slug' => 'brakes']);

        // A numeric segment takes the primary key branch of the lookup, which
        // used to call the non-existent Builder::orWhereKey() and threw a
        // BadMethodCallException before anything could match.
        $this->get('/categories/'.$category->id)
            ->assertRedirect(route('shop.index', ['category' => 'brakes']));
    }

    public function test_category_still_resolves_by_slug(): void
    {
        Category::factory()->create(['slug' => 'engines']);

        $this->get('/categories/engines')
            ->assertRedirect(route('shop.index', ['category' => 'engines']));
    }

    public function test_unknown_category_identifier_is_not_found(): void
    {
        $this->get('/categories/9999')->assertNotFound();
    }
}
