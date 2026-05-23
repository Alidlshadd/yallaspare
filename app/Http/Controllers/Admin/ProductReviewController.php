<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use App\Support\SqlSafe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductReviewController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $rating = (int) $request->query('rating', 0);

        $baseQuery = ProductReview::query()
            ->with([
                'product:id,name_en,name_ar,name_ku,sku,slug,image',
                'user:id,name,email',
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    SqlSafe::whereLike($nested, 'title', $search);
                    SqlSafe::orWhereLike($nested, 'comment', $search);
                    $nested->orWhereHas('product', function ($productQuery) use ($search): void {
                        SqlSafe::whereLike($productQuery, 'name_en', $search);
                        SqlSafe::orWhereLike($productQuery, 'sku', $search);
                    });
                    $nested->orWhereHas('user', function ($userQuery) use ($search): void {
                        SqlSafe::whereLike($userQuery, 'name', $search);
                        SqlSafe::orWhereLike($userQuery, 'email', $search);
                    });
                });
            });

        $statsQuery = clone $baseQuery;
        $ratingCounts = (clone $statsQuery)
            ->selectRaw('rating, COUNT(*) as aggregate')
            ->groupBy('rating')
            ->pluck('aggregate', 'rating')
            ->map(fn ($count) => (int) $count);

        $stats = [
            'total' => (int) (clone $statsQuery)->count(),
            'average' => (float) (clone $statsQuery)->avg('rating'),
            'five_star' => (int) ($ratingCounts->get(5, 0) ?? 0),
            'low_rating' => (int) (clone $statsQuery)->where('rating', '<=', 2)->count(),
        ];

        $reviews = $baseQuery
            ->when($rating >= 1 && $rating <= 5, fn ($query) => $query->where('rating', $rating))
            ->latest('reviewed_at')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('admin.reviews.index', [
            'reviews' => $reviews,
            'stats' => $stats,
            'ratingCounts' => $ratingCounts,
            'search' => $search,
            'rating' => $rating,
        ]);
    }

    public function destroy(ProductReview $review): RedirectResponse
    {
        $review->delete();

        return back()->with('success', __('Review deleted successfully.'));
    }
}
