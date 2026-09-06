<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTOs\EpisodeDTO;
use PHPUnit\Framework\TestCase;

class EpisodeDTOTest extends TestCase
{
    public function test_it_maps_episode_data_to_dto(): void
    {
        $payload = [
            'id' => 1,
            'name' => 'Pilot',
            'air_date' => 'December 2, 2013',
            'episode' => 'S01E01',
        ];

        $dto = EpisodeDTO::fromArray($payload);

        $this->assertSame(1, $dto->externalId);
        $this->assertSame('Pilot', $dto->name);
        $this->assertSame('December 2, 2013', $dto->airDate);
        $this->assertSame('S01E01', $dto->episodeCode);
        $this->assertSame($payload, $dto->rawData);
    }

    public function test_it_maps_missing_optional_episode_fields_to_null(): void
    {
        $dto = EpisodeDTO::fromArray([
            'id' => 2,
            'name' => 'Unknown episode',
        ]);

        $this->assertNull($dto->airDate);
        $this->assertNull($dto->episodeCode);
    }
}
