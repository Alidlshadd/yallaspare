<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * The endpoint a load balancer or uptime monitor polls.
 *
 * It answers two questions and no others: is PHP serving, and can the
 * application still reach its database. Anything richer — versions, queue
 * depth, disk figures — is an unauthenticated leak about the inside of the
 * server, so the body stays at one word either way.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::connection()->getPdo()->query('SELECT 1');
        } catch (Throwable) {
            // 503, not 500: this is the code a load balancer reads as
            // "take me out of rotation", and it is the honest answer while
            // the database is unreachable.
            return response()->json(['status' => 'down'], 503);
        }

        return response()->json(['status' => 'ok']);
    }
}
