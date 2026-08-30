<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Inventory\BulkStockAdjustment;
use App\Services\Inventory\BulkStockParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

/**
 * Raising and lowering the stock of many products at once.
 *
 * Always two requests: one that reads the list and shows what it would do, one
 * that applies it. The reviewed list is held in the session between them and
 * never travels through the browser, so what gets applied is what was shown
 * and not what came back.
 */
class BulkStockAdjustmentController extends Controller
{
    private const SESSION_KEY = 'bulk_stock.preview';

    public function __construct(
        private readonly BulkStockParser $parser,
        private readonly BulkStockAdjustment $adjustment,
    ) {}

    public function create(): View
    {
        return view('admin.inventory.bulk-stock', [
            'maxRows' => BulkStockParser::MAX_ROWS,
            'preview' => null,
            'parseErrors' => [],
            'reason' => '',
        ]);
    }

    public function preview(Request $request): View|RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:200'],
            'rows' => ['nullable', 'string', 'max:200000'],
            'file' => ['nullable', 'file', 'max:5120', 'mimes:csv,txt,xlsx,xls'],
        ]);

        $reason = trim((string) $data['reason']);
        $pasted = trim((string) ($data['rows'] ?? ''));

        if ($request->hasFile('file')) {
            $parsed = $this->parser->fromFile($request->file('file'));
        } elseif ($pasted !== '') {
            $parsed = $this->parser->fromText($pasted);
        } else {
            return back()
                ->withInput()
                ->withErrors(['rows' => __('Paste some rows or choose a file.')]);
        }

        $preview = $this->adjustment->preview($parsed['rows']);

        // Parser errors block just as firmly as unresolvable products: a line
        // nobody could read is a line whose stock change is unknown.
        $applicable = $preview['applicable'] && $parsed['errors'] === [];

        $request->session()->put(self::SESSION_KEY, [
            'token' => (string) Str::uuid(),
            'reason' => $reason,
            'rows' => $preview['rows'],
            'applicable' => $applicable,
        ]);

        return view('admin.inventory.bulk-stock', [
            'maxRows' => BulkStockParser::MAX_ROWS,
            'preview' => $preview + ['applicable' => $applicable],
            'parseErrors' => $parsed['errors'],
            'reason' => $reason,
            'token' => $request->session()->get(self::SESSION_KEY)['token'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['token' => ['required', 'string']]);

        $held = $request->session()->get(self::SESSION_KEY);

        if (! is_array($held) || ($held['token'] ?? null) !== $request->input('token')) {
            return redirect()
                ->route('admin.inventory.bulk-stock')
                ->with('error', __('That review has expired. Paste the list again.'));
        }

        if (($held['applicable'] ?? false) !== true) {
            return back()->with('error', __('This run still has rows that cannot be applied.'));
        }

        try {
            $result = $this->adjustment->apply(
                (array) $held['rows'],
                (string) $held['reason'],
                $request->user(),
            );
        } catch (RuntimeException $exception) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()
                ->route('admin.inventory.bulk-stock')
                ->with('error', $exception->getMessage());
        }

        $request->session()->forget(self::SESSION_KEY);

        return redirect()
            ->route('admin.inventory.index', ['search' => $result['reference']])
            ->with('success', __(':count product(s) adjusted. This run is recorded as :reference.', [
                'count' => $result['applied'],
                'reference' => $result['reference'],
            ]));
    }
}
