<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function index(Request $request): View
    {
        $productsQuery = Product::query()
            ->with('category')
            ->where('is_active', 1);

        $search = trim((string) $request->string('q'));
        if ($search !== '') {
            $productsQuery->where(function ($query) use ($search): void {
                $query->where('name_en', 'like', '%' . $search . '%')
                    ->orWhere('brand', 'like', '%' . $search . '%')
                    ->orWhere('sku', 'like', '%' . $search . '%');
            });
        }

        $categoryId = $request->integer('category');
        if ($categoryId > 0) {
            $productsQuery->where('category_id', $categoryId);
        }

        $sort = (string) $request->input('sort', 'latest');
        match ($sort) {
            'price_asc' => $productsQuery->orderBy('price'),
            'price_desc' => $productsQuery->orderByDesc('price'),
            'stock_desc' => $productsQuery->orderByDesc('stock_quantity'),
            default => $productsQuery->latest(),
        };

        $cartCount = 0;
        if (auth()->check()) {
            $cartCount = (int) CartItem::query()
                ->whereHas('cart', fn ($query) => $query->where('user_id', auth()->id()))
                ->sum('quantity');
        }

        return view('shop.index', [
            'products' => $productsQuery->paginate(12)->withQueryString(),
            'categories' => Category::query()->orderBy('name_en')->get(),
            'currencySymbol' => (string) Setting::getValue('currency_symbol', 'IQD'),
            'cartCount' => $cartCount,
            'activeCategory' => $categoryId,
            'search' => $search,
            'sort' => $sort,
        ]);
    }
}
