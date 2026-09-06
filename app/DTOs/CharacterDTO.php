<?php

namespace App\DTOs;

class CharacterDTO
{
    public function __construct(
        public readonly int $externalId,
        public readonly string $name,
        public readonly ?string $status,
        public readonly ?string $species,
        public readonly ?string $type,
        public readonly ?string $gender,
        public readonly ?string $image,
        public readonly ?int $originExternalId,
        public readonly ?int $locationExternalId,
        public readonly array $episodeExternalIds,
        public readonly array $rawData
    ) {}

    public static function fromArray(array $data): self
    {
        // Auxiliary helper to extract numeric ID from a URL like "https://rickandmortyapi.com/api/location/1"
        $extractId = function (?string $url): ?int {
            if (!$url) return null;
            $parts = explode('/', rtrim($url, '/'));
            $lastPart = end($parts);
            return is_numeric($lastPart) ? (int) $lastPart : null;
        };

        $episodeIds = array_map($extractId, $data['episode'] ?? []);

        return new self(
            externalId: $data['id'],
            name: $data['name'],
            status: $data['status'] ?? null,
            species: $data['species'] ?? null,
            type: $data['type'] ?? null,
            gender: $data['gender'] ?? null,
            image: $data['image'] ?? null,
            originExternalId: $extractId($data['origin']['url'] ?? null),
            locationExternalId: $extractId($data['location']['url'] ?? null),
            episodeExternalIds: array_filter($episodeIds),
            rawData: $data
        );
    }
}