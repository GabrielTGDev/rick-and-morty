<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Character;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class CharacterController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = Character::with(['origin', 'currentLocation']);

        if ($request->filled('name')) {
            $query->where('name', 'ilike', "%{$request->name}%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('species')) {
            $query->where('species', $request->species);
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        return $this->successResponse($query->paginate(15));
    }

    /**
     * Summary of show
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id): \Illuminate\Http\JsonResponse
    {
        $character = Character::with(['origin', 'currentLocation', 'episodes'])->find($id);

        if (!$character) {
            return $this->errorResponse('Personaje no encontrado', 404);
        }

        return $this->successResponse($character);
    }
}
