<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class FavoriteCharacterController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        return $this->successResponse(
            $request->user()->favoriteCharacters()->paginate(15)
        );
    }

    public function store(Request $request, int $characterId): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $user->favoriteCharacters()->syncWithoutDetaching([$characterId]);

        return $this->successResponse(null, 'Personaje añadido a favoritos', 201);
    }

    public function destroy(Request $request, int $characterId): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $user->favoriteCharacters()->detach($characterId);

        return $this->successResponse(null, 'Personaje eliminado de favoritos');
    }
}
