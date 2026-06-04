<?php

namespace App\Services\Reviews;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;

class ProductReviewEligibilityService
{
    public function canReview(User $user, Product $product): bool
    {
        return $user->orders()
            ->where('status', Order::STATUS_DELIVERED)
            ->where('payment_status', Order::PAYMENT_PAID)
            ->whereHas('items', fn ($query) => $query->where('product_id', $product->id))
            ->exists();
    }

    public function hasReviewed(User $user, Product $product): bool
    {
        return ProductReview::query()
            ->where('product_id', $product->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    public function rejectionReason(User $user, Product $product): ?string
    {
        if (! $this->canReview($user, $product)) {
            return __('You can review this product after a paid delivered order.');
        }

        if ($this->hasReviewed($user, $product)) {
            return __('You have already reviewed this product.');
        }

        return null;
    }
}
