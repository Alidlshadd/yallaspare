<?php

namespace App\Services\Analytics;

use App\Support\BotDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class SearchTracker
{
    public function __construct(private readonly AnalyticsRecorder $recorder) {}

    public function record(Request $request, string $rawKeyword, int $resultsCount): void
    {
        if (BotDetector::isBot($request->userAgent())) {
            return;
        }

        try {
            $this->recorder->recordSearch($rawKeyword, $resultsCount);
        } catch (Throwable $e) {
            Log::warning('analytics.search_failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
