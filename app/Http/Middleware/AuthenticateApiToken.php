<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $sanctumToken = PersonalAccessToken::findToken($token);

        if ($sanctumToken?->tokenable instanceof User) {
            $user = $sanctumToken->tokenable->load('roles');
            $user->withAccessToken($sanctumToken);
            Auth::setUser($user);
            $request->setUserResolver(fn (): User => $user);

            return $next($request);
        }

        $user = User::query()
            ->with('roles')
            ->where('api_token', hash('sha256', $token))
            ->first();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        Auth::setUser($user);
        $request->setUserResolver(fn (): User => $user);

        return $next($request);
    }
}
