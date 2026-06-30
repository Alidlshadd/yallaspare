<?php

namespace App\Http\Middleware;

use App\Services\Analytics\AnalyticsRecorder;
use App\Support\BotDetector;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordAnalyticsEvent
{
    private const ASSET_EXTENSIONS = [
        'js','css','map','png','jpg','jpeg','gif','svg','webp','ico',
        'woff','woff2','ttf','eot','otf','mp4','webm','mp3','pdf','txt','xml','json',
    ];

    public function __construct(private readonly AnalyticsRecorder $recorder) {}

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if ($request->method() !== 'GET') {
            return;
        }
        if ($response->getStatusCode() >= 400) {
            return;
        }
        if (BotDetector::isBot($request->userAgent())) {
            return;
        }
        if ($this->isAsset($request->path())) {
            return;
        }

        $this->recorder->record('page_view', AnalyticsRecorder::visitorPayloadFor($request));
    }

    private function isAsset(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return $ext !== '' && in_array($ext, self::ASSET_EXTENSIONS, true);
    }
}
