<?php

namespace App\Http\Controllers;

use App\Mail\SupportContactRequestMail;
use App\Rules\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class LegalController extends Controller
{
    public function privacy(): View
    {
        return view('legal.privacy');
    }

    public function terms(): View
    {
        return view('legal.terms');
    }

    public function support(): View
    {
        return view('legal.support');
    }

    public function about(): View
    {
        return view('legal.about');
    }

    public function vision(): View
    {
        // Showcase numbers for the vision page stats band. Kept here (not in
        // the view) so they are easy to update as the platform grows.
        $stats = [
            ['value' => 12400, 'suffix' => '+', 'label' => __('Parts listed')],
            ['value' => 18, 'suffix' => '', 'label' => __('Cities served')],
            ['value' => 9600, 'suffix' => '+', 'label' => __('Orders delivered')],
            ['value' => 96, 'suffix' => '%', 'label' => __('Fit-match accuracy')],
        ];

        return view('legal.vision', ['stats' => $stats]);
    }

    public function contact(): View
    {
        return view('legal.contact');
    }

    public function sendContact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40', new PhoneNumber()],
            'topic' => ['required', 'string', 'max:40'],
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        try {
            // Queue support requests so public contact form submissions are not
            // blocked by temporary Google Workspace SMTP delays or outages.
            Mail::to((string) config('mail.support.address'))
                ->queue(new SupportContactRequestMail($data));
        } catch (\Throwable $exception) {
            Log::error('Support contact email failed', [
                'email' => $data['email'],
                'topic' => $data['topic'],
                'error' => $exception->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', __('We could not send your message right now. Please email support@yallaspare.com directly.'));
        }

        return back()->with('success', __('Your message has been sent to YallaSpare support.'));
    }

    public function returnExchange(): View
    {
        return view('legal.return-exchange');
    }

    public function shippingDelivery(): View
    {
        return view('legal.shipping-delivery');
    }

    public function distanceSalesAgreement(): View
    {
        return view('legal.distance-sales-agreement');
    }
}
