<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Models\Character;
use App\Models\Episode;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncRickAndMortyDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_external_data_and_is_idempotent(): void
    {
        Config::set('services.rick_and_morty.retry_times', 1);
        Config::set('services.rick_and_morty.retry_delay', 0);

        Http::fake([
            'https://rickandmortyapi.com/api/location*' => Http::response([
                'info' => [
                    'pages' => 1,
                    'next' => null,
                ],
                'results' => [
                    [
                        'id' => 1,
                        'name' => 'Earth',
                        'type' => 'Planet',
                        'dimension' => 'Dimension C-137',
                    ],
                ],
            ], 200),
            'https://rickandmortyapi.com/api/episode*' => Http::response([
                'info' => [
                    'pages' => 1,
                    'next' => null,
                ],
                'results' => [
                    [
                        'id' => 1,
                        'name' => 'Pilot',
                        'air_date' => 'December 2, 2013',
                        'episode' => 'S01E01',
                    ],
                ],
            ], 200),
            'https://rickandmortyapi.com/api/character*' => Http::response([
                'info' => [
                    'pages' => 1,
                    'next' => null,
                ],
                'results' => [
                    [
                        'id' => 1,
                        'name' => 'Rick Sanchez',
                        'status' => 'Alive',
                        'species' => 'Human',
                        'type' => '',
                        'gender' => 'Male',
                        'image' => 'https://rickandmortyapi.com/api/character/avatar/1.jpeg',
                        'origin' => [
                            'name' => 'Earth',
                            'url' => 'https://rickandmortyapi.com/api/location/1',
                        ],
                        'location' => [
                            'name' => 'Earth',
                            'url' => 'https://rickandmortyapi.com/api/location/1',
                        ],
                        'episode' => [
                            'https://rickandmortyapi.com/api/episode/1',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('app:sync-rick-and-morty')
            ->assertExitCode(0);

        $this->assertDatabaseHas('locations', [
            'external_id' => 1,
            'name' => 'Earth',
        ]);
        $this->assertDatabaseHas('episodes', [
            'external_id' => 1,
            'episode_code' => 'S01E01',
        ]);
        $this->assertDatabaseHas('characters', [
            'external_id' => 1,
            'name' => 'Rick Sanchez',
        ]);

        $firstCounts = [
            'locations' => Location::count(),
            'episodes' => Episode::count(),
            'characters' => Character::count(),
        ];

        $this->artisan('app:sync-rick-and-morty')
            ->assertExitCode(0);

        $this->assertSame($firstCounts, [
            'locations' => Location::count(),
            'episodes' => Episode::count(),
            'characters' => Character::count(),
        ]);
        $this->assertDatabaseCount('locations', 1);
        $this->assertDatabaseCount('episodes', 1);
        $this->assertDatabaseCount('characters', 1);
        $this->assertDatabaseCount('character_episode', 1);
    }
}
