<?php

namespace App\Http\Controllers;

use App\Models\Governorate;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserAddress;
use App\Rules\IraqiMobileNumber;
use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutService;
use App\Services\Payments\PaymentService;
use App\Services\PhoneVerificationService;
use App\Support\UserCommunication;
use App\Support\VerificationRateLimit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Checkout for someone who does not have an account and should not have to
 * think about getting one.
 *
 * They fill in one form, a code arrives on their phone, and the order is
 * placed. The account it lands in is made for them along the way, which is
 * the part worth explaining.
 *
 * OTPiQ will only send a code to a number that already belongs to a saved
 * account (see OtpiqSmsService::sendVerification) — a deliberate guard
 * against turning the endpoint into an SMS cannon aimed at any number a form
 * cares to name. Creating the account first keeps that guard intact. And
 * because a confirmed phone is a confirmed account (EnsureAccountIsVerified),
 * the person is genuinely signed in by the time the order is written: no
 * nullable orders.user_id, no signed guest-tracking links, no claiming an
 * order afterwards. The order simply belongs to them.
 *
 * What they still lack is a way back in — no email, no password. Both stay
 * NULL until they choose them, and `password IS NULL` is what tells a
 * returning visitor on the same number apart from someone who has since set
 * a password and belongs on the sign-in page.
 */
class ExpressCheckoutController extends Controller
{
    public const PENDING_SESSION_KEY = 'checkout.express.pending';

    public const LAST_SENT_SESSION_KEY = 'checkout.express.last_sent_at';

