<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_homepage_shows_search(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('Search products, brands, and categories');
    }

    public function test_search_path_redirects_to_homepage(): void
    {
        $this->get('/search')
            ->assertMovedPermanently()
            ->assertRedirect('/');
    }
}
