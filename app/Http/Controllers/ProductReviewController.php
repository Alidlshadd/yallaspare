<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReview;
use App\Services\Reviews\ProductReviewEligibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    public function store(Request $request, Product $product, ProductReviewEligibilityService $eligibility): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:120'],
            'comment' => ['nullable', 'string', 'max:1500'],
        ]);

        $rejectionReason = $eligibility->rejectionReason($request->user(), $product);
        if ($rejectionReason !== null) {
            return back()->with('review_error', $rejectionReason);
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