    public function __construct(
        private readonly CartService $carts,
        private readonly CheckoutService $checkoutService,
        private readonly PhoneVerificationService $phoneVerification,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        $cart = $this->carts->current();
        $items = $cart?->items()->with('product')->get();

        if ($items === null || $items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', __('Cart is empty.'));
        }

        $subtotal = (float) $items->sum(
            fn ($item): float => $item->product ? (float) $item->product->priceFor(null) * (int) $item->quantity : 0.0
        );

        return view('shop.checkout-express', [
            'items' => $items,
            'subtotal' => round($subtotal, 2),
            'currencySymbol' => (string) Setting::getValue('currency_code', 'IQD'),
            'governorates' => Governorate::query()->ordered()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $cart = $this->carts->current();

        if (! $cart || $cart->items()->count() === 0) {
            return redirect()->route('cart.index')->with('error', __('Cart is empty.'));
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'country_code' => ['required', Rule::in(['+964'])],
            'phone' => ['required', 'string', 'max:30', new IraqiMobileNumber],
            'governorate_id' => ['required', 'integer', Rule::exists('governorates', 'id')],
            'city' => ['required', 'string', 'max:120'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $account = $this->accountFor((string) $data['phone'], (string) $data['name']);

        if ($account === null) {
            // The number is spoken for by an account someone can actually
            // sign into. Sending a code here would hand its order history to
            // whoever asked, so this is the one path back to the login screen.
            return redirect()
                ->route('login')
                ->with('status', __('You already have an account with this phone number. Sign in and your cart will be waiting.'));
        }

        if (! $this->phoneVerification->sendCode($account)) {
            return back()
                ->withInput()
                ->withErrors(['phone' => __('user.phone_verification_send_failed')]);
        }

        $request->session()->put(self::PENDING_SESSION_KEY, [
            'user_id' => (int) $account->getKey(),
            'address' => [
                'country' => __('Iraq'),
                'governorate_id' => (int) $data['governorate_id'],
                'city' => (string) $data['city'],
                'address_line1' => (string) $data['address_line1'],
                'address_line2' => $data['address_line2'] ?? null,
            ],
            'notes' => (string) ($data['notes'] ?? ''),
        ]);
        $request->session()->put(self::LAST_SENT_SESSION_KEY, now()->timestamp);

        return redirect()->route('checkout.express.verify');
    }

    public function verifyForm(Request $request): View|RedirectResponse
    {
        $account = $this->pendingAccount($request);

        if (! $account) {
            return redirect()->route('checkout.express');
        }

        return view('shop.checkout-express-verify', [
            'maskedPhone' => PhoneVerificationService::displayPhone((string) $account->phone_normalized),
            'expiresInMinutes' => $this->phoneVerification->expiresInMinutes(),
            'resendCooldownSeconds' => $this->resendCooldownSeconds($request),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $account = $this->pendingAccount($request);

        if (! $account) {
            return redirect()->route('checkout.express');
        }

        $cart = $this->carts->current();

        if (! $cart || $cart->items()->count() === 0) {
            return redirect()->route('cart.index')->with('error', __('Cart is empty.'));
        }

        // Checked every time, even when this account confirmed the same
        // number on an earlier order: the code is what proves the person at
        // the form is the person holding the phone, and waving it through for
        // a returning number would let anyone order in their name.
        if (! $this->phoneVerification->confirmCode($account, (string) $request->input('code'))) {
            throw ValidationException::withMessages([
                'code' => __('user.phone_verification_invalid'),
            ]);
        }

        $pending = (array) $request->session()->get(self::PENDING_SESSION_KEY, []);

        // Signing in is what carries the guest cart onto the account
        // (MergeGuestCartOnLogin), so the cart is read again afterwards.
        Auth::login($account);
        $request->session()->regenerate();

        $address = new UserAddress(['is_default' => true] + (array) ($pending['address'] ?? []));
        $address->user()->associate($account);
        $address->save();

        $cart = $this->carts->current();
        $cart?->load('items.product');

        if (! $cart || $cart->items->isEmpty()) {
            $this->forgetPending($request);

            return redirect()->route('cart.index')->with('error', __('Cart is empty.'));
        }

        $notes = trim((string) ($pending['notes'] ?? ''));

        try {
            $order = $this->checkoutService->placeCartOrder(
                $cart,
                $account,
                $address,
                $notes !== '' ? $notes : null,
                '',
                PaymentService::METHOD_COD
            );
        } catch (\RuntimeException $exception) {
            $this->forgetPending($request);

            return redirect()->route('cart.index')->with('error', $exception->getMessage());
        }

        $this->forgetPending($request);

        $channels = UserCommunication::sendOrderPlaced($account, $order);
        $message = __('Order placed successfully.');

        if ($channels !== []) {
            $message .= ' '.__('Confirmation sent via :channels.', ['channels' => implode(', ', $channels)]);
        }

        return redirect()->route('checkout.success', $order)->with('success', $message);
    }

    public function resend(Request $request): RedirectResponse
    {
        $account = $this->pendingAccount($request);

        if (! $account) {
            return redirect()->route('checkout.express');
        }

        if ($this->resendCooldownSeconds($request) > 0) {
            return back()->withErrors([
                'code' => __('Please wait a moment before requesting another verification code.'),
            ]);
        }

        if (! $this->phoneVerification->sendCode($account)) {
            return back()->withErrors(['code' => __('user.phone_verification_send_failed')]);
        }

        $request->session()->put(self::LAST_SENT_SESSION_KEY, now()->timestamp);

        return back()->with('status', __('user.phone_verification_sent', [
            'minutes' => $this->phoneVerification->expiresInMinutes(),
        ]));
    }

    /**
     * The account this number may check out with, created here when the
     * number is new. Null means the number belongs to someone who can sign
     * in — or to the admin panel — and must not be verified from this form.
     */
    private function accountFor(string $phone, string $name): ?User
    {
        $candidates = User::phoneLookupCandidates($phone);
        $existing = $candidates === []
            ? null
            : User::query()->whereIn('phone_normalized', $candidates)->first();

        if ($existing) {
            // A password, an email address or a panel role each mean there is
            // a person behind this number with a way back in. Banned accounts
            // are refused here too, and share the wording, so a stranger
            // learns nothing the sign-in page would not already tell them.
            $claimed = $existing->password !== null
                || $existing->email !== null
                || $existing->isAdminPanelUser()
                || $existing->isBanned();

            return $claimed ? null : $existing;
        }

        $user = new User;
        $user->name = $name;
        $user->phone = $phone;
        $user->save();

        return $user;
    }

    private function pendingAccount(Request $request): ?User
    {
        $pending = $request->session()->get(self::PENDING_SESSION_KEY);
        $userId = is_array($pending) ? (int) ($pending['user_id'] ?? 0) : 0;

        if ($userId < 1) {
            return null;
        }

        $account = User::query()->find($userId);

        // The account may have gained credentials, a role or a ban between
        // the two steps. It is no longer ours to sign anyone into.
        if (! $account || $account->password !== null || $account->email !== null
            || $account->isAdminPanelUser() || $account->isBanned()) {
            $this->forgetPending($request);

            return null;
        }

        return $account;
    }

    private function forgetPending(Request $request): void
    {
        $request->session()->forget([self::PENDING_SESSION_KEY, self::LAST_SENT_SESSION_KEY]);
    }

    private function resendCooldownSeconds(Request $request): int
    {
        $lastSentAt = (int) $request->session()->get(self::LAST_SENT_SESSION_KEY, 0);

        return max(
            $lastSentAt > 0 ? max(0, 60 - (now()->timestamp - $lastSentAt)) : 0,
            VerificationRateLimit::remainingSeconds($request),
        );
    }
}
