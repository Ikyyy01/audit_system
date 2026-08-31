<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Batasi akses route hanya untuk role tertentu.
     * Pemakaian di route: ->middleware('role:Manager,Partner')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->role) {
            return response()->json([
                'message' => 'Anda tidak memiliki role yang terdaftar di sistem.',
            ], 403);
        }

        if (! in_array($user->role->name, $roles, true)) {
            return response()->json([
                'message' => 'Aksi ini hanya bisa dilakukan oleh role: ' . implode(', ', $roles) . '.',
            ], 403);
        }

        return $next($request);
    }
}
