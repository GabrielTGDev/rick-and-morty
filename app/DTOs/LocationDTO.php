<?php

namespace App\DTOs;

class LocationDTO
{
    public function __construct(
        public readonly int $externalId,
        public readonly string $name,
        public readonly ?string $type,
        public readonly ?string $dimension,
        public readonly array $rawData
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            externalId: $data['id'],
            name: $data['name'],
            type: $data['type'] ?? null,
            dimension: $data['dimension'] ?? null,
            rawData: $data
        );
    }
}