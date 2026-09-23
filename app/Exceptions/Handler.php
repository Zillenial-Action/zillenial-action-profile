<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Session\TokenMismatchException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (AuthenticationException $exception, Request $request) {
            if ($request->expectsJson() && $this->isCustomerApi($request)) {
                return response()->json([
                    'message' => 'Sesi customer tidak ditemukan.',
                    'code' => 'UNAUTHENTICATED',
                    'errors' => [],
                ], 401);
            }
        });

        $this->renderable(function (TokenMismatchException $exception, Request $request) {
            if ($request->expectsJson() && $this->isCustomerApi($request)) {
                return response()->json([
                    'message' => 'Token CSRF tidak valid atau sudah kedaluwarsa.',
                    'code' => 'CSRF_TOKEN_MISMATCH',
                    'errors' => [],
                ], 419);
            }
        });

        $this->renderable(function (ThrottleRequestsException $exception, Request $request) {
            if ($request->expectsJson() && $this->isCustomerApi($request)) {
                return response()->json([
                    'message' => 'Terlalu banyak percobaan. Coba lagi nanti.',
                    'code' => 'RATE_LIMITED',
                    'errors' => [],
                ], 429);
            }
        });

        $this->renderable(function (InvalidSignatureException $exception, Request $request) {
            if ($request->expectsJson() && $this->isCustomerApi($request)) {
                return response()->json([
                    'message' => 'Tautan verifikasi tidak valid atau sudah kedaluwarsa.',
                    'code' => 'INVALID_SIGNATURE',
                    'errors' => [],
                ], 403);
            }
        });
    }

    private function isCustomerApi(Request $request): bool
    {
        return $request->is('api/auth/*') || $request->is('api/account/*');
    }
}
