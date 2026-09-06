<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\ExternalApiException;
use App\Exceptions\InvalidExternalDataException;
use App\DTOs\CharacterDTO;
use App\Services\RickAndMortyApiClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RickAndMortyApiClientTest extends TestCase
{
    public function test_it_fetches_characters_successfully_with_faked_http(): void
    {
        Http::fake([
            'https://rickandmortyapi.com/api/character*' => Http::response([
                'info' => ['next' => null],
                'results' => [
                    ['id' => 1, 'name' => 'Rick Sanchez', 'episode' => []]
                ]
            ], 200)
        ]);

        $client = new RickAndMortyApiClient();
        $result = $client->getCharacters(1);

        $this->assertFalse($result['has_next']);
        $this->assertCount(1, $result['data']);
        $this->assertInstanceOf(CharacterDTO::class, $result['data'][0]);
        $this->assertSame('Rick Sanchez', $result['data'][0]->name);
    }

    public function test_it_throws_external_api_exception_on_server_error(): void
    {
        $this->expectException(ExternalApiException::class);

        Http::fake([
            'https://rickandmortyapi.com/api/character*' => Http::response(null, 500)
        ]);

        $client = new RickAndMortyApiClient();
        $client->getCharacters(1);
    }

    public function test_it_throws_invalid_external_data_exception_for_malformed_json(): void
    {
        $this->expectException(InvalidExternalDataException::class);

        Http::fake([
            'https://rickandmortyapi.com/api/character*' => Http::response([
                'info' => ['next' => null],
            ], 200),
        ]);

        $client = new RickAndMortyApiClient();
        $client->getCharacters(1);
    }
}
