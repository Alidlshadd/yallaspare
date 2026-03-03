<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LowStockService;
use Illuminate\Http\JsonResponse;

class LowStockController extends Controller
{
    public function count(LowStockService $lowStockService): JsonResponse
    {
        return response()->json([
            'count' => $lowStockService->getLowStockCount(),
        ]);
    }
}
