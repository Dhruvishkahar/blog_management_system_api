<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */

    Protected function redirectTo(Request $request)
    {
        if (!$request->expectsJson()) {
            return null;
        }
    }

    protected function unauthenticated($request, array $guards)
    {
        abort(response()->json([
            'status' => false,
            'message' => 'Unauthenticated. Token not valid or expired.'
        ], 401));
    }
}
