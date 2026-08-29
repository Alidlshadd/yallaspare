<?php

namespace App\Services\Shipping;

use App\Models\Governorate;
use App\Models\Setting;
use App\Models\UserAddress;
use Illuminate\Database\Eloquent\Collection;

/**
 * The one place that answers what shipping costs.
 *
 * Every checkout surface — cart review, buy now, the mobile API, the order
 * itself — asks here, so a governorate's fee cannot be charged on one screen
 * and quoted differently on the next.
 */
class ShippingFeeResolver
{
    public function forAddress(?UserAddress $address): ShippingQuote
    {
        return $this->forGovernorate($address?->governorate);
    }

    public function forGovernorate(?Governorate $governorate): ShippingQuote
    {
        if ($governorate === null) {
            return new ShippingQuote($this->flatFee(), null, null);
        }

        return new ShippingQuote(
            max(0.0, round((float) $governorate->shipping_fee, 2)),
            max(1, (int) $governorate->delivery_days),
            $governorate,
        );
    }

    /**
     * Quotes for a list of saved addresses, keyed by address id, in one query
     * rather than one per card on the delivery screen.
     *
     * @param  Collection<int, UserAddress>  $addresses
     * @return array<int, ShippingQuote>
     */
    public function forAddresses(Collection $addresses): array
    {
        $addresses->loadMissing('governorate');

        return $addresses
            ->mapWithKeys(fn (UserAddress $address): array => [
                (int) $address->id => $this->forAddress($address),
            ])
            ->all();
    }

    /**
     * The rate for a destination with no governorate on it — addresses saved
     * before the shipping map existed, and any operator who never filled it in.
     */
    public function flatFee(): float
    {
        return max(0, round((float) Setting::getValue('shipping_fee', 5000), 2));
    }
}
