<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTOs\CharacterDTO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CharacterDTOTest extends TestCase
{
    public function test_it_correctly_maps_valid_json_payload_to_dto(): void
    {
        $payload = [
            'id' => 1,
            'name' => 'Rick Sanchez',
            'status' => 'Alive',
            'species' => 'Human',
            'type' => '',
            'gender' => 'Male',
            'image' => 'https://rickandmortyapi.com/api/character/avatar/1.jpeg',
            'origin' => ['name' => 'Earth', 'url' => 'https://rickandmortyapi.com/api/location/1'],
            'location' => ['name' => 'Earth', 'url' => 'https://rickandmortyapi.com/api/location/20'],
            'episode' => [
                'https://rickandmortyapi.com/api/episode/1',
                'https://rickandmortyapi.com/api/episode/2'
            ]
        ];

        $dto = CharacterDTO::fromArray($payload);

        $this->assertSame(1, $dto->externalId);
        $this->assertSame('Rick Sanchez', $dto->name);
        $this->assertSame(1, $dto->originExternalId);
        $this->assertSame(20, $dto->locationExternalId);
        $this->assertSame([1, 2], $dto->episodeExternalIds);
    }

    #[DataProvider('emptyOriginUrlProvider')]
    public function test_it_handles_empty_or_null_origin_url(?string $originUrl): void
    {
        $dto = CharacterDTO::fromArray([
            'id' => 1,
            'name' => 'Rick Sanchez',
            'origin' => ['url' => $originUrl],
            'location' => ['url' => null],
            'episode' => [],
        ]);

        $this->assertNull($dto->originExternalId);
        $this->assertNull($dto->locationExternalId);
        $this->assertSame([], $dto->episodeExternalIds);
    }

    public static function emptyOriginUrlProvider(): array
    {
        return [
            'null URL' => [null],
            'empty URL' => [''],
        ];
    }
}
