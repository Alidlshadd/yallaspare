<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
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
    }
}
