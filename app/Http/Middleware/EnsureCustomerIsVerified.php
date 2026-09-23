<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $customer = $request->user('customer');

        if (! $customer || ! $customer->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Verifikasi email diperlukan untuk mengakses akun.',
                'code' => 'EMAIL_NOT_VERIFIED',
                'errors' => [],
            ], 403);
        }

        return $next($request);
    }
}
