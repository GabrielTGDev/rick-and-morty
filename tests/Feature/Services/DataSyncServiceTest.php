<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\RickAndMortyApiClientInterface;
use App\DTOs\CharacterDTO;
use App\Models\Character;
use App\Models\Episode;
use App\Models\Location;
use App\Services\DataSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DataSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_maps_locations_and_episodes_to_database(): void
    {
        $client = Mockery::mock(RickAndMortyApiClientInterface::class);
        $client->shouldReceive('getLocations')->once()->with(1)->andReturn([
            'has_next' => false,
            'data' => [
                (object) [
                    'externalId' => 1,
                    'name' => 'Earth',
                    'type' => 'Planet',
                    'dimension' => 'Dimension C-137',
                    'rawData' => ['id' => 1],
                ],
            ],
        ]);
        $client->shouldReceive('getEpisodes')->once()->with(1)->andReturn([
            'has_next' => false,
            'data' => [
                (object) [
                    'externalId' => 1,
                    'name' => 'Pilot',
                    'airDate' => 'December 2, 2013',
                    'episodeCode' => 'S01E01',
                    'rawData' => ['id' => 1],
                ],
            ],
        ]);

        $service = new DataSyncService($client);

        $this->assertSame(1, $service->syncLocations());
        $this->assertSame(1, $service->syncEpisodes());
        $this->assertDatabaseHas('locations', [
            'external_id' => 1,
            'name' => 'Earth',
        ]);
        $this->assertDatabaseHas('episodes', [
            'external_id' => 1,
            'episode_code' => 'S01E01',
        ]);
    }

    public function test_it_maps_character_locations_and_episode_relationships(): void
    {
        $origin = Location::create([
            'external_id' => 1,
            'name' => 'Earth',
        ]);
        $currentLocation = Location::create([
            'external_id' => 20,
            'name' => 'Earth (Replacement Dimension)',
        ]);
        $episode = Episode::create([
            'external_id' => 1,
            'name' => 'Pilot',
        ]);

        $client = Mockery::mock(RickAndMortyApiClientInterface::class);
        $client->shouldReceive('getCharacters')->once()->with(1)->andReturn([
            'has_next' => false,
            'data' => [CharacterDTO::fromArray([
                'id' => 1,
                'name' => 'Rick Sanchez',
                'origin' => ['url' => 'https://rickandmortyapi.com/api/location/1'],
                'location' => ['url' => 'https://rickandmortyapi.com/api/location/20'],
                'episode' => ['https://rickandmortyapi.com/api/episode/1'],
            ])],
        ]);

        $service = new DataSyncService($client);

        $this->assertSame(1, $service->syncCharacters());
        $this->assertDatabaseHas('characters', [
            'external_id' => 1,
            'name' => 'Rick Sanchez',
            'origin_location_id' => $origin->id,
            'current_location_id' => $currentLocation->id,
        ]);
        $character = Character::where('external_id', 1)->firstOrFail();

        $this->assertDatabaseHas('character_episode', [
            'character_id' => $character->id,
            'episode_id' => $episode->id,
        ]);
    }
}
