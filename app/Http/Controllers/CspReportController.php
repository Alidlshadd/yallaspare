<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class CspReportController extends Controller
{
    /**
     * Ingest a CSP violation report and write a whitelisted subset to the
     * security channel. script-sample / original-policy / disposition are
     * intentionally excluded — under a reflected XSS, the sample would carry
     * attacker-controlled content, so persisting it creates a log-injection
     * and data-leak vector.
     */
    public function store(Request $request): Response
    {
        foreach ($this->extractReports($request) as $report) {
            Log::channel('security')->notice('csp.report', [
                'event' => 'csp.report',
                'document_uri' => $report['document-uri'] ?? null,
                'violated_directive' => $report['violated-directive'] ?? null,
                'effective_directive' => $report['effective-directive'] ?? null,
                'blocked_uri' => $report['blocked-uri'] ?? null,
                'line_number' => isset($report['line-number']) ? (int) $report['line-number'] : null,
                'source_file' => $report['source-file'] ?? null,
                'route' => $request->route()?->getName() ?? $request->path(),
                'user_id' => $request->user()?->getAuthIdentifier(),
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]);
        }

        return response()->noContent();
    }

    /**
     * Accept both the legacy report-uri body ({"csp-report": {...}}) and the
     * modern Reporting-API body ([{"type":"csp-violation","body":{...}}]).
     *
     * @return list<array<string, mixed>>
     */
    private function extractReports(Request $request): array
    {
        $payload = $request->json()->all();
        if (! is_array($payload)) {
            return [];
        }

        if (isset($payload['csp-report']) && is_array($payload['csp-report'])) {
            return [$payload['csp-report']];
        }

        $reports = [];
        foreach ($payload as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            if (($entry['type'] ?? null) === 'csp-violation' && is_array($entry['body'] ?? null)) {
                $reports[] = $this->normalizeReportingApiBody($entry['body']);
            } elseif (isset($entry['document-uri']) || isset($entry['violated-directive'])) {
                $reports[] = $entry;
            }
        }

        return $reports;
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function normalizeReportingApiBody(array $body): array
    {
        return [
            'document-uri' => $body['documentURL'] ?? null,
            'violated-directive' => $body['effectiveDirective'] ?? null,
            'effective-directive' => $body['effectiveDirective'] ?? null,
            'blocked-uri' => $body['blockedURL'] ?? null,
            'line-number' => $body['lineNumber'] ?? null,
            'source-file' => $body['sourceFile'] ?? null,
        ];
    }
}
