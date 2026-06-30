<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsQueryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsQueryService $analytics) {}

    public function index(Request $request): View
    {
        $days = $this->analytics->normalizeDays((int) $request->query('days', 30));
        $snapshot = $this->analytics->snapshot($days);

        return view('admin.analytics.index', $snapshot);
    }
}
