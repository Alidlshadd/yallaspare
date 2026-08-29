<?php

namespace App\Services\Shipping;

use App\Models\Governorate;

/**
 * What delivery costs and how long it takes for one destination.
 *
 * A governorate of null means the destination is not on the shipping map yet,
 * so the flat fee applies and no delivery promise can be made. That is not the
 * same as a governorate whose fee happens to be zero, which is free delivery
 * and a promise the operator made on purpose.
 */
final class ShippingQuote
{
    public function __construct(
        public readonly float $fee,
        public readonly ?int $deliveryDays,
        public readonly ?Governorate $governorate,
    ) {}

    public function isGovernorateRate(): bool
    {
        return $this->governorate !== null;
    }

    public function isFree(): bool
    {
        return $this->fee <= 0.0;
    }

    public function destinationName(): ?string
    {
        return $this->governorate?->localizedName();
    }
}
