<?php

namespace App\Http\Controllers;

use App\Models\ManualInvoice;
use App\Services\InvoiceRenderer;
use App\Services\Invoices\ManualInvoiceService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The page a customer lands on from the link staff sent them.
 *
 * It answers to the token and nothing else: there is no id in the URL to
 * count up from, no listing, and no way from here to another invoice or to
 * the customer directory. A wrong, revoked or draft token is a plain 404, the
 * same as a page that never existed.
 */
class SharedInvoiceController extends Controller
{
    public function __construct(private readonly ManualInvoiceService $invoices) {}

    public function show(Request $request, string $token, InvoiceRenderer $renderer): Response
    {
        $invoice = $this->resolve($token);
        $locale = $renderer->resolveManualLocale($request->query('lang'));
        app()->setLocale($locale);

        return $this->private(response()->view('invoices.shared', [
            'invoice' => $invoice->load('items'),
            'token' => $token,
            'locale' => $locale,
            'isRtl' => in_array($locale, ['ar', 'ku'], true),
            'service' => $this->invoices,
        ]));
    }

    /**
     * The same invoice laid out like its PDF, to be saved as a picture.
     */
    public function image(Request $request, string $token, InvoiceRenderer $renderer): Response
    {
        $invoice = $this->resolve($token);
        $locale = $renderer->resolveManualLocale($request->query('lang'));
        app()->setLocale($locale);

        return $this->private(response()->view('invoices.image', [
            'invoice' => $invoice->load('items'),
            'currency' => 'IQD',
            'logoUrl' => $renderer->logoUrl(),
            'locale' => $locale,
            'isRtl' => in_array($locale, ['ar', 'ku'], true),
            'auto' => false,
            'links' => [
                'languages' => collect(['en', 'ar', 'ku'])->mapWithKeys(fn (string $code): array => [
                    $code => route('invoices.shared.image', ['token' => $token, 'lang' => $code]),
                ])->all(),
                'pdf' => route('invoices.shared.pdf', ['token' => $token, 'lang' => $locale]),
                'back' => route('invoices.shared.show', ['token' => $token, 'lang' => $locale]),
            ],
        ]));
    }

    public function pdf(Request $request, string $token, InvoiceRenderer $renderer): Response
    {
        $invoice = $this->resolve($token);

        return $this->private($renderer->manualResponse(
            $invoice,
            $renderer->resolveManualLocale($request->query('lang')),
            $request->boolean('inline'),
        ));
    }

    private function resolve(string $token): ManualInvoice
    {
        return $this->invoices->findShared($token) ?? abort(404);
    }

    /**
     * Keep the document out of search results, shared caches and the Referer
     * header of anything the reader clicks next.
     */
    private function private(Response $response): Response
    {
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }
}
