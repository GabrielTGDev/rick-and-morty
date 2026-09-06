<?php

namespace App\Services;

use App\Contracts\RickAndMortyApiClientInterface;
use App\DTOs\CharacterDTO;
use App\DTOs\EpisodeDTO;
use App\DTOs\LocationDTO;
use App\Exceptions\ExternalApiException;
use App\Exceptions\InvalidExternalDataException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Closure;

class RickAndMortyApiClient implements RickAndMortyApiClientInterface
{
    protected string $baseUrl;
    protected ?Closure $progressCallback = null;

    public function __construct()
    {
        $this->baseUrl = config('services.rick_and_morty.base_url', 'https://rickandmortyapi.com/api');
    }

    public function setProgressCallback(?Closure $callback): void
    {
        $this->progressCallback = $callback;
    }

    private function requestPage(string $resource, int $page): Response
    {
        $attempts = min(3, max(1, (int) config('services.rick_and_morty.retry_times', 3)));
        $delaySeconds = (int) ceil(max(0, (int) config('services.rick_and_morty.retry_delay', 10000)) / 1000);
        $lastException = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $this->writeProgress(
                'attempt',
                compact('resource', 'page', 'attempt', 'attempts')
            );

            try {
                $response = Http::timeout(5)
                    ->get("{$this->baseUrl}/{$resource}", ['page' => $page]);

                if (!$response->failed()) {
                    $this->writeProgress(
                        'success',
                        [
                            'resource' => $resource,
                            'page' => $page,
                            'attempt' => $attempt,
                            'totalPages' => $response->json('info.pages'),
                            'status' => $response->status(),
                        ]
                    );

                    return $response;
                }

                $errorMessage = $this->describeResponseError($response);
                $lastException = new ExternalApiException($errorMessage);
            } catch (\Exception $e) {
                $errorMessage = $e instanceof ConnectionException
                    ? 'Error de conexión o timeout con la API externa.'
                    : "Error inesperado al consultar la API externa: {$e->getMessage()}";

                $lastException = new ExternalApiException(
                    $errorMessage,
                    0,
                    $e
                );
            }

            $this->writeProgress(
                'error',
                compact('resource', 'page', 'attempt', 'errorMessage')
            );

            if ($attempt < $attempts) {
                $this->countdownBeforeRetry($delaySeconds);
            } else {
                $this->writeProgress(
                    'exhausted',
                    compact('resource', 'page', 'attempts')
                );
            }
        }

        throw $lastException;
    }

    private function describeResponseError(Response $response): string
    {
        $status = $response->status();

        return match (true) {
            $status === 429 => 'External API has limited requests (HTTP 429).',
            $status >= 500 =>"External API is not available (HTTP {$status}).",
            $status >= 400 => "External API rejected the request (HTTP {$status}).",
            default => "External API returned unexpected HTTP error (HTTP {$status}).",
        };
    }

    private function writeProgress(string $event, array $data = []): void
    {
        if ($this->progressCallback !== null) {
            ($this->progressCallback)(array_merge(['event' => $event], $data));
        }
    }

    private function countdownBeforeRetry(int $delaySeconds): void
    {
        $totalSeconds = $delaySeconds;
        $this->writeProgress('retry_wait_start', compact('totalSeconds'));

        while ($delaySeconds > 0) {
            sleep(1);
            $delaySeconds--;
            $this->writeProgress('retry_wait_tick', ['remainingSeconds' => $delaySeconds]);
        }
    }

    public function getCharacters(int $page = 1): array
    {
        try {
            $response = $this->requestPage('character', $page);

            $data = $response->json();

            // Strict validation of the received schema
            if (!isset($data['results']) || !is_array($data['results'])) {
                throw new InvalidExternalDataException("Invalid response structure from Rick and Morty API.");
            }

            $dtos = array_map(fn(array $item) => CharacterDTO::fromArray($item), $data['results']);

            return [
                'has_next' => isset($data['info']['next']) && $data['info']['next'] !== null,
                'total_pages' => $data['info']['pages'] ?? null,
                'data' => $dtos
            ];
        } catch (\Exception $e) {
            if ($e instanceof ExternalApiException || $e instanceof InvalidExternalDataException) {
                throw $e;
            }
            throw new ExternalApiException("Connection exception with remote service: " . $e->getMessage(), 0, $e);
        }
    }
    
    public function getLocations(int $page = 1): array
    {
        try {
            $response = $this->requestPage('location', $page);

            $data = $response->json();

            if (!isset($data['results']) || !is_array($data['results'])) {
                throw new InvalidExternalDataException("Invalid response structure from Rick and Morty API.");
            }

            $dtos = array_map(fn(array $item) => LocationDTO::fromArray($item), $data['results']);

            return [
                'has_next' => isset($data['info']['next']) && $data['info']['next'] !== null,
                'total_pages' => $data['info']['pages'] ?? null,
                'data' => $dtos
            ];
        } catch (\Exception $e) {
            if ($e instanceof ExternalApiException || $e instanceof InvalidExternalDataException) {
                throw $e;
            }
            throw new ExternalApiException("Connection exception with remote service: " . $e->getMessage(), 0, $e);
        }
    }

    public function getEpisodes(int $page = 1): array
    {
        try {
            $response = $this->requestPage('episode', $page);

            $data = $response->json();

            if (!isset($data['results']) || !is_array($data['results'])) {
                throw new InvalidExternalDataException("Invalid response structure from Rick and Morty API.");
            }

            $dtos = array_map(fn(array $item) => EpisodeDTO::fromArray($item), $data['results']);

            return [
                'has_next' => isset($data['info']['next']) && $data['info']['next'] !== null,
                'total_pages' => $data['info']['pages'] ?? null,
                'data' => $dtos
            ];
        } catch (\Exception $e) {
            if ($e instanceof ExternalApiException || $e instanceof InvalidExternalDataException) {
                throw $e;
            }
            throw new ExternalApiException("Connection exception with remote service: " . $e->getMessage(), 0, $e);
        }
    }
}