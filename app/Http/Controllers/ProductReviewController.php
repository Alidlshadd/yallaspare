<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:120'],
            'comment' => ['nullable', 'string', 'max:1500'],
        ]);

        $hasDeliveredOrder = $request->user()
            ->orders()
            ->where('status', Order::STATUS_DELIVERED)
            ->whereHas('items', fn ($query) => $query->where('product_id', $product->id))
            ->exists();

        if (! $hasDeliveredOrder) {
            return back()->with('review_error', __('You can review this product after a delivered order.'));
        }

        $alreadyReviewed = ProductReview::query()
            ->where('product_id', $product->id)
            ->where('user_id', $request->user()->id)
            ->exists();

        if ($alreadyReviewed) {
            return back()->with('review_error', __('You have already reviewed this product.'));
        }

        ProductReview::query()->create([
            'product_id' => $product->id,
            'user_id' => $request->user()->id,
            'rating' => (int) $data['rating'],
            'title' => trim((string) ($data['title'] ?? '')) ?: null,
            'comment' => trim((string) ($data['comment'] ?? '')) ?: null,
            'is_approved' => true,
            'reviewed_at' => now(),
        ]);

        return back()->with('review_status', __('Thank you. Your review has been saved.'));
    }
}
