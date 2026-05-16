<?php

namespace Tests\Feature\Api\User;

use App\Models\User;
use App\Support\Pagination;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_paginated_list_with_default_per_page(): void
    {
        User::factory()->count(30)->create();

        $this->getJson('/api/users')
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonPath('meta.per_page', Pagination::DEFAULT_PER_PAGE)
            ->assertJsonPath('meta.total', 30)
            ->assertJsonCount(Pagination::DEFAULT_PER_PAGE, 'data');
    }

    public function test_per_page_is_honored(): void
    {
        User::factory()->count(10)->create();

        $this->getJson('/api/users?per_page=5')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonCount(5, 'data');
    }

    public function test_per_page_above_max_is_rejected(): void
    {
        $this->getJson('/api/users?per_page='.(Pagination::MAX_PER_PAGE + 1))
            ->assertStatus(422)
            ->assertJsonValidationErrors('per_page');
    }

    public function test_per_page_below_one_is_rejected(): void
    {
        $this->getJson('/api/users?per_page=0')
            ->assertStatus(422)
            ->assertJsonValidationErrors('per_page');
    }

    public function test_returns_expected_resource_shape(): void
    {
        User::factory()->create();

        $this->getJson('/api/users')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email', 'created_at', 'updated_at'],
                ],
            ]);
    }

    public function test_does_not_leak_password_or_remember_token(): void
    {
        User::factory()->create();

        $response = $this->getJson('/api/users')->assertOk();
        $first = $response->json('data.0');

        $this->assertArrayNotHasKey('password', $first);
        $this->assertArrayNotHasKey('remember_token', $first);
    }

    public function test_paginates_to_second_page(): void
    {
        User::factory()->count(7)->create();

        $this->getJson('/api/users?per_page=5&page=2')
            ->assertOk()
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonCount(2, 'data');
    }
}
