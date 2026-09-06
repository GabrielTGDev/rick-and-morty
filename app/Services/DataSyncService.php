<?php

namespace App\Services;

use App\Contracts\RickAndMortyApiClientInterface;
use App\DTOs\CharacterDTO;
use App\Models\Character;
use App\Models\Episode;
use App\Models\Location;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Closure;

class DataSyncService
{
    public function __construct(
        protected RickAndMortyApiClientInterface $client
    ) {}

    public function setProgressCallback(?Closure $callback): void
    {
        $this->client->setProgressCallback($callback);
    }

    public function syncLocations(?callable $onProgress = null): int
    {
        $page = 1;
        $totalSynced = 0;

        do {
            $result = $this->client->getLocations($page);
            DB::transaction(function () use ($result, &$totalSynced) {
                foreach ($result['data'] as $dto) {
                    Location::updateOrCreate(
                        ['external_id' => $dto->externalId],
                        [
                            'name' => $dto->name,
                            'type' => $dto->type,
                            'dimension' => $dto->dimension,
                            'raw_data' => $dto->rawData
                        ]
                    );
                    $totalSynced++;
                }
            });

            if ($onProgress) $onProgress($page, $result['total_pages'] ?? null);
            $page++;
        } while ($result['has_next']);

        return $totalSynced;
    }

    public function syncEpisodes(?callable $onProgress = null): int
    {
        $page = 1;
        $totalSynced = 0;

        do {
            $result = $this->client->getEpisodes($page);
            DB::transaction(function () use ($result, &$totalSynced) {
                foreach ($result['data'] as $dto) {
                    Episode::updateOrCreate(
                        ['external_id' => $dto->externalId],
                        [
                            'name' => $dto->name,
                            'air_date' => $dto->airDate,
                            'episode_code' => $dto->episodeCode,
                            'raw_data' => $dto->rawData
                        ]
                    );
                    $totalSynced++;
                }
            });

            if ($onProgress) $onProgress($page, $result['total_pages'] ?? null);
            $page++;
        } while ($result['has_next']);

        return $totalSynced;
    }

    public function syncCharacters(?callable $onProgress = null): int
    {
        $page = 1;
        $totalSynced = 0;

        // Precargar mapas de external_id => internal_id para rendimiento masivo
        $locationsMap = Location::pluck('id', 'external_id')->toArray();
        $episodesMap = Episode::pluck('id', 'external_id')->toArray();

        do {
            $result = $this->client->getCharacters($page);

            DB::transaction(function () use ($result, $locationsMap, $episodesMap, &$totalSynced) {
                foreach ($result['data'] as $dto) {
                    /** @var CharacterDTO $dto */
                    $originId = $dto->originExternalId ? ($locationsMap[$dto->originExternalId] ?? null) : null;
                    $locationId = $dto->locationExternalId ? ($locationsMap[$dto->locationExternalId] ?? null) : null;

                    $character = Character::updateOrCreate(
                        ['external_id' => $dto->externalId],
                        [
                            'name' => $dto->name,
                            'status' => $dto->status,
                            'species' => $dto->species,
                            'type' => $dto->type,
                            'gender' => $dto->gender,
                            'image' => $dto->image,
                            'origin_location_id' => $originId,
                            'current_location_id' => $locationId,
                            'raw_data' => $dto->rawData
                        ]
                    );

                    // Sincronizar relación N:M con episodios
                    $episodeInternalIds = array_filter(
                        array_map(fn($extId) => $episodesMap[$extId] ?? null, $dto->episodeExternalIds)
                    );
                    $character->episodes()->sync($episodeInternalIds);

                    $totalSynced++;
                }
            });

            if ($onProgress) $onProgress($page, $result['total_pages'] ?? null);
            $page++;
        } while ($result['has_next']);

        return $totalSynced;
    }
}