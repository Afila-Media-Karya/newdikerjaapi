<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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

        // Kembalikan JSON 429 saat throttle terkena (berlaku untuk semua route, web maupun api)
        $this->renderable(function (ThrottleRequestsException $e, $request) {
            $retryAfter = $e->getHeaders()['Retry-After'] ?? 60;
            return response()->json([
                'success' => false,
                'message' => 'Terlalu banyak permintaan. Silahkan coba lagi dalam ' . $retryAfter . ' detik.',
                'retry_after' => (int) $retryAfter,
            ], 429, $e->getHeaders());
        });
    }
}
