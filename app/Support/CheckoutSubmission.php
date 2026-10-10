<?php

namespace App\Support;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * The one-order-per-form rule for buy-now.
 *
 * The cart cannot be ordered twice, because the first order empties it.
 * Buy-now has nothing to run out, so the same form posted twice — a double
 * click, a resubmitted page, two tabs racing — used to place two orders and
 * take the stock twice.
 *
 * Each showing of the form carries a token the server issued for that
 * customer and that product, signed so that no other string is accepted in
 * its place. Placing an order claims the token atomically (Cache::add is a
 * single insert on the database store, so of any number of simultaneous
 * copies exactly one wins). What was ordered is remembered with the claim: a
 * copy of the same form is led to the order already placed, while the same
 * token arriving with a different quantity, address or payment method is not
 * a copy at all and is refused.
 */
class CheckoutSubmission
{
    /** How long a used form is remembered, so its copies can find the order. */
    private const MEMORY_MINUTES = 60;

    public const CLAIMED = 'claimed';

    public const DUPLICATE = 'duplicate';

    public const MISMATCH = 'mismatch';

    public static function issue(User $user, Product $product): string
    {
        $nonce = Str::random(32);

        return $nonce.'.'.self::sign($nonce, $user, $product);
    }

    /**
     * Whether this token is one the server issued for this customer and this
     * product. A token from another account or another product fails here.
     */
    public static function isValid(?string $token, User $user, Product $product): bool
    {
        if (! is_string($token) || preg_match('/^([A-Za-z0-9]{32})\.([a-f0-9]{64})$/', $token, $parts) !== 1) {
            return false;
        }

        return hash_equals(self::sign($parts[1], $user, $product), $parts[2]);
    }

    /**
     * Try to take the token for an order with these contents.
     *
     * @param  array<string, scalar|null>  $contents
     * @return array{0: string, 1: ?int} the outcome and, for a duplicate
     *                                   whose first copy has finished, the id of the order it placed
     */
    public static function claim(string $token, User $user, array $contents): array
    {
        $key = self::key($token, $user);
        $fingerprint = self::fingerprint($contents);

        if (Cache::add($key, ['fingerprint' => $fingerprint, 'order_id' => null], now()->addMinutes(self::MEMORY_MINUTES))) {
            return [self::CLAIMED, null];
        }

        $held = Cache::get($key);

        if (! is_array($held) || ! hash_equals((string) ($held['fingerprint'] ?? ''), $fingerprint)) {
            return [self::MISMATCH, null];
        }

        return [self::DUPLICATE, isset($held['order_id']) ? (int) $held['order_id'] : null];
    }

    /**
     * Record the order a claimed token placed.
     *
     * @param  array<string, scalar|null>  $contents
     */
    public static function complete(string $token, User $user, array $contents, int $orderId): void
    {
        Cache::put(self::key($token, $user), [
            'fingerprint' => self::fingerprint($contents),
            'order_id' => $orderId,
        ], now()->addMinutes(self::MEMORY_MINUTES));
    }

    /**
     * Nothing was placed: give the token back so the form can be sent again.
     */
    public static function release(string $token, User $user): void
    {
        Cache::forget(self::key($token, $user));
    }

    private static function key(string $token, User $user): string
    {
        return 'buy-now-submission:'.$user->id.':'.hash('sha256', $token);
    }

    /**
     * @param  array<string, scalar|null>  $contents
     */
    private static function fingerprint(array $contents): string
    {
        ksort($contents);

        return hash('sha256', json_encode(array_map(fn ($value) => (string) $value, $contents)));
    }

    private static function sign(string $nonce, User $user, Product $product): string
    {
        return hash_hmac('sha256', 'buy-now|'.$nonce.'|'.$user->id.'|'.$product->id, (string) config('app.key'));
    }
}
