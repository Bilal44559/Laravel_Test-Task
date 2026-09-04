<?php

namespace App\Http\Middleware;

use App\Models\PersonalAccessToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();

        if ($bearer === null || $bearer === '') {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        [$id, $token] = array_pad(explode('|', $bearer, 2), 2, null);

        if ($id === null || $token === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $accessToken = PersonalAccessToken::query()->find($id);

        if ($accessToken === null || ! hash_equals($accessToken->token, hash('sha256', $token))) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($accessToken->expires_at !== null && $accessToken->expires_at->isPast()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user = $accessToken->tokenable;

        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $accessToken->forceFill(['last_used_at' => now()])->save();

        $user->withAccessToken($accessToken);
        auth()->setUser($user);
        $request->setUserResolver(static fn () => $user);

        return $next($request);
    }
}
