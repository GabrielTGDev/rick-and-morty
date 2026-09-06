<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWithApiToken
{
    use ApiResponseTrait;

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token || !($user = User::where('api_token', $token)->first())) {
            return $this->errorResponse('Unauthorized. Invalid or not provided token.', 401);
        }

        // Asignar el usuario autenticado a la petición activa
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
