<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    // Global middleware nga moagi sa tanang incoming HTTP requests.
    protected $middleware = [
        // Global middleware
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\Pr9yMnTm4NSzvG9rrwjM2ec8xZgh1cafXH8::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,

        // Custom middleware para pugngan ang browser sa paggamit sa cached pages.
        'nocache' => \App\Http\Middleware\NoCacheMiddleware::class,
    ];

    // Mga middleware group nga magamit sa pag-organize sa request processing.
    protected $middlewareGroups = [
        // Middleware nga gamiton sa web routes.
        'web' => [
            // I-encrypt ang cookies para maprotektahan ang stored data.
            \App\Http\Middleware\EncryptCookies::class,

            // Idugang ang queued cookies sa HTTP response.
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,

            // Sugdan ug i-manage ang user session.
            \Illuminate\Session\Middleware\StartSession::class,

            // I-share ang validation errors gikan sa session ngadto sa views.
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,

            // Proteksyon batok sa Cross-Site Request Forgery (CSRF) attacks.
            \App\Http\Middleware\VerifyCsrfToken::class,

            // I-resolve ang route model bindings.
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        // Middleware nga gamiton sa API routes.
        'api' => [
            // Limitahan ang gidaghanon sa API requests base sa API throttle configuration.
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',

            // I-handle ang stateful authentication para sa Sanctum frontend requests.
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,

            // I-apply ang API request rate limit.
            'throttle:api',

            // I-resolve ang route model bindings sa API requests.
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    // Mga middleware nga mahimong tawagon gamit ang ilang alias sa routes.
    protected $routeMiddleware = [
        // Authentication middleware para sa protected routes.
        'auth' => \App\Http\Middleware\Authenticate::class,

        // Basic HTTP authentication middleware.
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,

        // I-redirect ang authenticated users palayo sa guest-only routes.
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,

        // Rate limiting middleware para limitahan ang requests.
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,

        // I-check kung verified na ang email address sa user.
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,

        // Custom middleware para sa admin authentication ug access control.
        'AdminAuth' => \App\Http\Middleware\AdminAuth::class,
    ];
}

