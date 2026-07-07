<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MyThrottleMiddleware extends ThrottleRequests
{
    protected $maxAttempts = 100000; // Jumlah maksimum percobaan
    protected $decayMinutes = 1; // Waktu penundaan dalam menit
}
