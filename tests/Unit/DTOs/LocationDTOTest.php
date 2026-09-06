<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTOs\LocationDTO;
use PHPUnit\Framework\TestCase;

class LocationDTOTest extends TestCase
{
    public function test_it_maps_location_data_to_dto(): void
    {
        $payload = [
            'id' => 1,
            'name' => 'Earth',
            'type' => 'Planet',
            'dimension' => 'Dimension C-137',
        ];

        $dto = LocationDTO::fromArray($payload);

        $this->assertSame(1, $dto->externalId);
        $this->assertSame('Earth', $dto->name);
        $this->assertSame('Planet', $dto->type);
        $this->assertSame('Dimension C-137', $dto->dimension);
        $this->assertSame($payload, $dto->rawData);
    }

    public function test_it_maps_missing_optional_location_fields_to_null(): void
    {
        $dto = LocationDTO::fromArray([
            'id' => 2,
            'name' => 'Unknown location',
        ]);

        $this->assertNull($dto->type);
        $this->assertNull($dto->dimension);
    }
}
