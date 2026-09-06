<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Character;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_registers_and_logs_in_a_user(): void
    {
        $credentials = [
            'name' => 'Morty Smith',
            'email' => 'morty@example.com',
            'password' => 'secret-password',
        ];

        $registerResponse = $this->postJson('/api/register', $credentials);

        $registerResponse
            ->assertCreated()
            ->assertJsonStructure(['token']);

        $loginResponse = $this->postJson('/api/login', [
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonStructure(['token']);
    }

    public function test_it_filters_characters_and_returns_a_homogeneous_json_response(): void
    {
        Character::create([
            'external_id' => 1,
            'name' => 'Rick Sanchez',
        ]);
        Character::create([
            'external_id' => 2,
            'name' => 'Morty Smith',
        ]);

        $response = $this->getJson('/api/characters?name=Rick');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'current_page',
                    'data',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.data.0.name', 'Rick Sanchez')
            ->assertJsonPath('data.total', 1);
    }

    public function test_it_rejects_favorite_access_without_a_token(): void
    {
        $response = $this->postJson('/api/characters/1/favorite');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_it_adds_a_character_to_the_authenticated_users_favorites(): void
    {
        $user = User::factory()->create([
            'api_token' => 'valid-test-token',
        ]);
        $character = Character::create([
            'external_id' => 1,
            'name' => 'Rick Sanchez',
        ]);

        $response = $this
            ->withToken('valid-test-token')
            ->postJson("/api/characters/{$character->id}/favorite");

        $response
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('character_user', [
            'user_id' => $user->id,
            'character_id' => $character->id,
        ]);
    }
}
