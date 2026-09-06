<?php

namespace App\DTOs;

class EpisodeDTO
{
    public function __construct(
        public readonly int $externalId,
        public readonly string $name,
        public readonly ?string $airDate,
        public readonly ?string $episodeCode,
        public readonly array $rawData
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            externalId: $data['id'],
            name: $data['name'],
            airDate: $data['air_date'] ?? null,
            episodeCode: $data['episode'] ?? null,
            rawData: $data
        );
    }
}