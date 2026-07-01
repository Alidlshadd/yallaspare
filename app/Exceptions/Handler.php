<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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

        $this->renderable(function (PostTooLargeException $e, Request $request): Response {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('The uploaded file is too large. Please upload an MP4 video up to 50MB.'),
                ], 413);
            }

            return back()
                ->withInput()
                ->withErrors([
                    'storefront_hero_video' => __('The uploaded file is too large. Please upload an MP4 video up to 50MB.'),
                ]);
        });

        // Scoped 404 logging: `routeIs()` is unreliable when routing itself
        // failed, so we match against the request path prefixes instead.
        $this->renderable(function (NotFoundHttpException $e, Request $request): void {
            if ($request->is('account/*', 'user/*', 'admin/*')) {
                $this->logSecurityEvent($request, 'authz.not_found', 'notice');
            }
        });

        $this->renderable(function (AuthorizationException $e, Request $request): void {
            if ($request->user()) {
                $this->logSecurityEvent($request, 'authz.forbidden', 'notice');
            }
        });

        $this->renderable(function (ThrottleRequestsException $e, Request $request): void {
            $this->logSecurityEvent($request, 'throttle.exceeded', 'warning');
        });

        $this->renderable(function (HttpException $e, Request $request): void {
            if ($e instanceof NotFoundHttpException || $e instanceof ThrottleRequestsException) {
                return;
            }
            if ($e->getStatusCode() === 403 && $request->user()) {
                $this->logSecurityEvent($request, 'authz.forbidden', 'notice');
            }
        });
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function logSecurityEvent(Request $request, string $event, string $level, array $extra = []): void
    {
        Log::channel('security')->{$level}('security event', array_merge([
            'event' => $event,
            'route' => $request->route()?->getName() ?? $request->path(),
            'user_id' => $request->user()?->getAuthIdentifier(),
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ], $extra));
    }
}
