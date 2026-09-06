<?php

namespace App\Contracts;

use Closure;

interface RickAndMortyApiClientInterface
{
    public function setProgressCallback(?Closure $callback): void;

    public function getCharacters(int $page = 1): array;
    public function getLocations(int $page = 1): array;
    public function getEpisodes(int $page = 1): array;
}