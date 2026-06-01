<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\View\Composers\HeaderComposer;
use App\Models\BackInStockSubscription;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Discount;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\RecentlyViewedProduct;
use App\Models\ReturnRequest;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\VehicleBrand;
use App\Mail\SupportContactRequestMail;
use App\Models\Wishlist;
use App\Rules\PhoneNumber;
use App\Services\CheckoutTotals;
use App\Services\CouponService;
use App\Services\InvoiceRenderer;
use App\Support\SqlSafe;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MobileController extends Controller
{
    /**
     * Slug → translation-key map for the seven legal *content* pages.
     * Contact is intentionally excluded — it's a form, not a static page,
     * and is exposed via POST /legal/contact instead.
     */
    private const LEGAL_PAGES = [
        'privacy' => 'Privacy Policy & SSL Security',
        'terms' => 'Terms of Service | Yalla Spare',
        'support' => 'Support | Yalla Spare',
        'about' => 'About Us',
        'return-exchange' => 'Return & Exchange Policy',
        'shipping-delivery' => 'Shipping & Delivery',
        'distance-sales-agreement' => 'Distance Sales Agreement',
    ];

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $login = trim((string) $credentials['email']);
        $user = $this->userForLogin($login);

        if (! $user) {
            $this->debugLoginFailure('user_not_found', $login);

            return response()->json(['message' => __('Email or password is incorrect.')], 422);
        }

        if (! Hash::check((string) $credentials['password'], (string) $user->password)) {
            $this->debugLoginFailure('password_mismatch', $login, $user);

            return response()->json(['message' => __('Email or password is incorrect.')], 422);
        }

        if (! $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => __('Please verify your email address before signing in.'),
                'verification_required' => true,
            ], 403);
        }

        try {
            $token = $user->createToken('mobile')->plainTextToken;
        } catch (\Throwable $e) {
            Log::error('Mobile login token creation failed', [
                'user_id' => $user->id,
                'reason' => $e->getMessage(),
            ]);

            return response()->json(['message' => __('Unable to start mobile session. Please try again.')], 500);
        }

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:20', new PhoneNumber(), User::uniquePhoneRule()],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = new User();
        $user->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => User::ROLE_USER,
        ])->save();

        event(new Registered($user));

        return response()->json([
            'message' => __('Registration complete. Please verify your email address before signing in.'),
            'verification_required' => true,
            'user' => $this->userPayload($user),
        ], 201);
    }

    public function forgotPassword(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink(['email' => $data['email']]);

        return response()->json(['message' => __('If this email exists, we sent a reset link.')]);
    }

    public function me(Request $request)
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => __('Logged out.')]);
    }

    public function refreshToken(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'token' => $request->user()->createToken('mobile')->plainTextToken,
            'user' => $this->userPayload($request->user()),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20', new PhoneNumber(), User::uniquePhoneRule($user->id)],
        ]);

        $user->update($data);

        return response()->json(['user' => $this->userPayload($user->fresh())]);
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], $request->user()->password)) {
            return response()->json(['message' => __('Current password is incorrect.')], 422);
        }

        $request->user()->update(['password' => Hash::make($data['password'])]);

        return response()->json(['message' => __('Password updated.')]);
    }

    public function deleteProfile(Request $request)
    {
        $data = $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! Hash::check($data['password'], (string) $user->password)) {
            return response()->json(['message' => __('Current password is incorrect.')], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => __('Account deleted.')]);
    }

    public function getSettings(Request $request)
    {
        return response()->json(['data' => $this->settingsPayload($request->user())]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'theme_preference' => ['required', Rule::in(['light', 'dark'])],
            'locale_preference' => ['required', Rule::in(['en', 'ar', 'ku'])],
            'notify_order_updates' => ['nullable', 'boolean'],
            'notify_promotions' => ['nullable', 'boolean'],
            'notify_stock_alerts' => ['nullable', 'boolean'],
            'login_alerts' => ['nullable', 'boolean'],
            'session_timeout' => ['required', Rule::in(['15', '30', '60', '120'])],
            'email_notifications' => ['nullable', 'boolean'],
            'sms_notifications' => ['nullable', 'boolean'],
            'whatsapp_notifications' => ['nullable', 'boolean'],
            'marketing_consent' => ['nullable', 'boolean'],
            'currency_preference' => ['required', Rule::in(['USD', 'IQD'])],
            'timezone_preference' => ['required', Rule::in(['Asia/Baghdad', 'UTC'])],
            'date_format_preference' => ['required', Rule::in(['dmy', 'mdy', 'ymd'])],
            'default_contact_method' => ['required', Rule::in(['phone', 'email', 'whatsapp'])],
            'default_delivery_note' => ['nullable', 'string', 'max:255'],
            'express_checkout' => ['nullable', 'boolean'],
            'font_size_preference' => ['required', Rule::in(['default', 'large', 'xl'])],
            'reduced_motion' => ['nullable', 'boolean'],
            'high_contrast_mode' => ['nullable', 'boolean'],
        ]);

        $request->user()->forceFill([
            'theme_preference' => $data['theme_preference'],
            'locale_preference' => $data['locale_preference'],
            'notify_order_updates' => $request->boolean('notify_order_updates'),
            'notify_promotions' => $request->boolean('notify_promotions'),
            'notify_stock_alerts' => $request->boolean('notify_stock_alerts'),
            'two_factor_preference' => (string) ($request->user()->two_factor_preference ?? 'off'),
            'login_alerts' => $request->boolean('login_alerts'),
            'session_timeout' => $data['session_timeout'],
            'email_notifications' => $request->boolean('email_notifications'),
            'sms_notifications' => $request->boolean('sms_notifications'),
            'whatsapp_notifications' => $request->boolean('whatsapp_notifications'),
            'marketing_consent' => $request->boolean('marketing_consent'),
            'currency_preference' => $data['currency_preference'],
            'timezone_preference' => $data['timezone_preference'],
            'date_format_preference' => $data['date_format_preference'],
            'default_contact_method' => $data['default_contact_method'],
            'default_delivery_note' => $data['default_delivery_note'] ?? null,
            'express_checkout' => $request->boolean('express_checkout'),
            'font_size_preference' => $data['font_size_preference'],
            'reduced_motion' => $request->boolean('reduced_motion'),
            'high_contrast_mode' => $request->boolean('high_contrast_mode'),
        ])->save();

        return response()->json(['data' => $this->settingsPayload($request->user()->fresh())]);
    }

    public function updateSettingsAppearance(Request $request)
    {
        $data = $request->validate([
            'theme_preference' => ['required', Rule::in(['light', 'dark'])],
        ]);

        $request->user()->forceFill(['theme_preference' => $data['theme_preference']])->save();

        return response()->json(['data' => $this->settingsPayload($request->user()->fresh())]);
    }

    public function updateSettingsLanguage(Request $request)
    {
        $data = $request->validate([
            'locale_preference' => ['required', Rule::in(['en', 'ar', 'ku'])],
        ]);

        $request->user()->forceFill(['locale_preference' => $data['locale_preference']])->save();

        return response()->json(['data' => $this->settingsPayload($request->user()->fresh())]);
    }

    public function updateSettingsNotifications(Request $request)
    {
        $request->validate([
            'notify_order_updates' => ['sometimes', 'boolean'],
            'notify_promotions' => ['sometimes', 'boolean'],
            'notify_stock_alerts' => ['sometimes', 'boolean'],
        ]);

        $request->user()->forceFill([
            'notify_order_updates' => $request->boolean('notify_order_updates'),
            'notify_promotions' => $request->boolean('notify_promotions'),
            'notify_stock_alerts' => $request->boolean('notify_stock_alerts'),
        ])->save();

        return response()->json(['data' => $this->settingsPayload($request->user()->fresh())]);
    }

    public function updateSettingsSecurity(Request $request)
    {
        $data = $request->validate([
            'login_alerts' => ['sometimes', 'boolean'],
            'session_timeout' => ['required', Rule::in(['15', '30', '60', '120'])],
        ]);

        $request->user()->forceFill([
            'two_factor_preference' => (string) ($request->user()->two_factor_preference ?? 'off'),
            'login_alerts' => $request->boolean('login_alerts'),
            'session_timeout' => $data['session_timeout'],
        ])->save();

        return response()->json(['data' => $this->settingsPayload($request->user()->fresh())]);
    }

    public function updateSettingsCommunication(Request $request)
    {
        $request->validate([
            'email_notifications' => ['sometimes', 'boolean'],
            'sms_notifications' => ['sometimes', 'boolean'],
            'whatsapp_notifications' => ['sometimes', 'boolean'],
            'marketing_consent' => ['sometimes', 'boolean'],
        ]);

        $request->user()->forceFill([
            'email_notifications' => $request->boolean('email_notifications'),
            'sms_notifications' => $request->boolean('sms_notifications'),
            'whatsapp_notifications' => $request->boolean('whatsapp_notifications'),
            'marketing_consent' => $request->boolean('marketing_consent'),
        ])->save();

        return response()->json(['data' => $this->settingsPayload($request->user()->fresh())]);
    }

    public function updateSettingsCheckout(Request $request)
    {
        $data = $request->validate([
            'default_contact_method' => ['required', Rule::in(['phone', 'email', 'whatsapp'])],
            'default_delivery_note' => ['nullable', 'string', 'max:255'],
            'express_checkout' => ['sometimes', 'boolean'],
        ]);

        $request->user()->forceFill([
            'default_contact_method' => $data['default_contact_method'],
            'default_delivery_note' => $data['default_delivery_note'] ?? null,
            'express_checkout' => $request->boolean('express_checkout'),
        ])->save();

        return response()->json(['data' => $this->settingsPayload($request->user()->fresh())]);
    }

    public function updateSettingsAccessibility(Request $request)
    {
        $data = $request->validate([
            'font_size_preference' => ['required', Rule::in(['default', 'large', 'xl'])],
            'reduced_motion' => ['sometimes', 'boolean'],
            'high_contrast_mode' => ['sometimes', 'boolean'],
        ]);

        $request->user()->forceFill([
            'font_size_preference' => $data['font_size_preference'],
            'reduced_motion' => $request->boolean('reduced_motion'),
            'high_contrast_mode' => $request->boolean('high_contrast_mode'),
        ])->save();

        return response()->json(['data' => $this->settingsPayload($request->user()->fresh())]);
    }

    public function legalIndex()
    {
        $pages = [];
        foreach (self::LEGAL_PAGES as $slug => $titleKey) {
            $pages[] = [
                'slug' => $slug,
                'title' => __($titleKey),
            ];
        }

        return response()->json(['data' => $pages]);
    }

    public function legalShow(string $slug)
    {
        $titleKey = self::LEGAL_PAGES[$slug] ?? null;
        abort_if($titleKey === null, 404);

        $viewName = 'legal.' . $slug;
        abort_unless(View::exists($viewName), 404);

        $sections = view($viewName)->renderSections();

        return response()->json([
            'data' => [
                'slug' => $slug,
                'title' => __($titleKey),
                'html' => trim((string) ($sections['content'] ?? '')),
            ],
        ]);
    }

    public function sendContact(Request $request)
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
            Mail::to((string) config('mail.support.address'))
                ->queue(new SupportContactRequestMail($data));
        } catch (\Throwable $exception) {
            Log::error('Mobile support contact email failed', [
                'email' => $data['email'],
                'topic' => $data['topic'],
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => __('We could not send your message right now. Please email support@yallaspare.com directly.'),
            ], 503);
        }

        return response()->json([
            'message' => __('Your message has been sent to YallaSpare support.'),
        ]);
    }

    private function settingsPayload(User $user): array
    {
        return [
            'appearance' => [
                'theme_preference' => (string) ($user->theme_preference ?? 'light'),
            ],
            'language' => [
                'locale_preference' => (string) ($user->locale_preference ?? 'en'),
            ],
            'notifications' => [
                'notify_order_updates' => (bool) ($user->notify_order_updates ?? true),
                'notify_promotions' => (bool) ($user->notify_promotions ?? false),
                'notify_stock_alerts' => (bool) ($user->notify_stock_alerts ?? true),
            ],
            'security' => [
                'login_alerts' => (bool) ($user->login_alerts ?? false),
                'session_timeout' => (string) ($user->session_timeout ?? '30'),
                'two_factor_preference' => (string) ($user->two_factor_preference ?? 'off'),
            ],
            'communication' => [
                'email_notifications' => (bool) ($user->email_notifications ?? true),
                'sms_notifications' => (bool) ($user->sms_notifications ?? false),
                'whatsapp_notifications' => (bool) ($user->whatsapp_notifications ?? false),
                'marketing_consent' => (bool) ($user->marketing_consent ?? false),
            ],
            'checkout' => [
                'default_contact_method' => (string) ($user->default_contact_method ?? 'phone'),
                'default_delivery_note' => $user->default_delivery_note,
                'express_checkout' => (bool) ($user->express_checkout ?? false),
            ],
            'accessibility' => [
                'font_size_preference' => (string) ($user->font_size_preference ?? 'default'),
                'reduced_motion' => (bool) ($user->reduced_motion ?? false),
                'high_contrast_mode' => (bool) ($user->high_contrast_mode ?? false),
            ],
            'general' => [
                'currency_preference' => (string) ($user->currency_preference ?? 'IQD'),
                'timezone_preference' => (string) ($user->timezone_preference ?? 'Asia/Baghdad'),
                'date_format_preference' => (string) ($user->date_format_preference ?? 'dmy'),
            ],
        ];
    }

    public function categories()
    {
        return response()->json([
            'data' => Category::query()
                ->withCount('products')
                ->orderBy('name_en')
                ->get()
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'slug' => (string) ($category->slug ?? $category->id),
                    'name' => method_exists($category, 'localizedName') ? $category->localizedName() : ($category->name_en ?? $category->name ?? ''),
                    'description' => $category->description_en ?? '',
                    'product_count' => $category->products_count,
                    'image_url' => $category->image ? asset('storage/' . $category->image) : null,
                ]),
        ]);
    }

    public function brands()
    {
        return response()->json([
            'data' => Product::query()
                ->whereNotNull('brand')
                ->where('brand', '!=', '')
                ->distinct()
                ->orderBy('brand')
                ->pluck('brand')
                ->values(),
        ]);
    }

    public function products(Request $request)
    {
        $query = Product::query()
            ->with(['category', 'images', 'reviews'])
            ->where('is_active', true);

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($builder) use ($search): void {
                SqlSafe::whereLike($builder, 'name_en', $search);
                SqlSafe::orWhereLike($builder, 'sku', $search);
                SqlSafe::orWhereLike($builder, 'oem_number', $search);
                SqlSafe::orWhereLike($builder, 'part_number', $search);
                SqlSafe::orWhereLike($builder, 'brand', $search);
            });
        }

        if ($brand = $request->query('brand')) {
            $query->where('brand', $brand);
        }

        if ($category = $request->query('category')) {
            $query->whereHas('category', fn ($builder) => $builder->where('name_en', $category));
        }

        if ($request->boolean('in_stock')) {
            $query->where('stock_quantity', '>', 0);
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float) $request->query('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->query('max_price'));
        }

        if ($request->filled('vehicle_brand') || $request->filled('vehicle_model') || $request->filled('year') || $request->filled('engine')) {
            $vehicleBrand = trim((string) $request->query('vehicle_brand'));
            $vehicleModel = trim((string) $request->query('vehicle_model'));
            $year = (int) $request->query('year');
            $engine = trim((string) $request->query('engine'));
            $query->whereHas('vehicleFitments', function ($fitment) use ($vehicleBrand, $vehicleModel, $year, $engine): void {
                if ($vehicleBrand !== '') {
                    $fitment->whereHas('brand', fn ($brand) => $brand->where('name', $vehicleBrand)->orWhere('slug', $vehicleBrand));
                }
                if ($vehicleModel !== '') {
                    $fitment->whereHas('model', fn ($model) => $model->where('name', $vehicleModel)->orWhere('slug', $vehicleModel));
                }
                if ($year > 0) {
                    $fitment->where(function ($yearQuery) use ($year): void {
                        $yearQuery->whereNull('year_from')->orWhere('year_from', '<=', $year);
                    })->where(function ($yearQuery) use ($year): void {
                        $yearQuery->whereNull('year_to')->orWhere('year_to', '>=', $year);
                    });
                }
                if ($engine !== '') {
                    $fitment->where(function ($engineQuery) use ($engine): void {
                        $engineQuery->whereNull('engine')->orWhere(function ($likeQuery) use ($engine): void {
                            SqlSafe::whereLike($likeQuery, 'engine', $engine);
                        });
                    });
                }
            });
        }

        match ($request->query('sort')) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'stock_desc' => $query->orderByDesc('stock_quantity'),
            'popular' => $query->withCount('orderItems')->orderByDesc('order_items_count'),
            default => $query->latest('id'),
        };

        $perPage = min(max((int) $request->query('per_page', 24), 1), 60);
        $products = $query->paginate($perPage);

        return response()->json([
            'data' => $products->getCollection()->map(fn (Product $product) => $this->productPayload($product, $request->user())),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function searchAutocomplete(Request $request)
    {
        $term = SqlSafe::searchTerm($request->query('q', $request->query('search', '')), 80);
        $limit = min(max((int) $request->query('limit', 8), 1), 12);

        if (mb_strlen($term) < 2) {
            return response()->json(['data' => [
                'query' => $term,
                'products' => [],
                'categories' => [],
                'brands' => [],
            ]]);
        }

        $products = Product::query()
            ->with(['category', 'images', 'reviews'])
            ->where('is_active', true)
            ->where(function ($query) use ($term): void {
                SqlSafe::whereLike($query, 'name_en', $term);
                SqlSafe::orWhereLike($query, 'name_ar', $term);
                SqlSafe::orWhereLike($query, 'name_ku', $term);
                SqlSafe::orWhereLike($query, 'sku', $term);
                SqlSafe::orWhereLike($query, 'oem_number', $term);
                SqlSafe::orWhereLike($query, 'part_number', $term);
                SqlSafe::orWhereLike($query, 'brand', $term);
            })
            ->orderByRaw('CASE WHEN stock_quantity > 0 THEN 0 ELSE 1 END')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'slug' => $product->slug,
                'label' => $product->localizedName(),
                'sku' => (string) $product->sku,
                'brand' => (string) $product->brand,
                'price' => $product->priceFor($request->user()),
                'stock_quantity' => (int) $product->stock_quantity,
                'image_url' => $this->primaryImageUrl($product),
            ])
            ->values();

        $categories = Category::query()
            ->where(function ($query) use ($term): void {
                SqlSafe::whereLike($query, 'name_en', $term);
                SqlSafe::orWhereLike($query, 'name_ar', $term);
                SqlSafe::orWhereLike($query, 'name_ku', $term);
            })
            ->withCount('products')
            ->orderByDesc('products_count')
            ->limit($limit)
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'slug' => (string) ($category->slug ?? $category->id),
                'label' => method_exists($category, 'localizedName') ? $category->localizedName() : (string) ($category->name_en ?? ''),
                'product_count' => $category->products_count,
            ])
            ->values();

        $brands = Product::query()
            ->where('is_active', true)
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->where(function ($query) use ($term): void {
                SqlSafe::whereLike($query, 'brand', $term);
            })
            ->select('brand')
            ->distinct()
            ->orderBy('brand')
            ->limit($limit)
            ->pluck('brand')
            ->map(fn (string $brand) => ['label' => $brand])
            ->values();

        return response()->json(['data' => [
            'query' => $term,
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
        ]]);
    }

    public function product(Request $request, string $idOrSlug)
    {
        $product = $this->findProduct($idOrSlug);
        if ($request->user()) {
            $this->recordRecentlyViewedProduct($request->user(), $product);
        }

        return response()->json(['data' => $this->productPayload($product, $request->user())]);
    }

    public function vehicleFitments()
    {
        return response()->json([
            'data' => VehicleBrand::query()
                ->with('models')
                ->orderBy('name')
                ->get()
                ->map(fn (VehicleBrand $brand) => [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'slug' => $brand->slug,
                    'models' => $brand->models->map(fn ($model) => [
                        'id' => $model->id,
                        'name' => $model->name,
                        'slug' => $model->slug,
                    ])->values()->all(),
                ]),
        ]);
    }

    public function decodeVin(Request $request)
    {
        $data = $request->validate([
            'vin' => ['required', 'string', 'min:8', 'max:17'],
        ]);

        $vin = strtoupper(preg_replace('/[^A-Z0-9]/', '', $data['vin']) ?? '');
        abort_if(strlen($vin) < 8, 422, __('errors.vin_too_short'));

        $manufacturer = $this->vinManufacturer($vin);
        $year = $this->vinYear($vin);
        $model = $request->input('model') ?: $this->vinModelGuess($manufacturer);
        $engine = $request->input('engine') ?: __('Engine match required');

        $query = Product::query()
            ->with(['category', 'images', 'reviews', 'vehicleFitments.brand', 'vehicleFitments.model'])
            ->where('is_active', true);

        if ($manufacturer !== 'Unknown') {
            $query->where(function ($builder) use ($manufacturer): void {
                SqlSafe::whereLike($builder, 'brand', $manufacturer);
                $builder->orWhereHas('vehicleFitments.brand', fn ($brand) => SqlSafe::whereLike($brand, 'name', $manufacturer));
            });
        }

        if ($year > 0) {
            $query->orWhereHas('vehicleFitments', function ($fitment) use ($year): void {
                $fitment->where(function ($yearQuery) use ($year): void {
                    $yearQuery->whereNull('year_from')->orWhere('year_from', '<=', $year);
                })->where(function ($yearQuery) use ($year): void {
                    $yearQuery->whereNull('year_to')->orWhere('year_to', '>=', $year);
                });
            });
        }

        $products = $query->limit(12)->get();
        if ($products->isEmpty()) {
            $products = Product::query()
                ->with(['category', 'images', 'reviews', 'vehicleFitments.brand', 'vehicleFitments.model'])
                ->where('is_active', true)
                ->latest('id')
                ->limit(8)
                ->get();
        }

        return response()->json(['data' => [
            'vin' => $vin,
            'manufacturer' => $manufacturer,
            'model' => $model,
            'year' => $year,
            'engine' => $engine,
            'confidence' => $manufacturer === 'Unknown' ? 0.58 : 0.82,
            'compatible_products' => $products->map(fn (Product $product) => $this->productPayload($product, $request->user()))->values(),
        ]]);
    }

    public function couponPreview(Request $request, CouponService $coupons)
    {
        $data = $request->validate([
            'coupon_code' => ['required', 'string', 'max:60'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
        ]);

        $preview = $coupons->preview($data['coupon_code'], (float) ($data['subtotal'] ?? 0), $request->user());

        return response()->json([
            'valid' => $preview['valid'],
            'message' => $preview['message'] ?? ($preview['valid'] ? __('Coupon applied.') : __('Coupon is not valid.')),
            'code' => $preview['code'],
            'discount' => $preview['discount'],
            'free_shipping' => $preview['free_shipping'],
        ]);
    }

    public function recentlyViewed(Request $request)
    {
        $limit = min(max((int) $request->query('limit', 12), 1), 60);

        $views = RecentlyViewedProduct::query()
            ->with(['product.category', 'product.images', 'product.reviews'])
            ->where('user_id', $request->user()->id)
            ->whereHas('product', fn ($query) => $query->where('is_active', true))
            ->latest('viewed_at')
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => $views
                ->map(fn (RecentlyViewedProduct $view) => [
                    'viewed_at' => optional($view->viewed_at)->toISOString(),
                    'product' => $this->productPayload($view->product, $request->user()),
                ])
                ->values(),
        ]);
    }

    public function storeRecentlyViewed(Request $request, string $idOrSlug)
    {
        $product = $this->findProduct($idOrSlug);
        abort_if(! $product->is_active, 404);

        $this->recordRecentlyViewedProduct($request->user(), $product);

        return response()->json([
            'data' => [
                'viewed_at' => now()->toISOString(),
                'product' => $this->productPayload($product, $request->user()),
            ],
        ], 201);
    }

    public function clearRecentlyViewed(Request $request)
    {
        RecentlyViewedProduct::query()
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['message' => __('Recently viewed products cleared.')]);
    }

    public function backInStockSubscriptions(Request $request)
    {
        return response()->json([
            'data' => BackInStockSubscription::query()
                ->with(['product.category', 'product.images', 'product.reviews'])
                ->where('user_id', $request->user()->id)
                ->whereNull('notified_at')
                ->latest('id')
                ->get()
                ->map(fn (BackInStockSubscription $subscription) => [
                    'id' => $subscription->id,
                    'created_at' => optional($subscription->created_at)->toISOString(),
                    'product' => $this->productPayload($subscription->product, $request->user()),
                ])
                ->values(),
        ]);
    }

    public function subscribeBackInStock(Request $request, string $idOrSlug)
    {
        $product = $this->findProduct($idOrSlug);
        abort_if(! $product->is_active, 404);

        if ((int) $product->stock_quantity > 0) {
            BackInStockSubscription::query()
                ->where('user_id', $request->user()->id)
                ->where('product_id', $product->id)
                ->delete();

            return response()->json(['data' => [
                'subscribed' => false,
                'available_now' => true,
                'message' => __('This product is already in stock.'),
                'product' => $this->productPayload($product, $request->user()),
            ]]);
        }

        $subscription = BackInStockSubscription::query()->firstOrCreate([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
        ]);

        return response()->json(['data' => [
            'id' => $subscription->id,
            'subscribed' => true,
            'available_now' => false,
            'message' => __('We will notify you when this product is back in stock.'),
            'product' => $this->productPayload($product, $request->user()),
        ]], $subscription->wasRecentlyCreated ? 201 : 200);
    }

    public function unsubscribeBackInStock(Request $request, string $idOrSlug)
    {
        $product = $this->findProduct($idOrSlug);

        BackInStockSubscription::query()
            ->where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->delete();

        return response()->json(['message' => __('Back-in-stock notification removed.')]);
    }

    public function cart(Request $request)
    {
        return response()->json(['data' => $this->cartPayload($this->cartFor($request->user()), $request->user())]);
    }

    public function addCartItem(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);
        $cart = $this->cartFor($request->user());
        $item = $cart->items()->firstOrNew(['product_id' => $data['product_id']]);
        $item->quantity = max(1, (int) $item->quantity + (int) ($data['quantity'] ?? 1));
        $item->save();

        return response()->json(['data' => $this->cartPayload($cart->fresh('items.product'), $request->user())]);
    }

    public function updateCartItem(Request $request, int $productId)
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0']]);
        $cart = $this->cartFor($request->user());
        $item = $cart->items()->where('product_id', $productId)->first();
        if ($item && (int) $data['quantity'] <= 0) {
            $item->delete();
        } elseif ($item) {
            $item->update(['quantity' => (int) $data['quantity']]);
        }

        return response()->json(['data' => $this->cartPayload($cart->fresh('items.product'), $request->user())]);
    }

    public function deleteCartItem(Request $request, int $productId)
    {
        $cart = $this->cartFor($request->user());
        $cart->items()->where('product_id', $productId)->delete();
        HeaderComposer::forgetCartCacheForUser((int) $request->user()->id);

        return response()->json(['data' => $this->cartPayload($cart->fresh('items.product'), $request->user())]);
    }

    public function wishlist(Request $request)
    {
        return response()->json([
            'data' => Wishlist::query()
                ->where('user_id', $request->user()->id)
                ->pluck('product_id')
                ->values(),
        ]);
    }

    public function addWishlist(Request $request, string $idOrSlug)
    {
        $product = $this->findProduct($idOrSlug);
        Wishlist::query()->firstOrCreate([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
        ]);

        return response()->json(['message' => __('Saved.')]);
    }

    public function deleteWishlist(Request $request, string $idOrSlug)
    {
        $product = $this->findProduct($idOrSlug);
        Wishlist::query()
            ->where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->delete();
        HeaderComposer::forgetWishlistCacheForUser((int) $request->user()->id);

        return response()->json(['message' => __('Removed.')]);
    }

    public function checkout(Request $request, CouponService $coupons)
    {
        $data = $request->validate([
            'address_id' => ['nullable', 'integer'],
            'coupon_code' => ['nullable', 'string', 'max:60'],
            'payment_method' => ['nullable', Rule::in(['cash_on_delivery', 'zaincash', 'fastpay', 'bank_transfer'])],
        ]);
        $cart = $this->cartFor($request->user())->load('items.product');
        abort_if($cart->items->isEmpty(), 422, __('errors.cart_empty'));

        $address = $this->resolveOrderAddress($request->user(), $data['address_id'] ?? null);
        abort_if(! $address, 422, __('errors.delivery_address_required'));
        $this->abortIfAddressPhoneInvalid($address);
        foreach ($cart->items as $item) {
            abort_if(! $item->product || ! $item->product->is_active, 422, __('errors.cart_contains_unavailable'));
            abort_if((int) $item->product->stock_quantity < (int) $item->quantity, 422, __('Insufficient stock for :product.', ['product' => $item->product->localizedName()]));
        }

        $subtotal = $cart->items->sum(fn (CartItem $item) => $item->quantity * $item->product->priceFor($request->user()));
        $shipping = 5000.0;
        $coupon = $data['coupon_code'] ?? null;
        $preview = $coupon ? $coupons->preview($coupon, (float) $subtotal, $request->user()) : null;
        $discount = $preview && $preview['valid'] ? (float) $preview['discount'] : 0.0;
        $total = max(0, (float) $subtotal + $shipping - $discount);

        $order = new Order();
        $order->forceFill([
            'user_id' => $request->user()->id,
            'order_number' => 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5)),
            'subtotal_amount' => $subtotal,
            'shipping_fee' => $shipping,
            'discount_amount' => $discount,
            'coupon_code' => $preview && $preview['valid'] ? $preview['code'] : null,
            'grand_total' => $total,
            'total_amount' => $total,
            'status' => Order::STATUS_PENDING,
            'payment_method' => $data['payment_method'] ?? 'cash_on_delivery',
            'payment_status' => Order::PAYMENT_PENDING,
            'delivery_address' => $address->address_line1,
            'delivery_city' => $address->city,
            'delivery_phone' => $address->phone,
        ])->save();

        foreach ($cart->items as $item) {
            $unit = $item->product->priceFor($request->user());
            $order->items()->create([
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'unit_price' => $unit,
                'subtotal' => $unit * $item->quantity,
            ]);
        }

        $cart->items()->delete();
        HeaderComposer::forgetCartCacheForUser((int) $request->user()->id);

        return response()->json([
            'cart' => $this->cartPayload($cart->fresh('items.product'), $request->user()),
            'order' => $this->orderPayload($order->fresh('items.product')),
        ]);
    }

    public function checkoutReview(Request $request, CouponService $coupons, CheckoutTotals $totals)
    {
        $data = $request->validate([
            'address_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'coupon_code' => ['nullable', 'string', 'max:80'],
        ]);

        $user = $request->user();
        $cart = $this->cartFor($user)->load('items.product');
        abort_if($cart->items->isEmpty(), 422, __('errors.cart_empty'));

        $address = $this->resolveOrderAddress($user, $data['address_id'] ?? null);
        abort_if(! $address, 422, __('errors.delivery_address_required'));
        $this->abortIfAddressPhoneInvalid($address);

        $lineItems = [];
        $responseItems = [];
        foreach ($cart->items as $item) {
            if (! $item->product) {
                continue;
            }
            $unitPrice = (float) $item->product->priceFor($user);
            $lineItems[] = ['quantity' => (int) $item->quantity, 'unit_price' => $unitPrice];
            $responseItems[] = [
                'product' => $this->productPayload($item->product, $user),
                'quantity' => (int) $item->quantity,
                'unit_price' => $unitPrice,
                'subtotal' => round($unitPrice * $item->quantity, 2),
            ];
        }

        $shippingFee = (float) Setting::getValue('shipping_fee', 5000);
        $code = $coupons->normalizeCode((string) ($data['coupon_code'] ?? ''));
        $subtotalForCoupon = array_sum(array_map(fn ($i) => $i['quantity'] * $i['unit_price'], $lineItems));
        $couponPreview = $code !== '' ? $coupons->preview($code, round($subtotalForCoupon, 2), $user) : null;

        $computed = $totals->compute($lineItems, $shippingFee, $couponPreview);

        return response()->json(['data' => [
            'address' => $this->addressPayload($address),
            'items' => $responseItems,
            'notes' => (string) ($data['notes'] ?? ''),
            'totals' => $computed,
            'coupon_summary' => $this->couponSummaryFor($couponPreview),
        ]]);
    }

    public function buyNowPreview(Request $request, string $idOrSlug, CouponService $coupons, CheckoutTotals $totals)
    {
        $data = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'address_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'coupon_code' => ['nullable', 'string', 'max:80'],
        ]);

        $user = $request->user();
        $product = $this->findProduct($idOrSlug);
        abort_unless($product->is_active, 422, __('errors.product_unavailable'));
        abort_if((int) $product->stock_quantity < 1, 422, __('errors.stock_insufficient'));

        $requestedQuantity = (int) ($data['quantity'] ?? 1);
        $quantity = min(99, max(1, min($requestedQuantity, (int) $product->stock_quantity)));

        $address = $this->resolveOrderAddress($user, $data['address_id'] ?? null);
        abort_if(! $address, 422, __('errors.delivery_address_required'));
        $this->abortIfAddressPhoneInvalid($address);

        $unitPrice = (float) $product->priceFor($user);
        $shippingFee = (float) Setting::getValue('shipping_fee', 5000);
        $code = $coupons->normalizeCode((string) ($data['coupon_code'] ?? ''));
        $subtotalForCoupon = round($unitPrice * $quantity, 2);
        $couponPreview = $code !== '' ? $coupons->preview($code, $subtotalForCoupon, $user) : null;

        $computed = $totals->compute(
            [['quantity' => $quantity, 'unit_price' => $unitPrice]],
            $shippingFee,
            $couponPreview,
        );

        $payload = [
            'product' => $this->productPayload($product, $user),
            'quantity' => $quantity,
            'address' => $this->addressPayload($address),
            'notes' => (string) ($data['notes'] ?? ''),
            'totals' => $computed,
            'coupon_summary' => $this->couponSummaryFor($couponPreview),
        ];

        if ($quantity !== $requestedQuantity) {
            $payload['quantity_requested'] = $requestedQuantity;
        }

        return response()->json(['data' => $payload]);
    }

    public function buyNowPlace(Request $request, string $idOrSlug, CouponService $coupons, CheckoutTotals $totals)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'address_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'coupon_code' => ['nullable', 'string', 'max:80'],
            'payment_method' => ['nullable', Rule::in(['cash_on_delivery', 'zaincash', 'fastpay', 'bank_transfer'])],
        ]);

        $user = $request->user();
        $product = $this->findProduct($idOrSlug);
        abort_unless($product->is_active, 422, __('errors.product_unavailable'));

        $quantity = (int) $data['quantity'];
        abort_if((int) $product->stock_quantity < $quantity, 422, __('errors.stock_insufficient'));

        $address = $this->resolveOrderAddress($user, $data['address_id'] ?? null);
        abort_if(! $address, 422, __('errors.delivery_address_required'));
        $this->abortIfAddressPhoneInvalid($address);

        $unitPrice = (float) $product->priceFor($user);
        $shippingFee = (float) Setting::getValue('shipping_fee', 5000);
        $code = $coupons->normalizeCode((string) ($data['coupon_code'] ?? ''));
        $subtotalForCoupon = round($unitPrice * $quantity, 2);
        $couponPreview = null;
        if ($code !== '') {
            $couponPreview = $coupons->preview($code, $subtotalForCoupon, $user);
            abort_if(
                ! (bool) ($couponPreview['valid'] ?? false),
                422,
                (string) ($couponPreview['message'] ?? __('Coupon could not be applied.')),
            );
        }

        $computed = $totals->compute(
            [['quantity' => $quantity, 'unit_price' => $unitPrice]],
            $shippingFee,
            $couponPreview,
        );

        $order = new Order();
        $order->forceFill([
            'user_id' => $user->id,
            'order_number' => 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5)),
            'subtotal_amount' => $computed['subtotal'],
            'shipping_fee' => $computed['shipping_fee'],
            'discount_amount' => $computed['discount_amount'],
            'coupon_code' => ($couponPreview['valid'] ?? false) ? (string) ($couponPreview['code'] ?? '') : null,
            'grand_total' => $computed['grand_total'],
            'total_amount' => $computed['grand_total'],
            'status' => Order::STATUS_PENDING,
            'payment_method' => $data['payment_method'] ?? 'cash_on_delivery',
            'payment_status' => Order::PAYMENT_PENDING,
            'delivery_address' => $address->address_line1,
            'delivery_city' => $address->city,
            'delivery_phone' => $address->phone,
            'notes' => trim((string) ($data['notes'] ?? '')) !== '' ? (string) $data['notes'] : null,
        ])->save();

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => round($unitPrice * $quantity, 2),
        ]);

        $product->update(['stock_quantity' => (int) $product->stock_quantity - $quantity]);

        return response()->json([
            'order' => $this->orderPayload($order->fresh('items.product')),
        ]);
    }

    private function couponSummaryFor(?array $couponPreview): array
    {
        if ($couponPreview === null) {
            return [
                'valid' => false,
                'code' => '',
                'discount' => 0.0,
                'free_shipping' => false,
                'message' => null,
            ];
        }
        return [
            'valid' => (bool) ($couponPreview['valid'] ?? false),
            'code' => (string) ($couponPreview['code'] ?? ''),
            'discount' => (float) ($couponPreview['discount'] ?? 0),
            'free_shipping' => (bool) ($couponPreview['free_shipping'] ?? false),
            'message' => $couponPreview['message'] ?? null,
        ];
    }

    public function orders(Request $request)
    {
        return response()->json([
            'data' => $request->user()
                ->orders()
                ->with('items.product.category', 'items.product.images', 'items.product.reviews')
                ->latest()
                ->limit(80)
                ->get()
                ->map(fn (Order $order) => $this->orderPayload($order)),
        ]);
    }

    public function order(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        return response()->json(['data' => $this->orderPayload($order->load('items.product.category', 'items.product.images', 'items.product.reviews'))]);
    }

    public function reorder(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'replace_cart' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();
        $order->load('items.product.category', 'items.product.images', 'items.product.reviews');
        $cart = $this->cartFor($user);

        $added = [];
        $skipped = [];

        DB::transaction(function () use ($cart, $data, $order, $user, &$added, &$skipped): void {
            if ((bool) ($data['replace_cart'] ?? false)) {
                $cart->items()->delete();
            }

            foreach ($order->items as $orderItem) {
                $product = $orderItem->product;
                if (! $product || ! $product->is_active) {
                    $skipped[] = [
                        'product_id' => $orderItem->product_id,
                        'reason' => 'unavailable',
                    ];
                    continue;
                }

                $availableStock = (int) $product->stock_quantity;
                if ($availableStock <= 0) {
                    $skipped[] = [
                        'product_id' => $product->id,
                        'product' => $this->productPayload($product, $user),
                        'reason' => 'out_of_stock',
                    ];
                    continue;
                }

                $requestedQuantity = max(1, (int) $orderItem->quantity);
                $item = $cart->items()->firstOrNew(['product_id' => $product->id]);
                $currentQuantity = (int) ($item->exists ? $item->quantity : 0);
                $nextQuantity = min($availableStock, $currentQuantity + $requestedQuantity);
                $actualAddedQuantity = max(0, $nextQuantity - $currentQuantity);

                if ($actualAddedQuantity <= 0) {
                    $skipped[] = [
                        'product_id' => $product->id,
                        'product' => $this->productPayload($product, $user),
                        'reason' => 'max_stock_in_cart',
                    ];
                    continue;
                }

                $item->quantity = $nextQuantity;
                $item->save();

                $added[] = [
                    'product' => $this->productPayload($product, $user),
                    'requested_quantity' => $requestedQuantity,
                    'added_quantity' => $actualAddedQuantity,
                    'cart_quantity' => (int) $item->quantity,
                    'limited_by_stock' => (int) $item->quantity < ($currentQuantity + $requestedQuantity),
                ];
            }
        });

        if ($added === []) {
            return response()->json([
                'message' => __('No products from this order can be reordered right now.'),
                'added' => [],
                'skipped' => $skipped,
                'cart' => $this->cartPayload($cart->fresh('items.product.category', 'items.product.images', 'items.product.reviews'), $user),
            ], 422);
        }

        return response()->json([
            'message' => __('Products added to cart.'),
            'added' => $added,
            'skipped' => $skipped,
            'cart' => $this->cartPayload($cart->fresh('items.product.category', 'items.product.images', 'items.product.reviews'), $user),
        ]);
    }

    public function orderInvoice(Request $request, Order $order, InvoiceRenderer $renderer)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        $explicit = (string) $request->query('lang', '');
        $locale = $renderer->resolveLocale(
            $explicit !== '' ? $explicit : null,
            $order,
            $request->user(),
        );

        return $renderer->render($order, $locale)
            ->download('invoice-' . $order->id . '-' . $locale . '.pdf');
    }

    public function reviews(string $idOrSlug)
    {
        $product = $this->findProduct($idOrSlug);

        return response()->json([
            'data' => $product->reviews()->latest()->limit(20)->get()->map(fn (ProductReview $review) => [
                'id' => $review->id,
                'author' => $review->user?->name ?? __('Customer'),
                'rating' => (int) $review->rating,
                'comment' => (string) ($review->comment ?? ''),
                'created_at' => optional($review->created_at)->toISOString(),
            ]),
        ]);
    }

    public function storeReview(Request $request, string $idOrSlug)
    {
        $product = $this->findProduct($idOrSlug);
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $review = $product->reviews()->create([
            'user_id' => $request->user()->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'is_approved' => false,
            'reviewed_at' => now(),
        ]);

        return response()->json(['data' => [
            'id' => $review->id,
            'author' => $request->user()->name,
            'rating' => (int) $review->rating,
            'comment' => (string) ($review->comment ?? ''),
            'created_at' => optional($review->created_at)->toISOString(),
        ]], 201);
    }

    public function addresses(Request $request)
    {
        return response()->json([
            'data' => $request->user()->addresses()->latest('is_default')->latest('id')->get()->map(fn (UserAddress $address) => $this->addressPayload($address)),
        ]);
    }

    public function storeAddress(Request $request)
    {
        $address = $request->user()->addresses()->create($this->addressData($request));
        $this->syncDefaultAddress($request, $address);

        return response()->json(['data' => $this->addressPayload($address->fresh())], 201);
    }

    public function updateAddress(Request $request, UserAddress $address)
    {
        abort_unless($address->user_id === $request->user()->id, 403);
        $address->update($this->addressData($request));
        $this->syncDefaultAddress($request, $address);

        return response()->json(['data' => $this->addressPayload($address->fresh())]);
    }

    public function setDefaultAddress(Request $request, UserAddress $address)
    {
        abort_unless($address->user_id === $request->user()->id, 403);
        $request->user()->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return response()->json(['data' => $this->addressPayload($address->fresh())]);
    }

    public function deleteAddress(Request $request, UserAddress $address)
    {
        abort_unless($address->user_id === $request->user()->id, 403);
        $address->delete();

        return response()->json(['message' => __('Address deleted.')]);
    }

    public function requestCancellation(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'string', 'max:500'],
        ]);

        $order->update([
            'cancellation_requested_at' => now(),
            'cancellation_reason' => trim($data['reason'] . "\n" . ($data['notes'] ?? '') . "\n" . ($data['attachment'] ?? '')),
        ]);

        return response()->json(['message' => __('Cancellation request submitted.')]);
    }

    public function requestReturn(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'string', 'max:500'],
        ]);

        $order->returnRequests()->create([
            'user_id' => $request->user()->id,
            'reason' => $data['reason'],
            'admin_note' => trim(($data['notes'] ?? '') . "\n" . ($data['attachment'] ?? '')),
            'type' => 'return',
            'status' => ReturnRequest::STATUS_REQUESTED,
            'requested_at' => now(),
        ]);

        return response()->json(['message' => __('Return request submitted.')]);
    }

    public function notifications(Request $request)
    {
        $user = $request->user();
        $items = collect();

        if ((bool) ($user->notify_order_updates ?? true)) {
            $latestOrder = $user->orders()->latest()->first();
            if ($latestOrder) {
                $items->push([
                    'id' => 'order-' . $latestOrder->id,
                    'type' => 'order',
                    'title' => __('Order update'),
                    'message' => __('Order :order is :status.', [
                        'order' => $latestOrder->order_number,
                        'status' => __(ucfirst(str_replace('_', ' ', $latestOrder->status))),
                    ]),
                    'created_at' => optional($latestOrder->updated_at)->toISOString(),
                    'read' => false,
                ]);
            }
        }

        if ((bool) ($user->notify_promotions ?? false) && Schema::hasTable('coupons')) {
            $coupon = Coupon::query()
                ->where('is_active', true)
                ->where(function ($query): void {
                    $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                })
                ->where(function ($query): void {
                    $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                })
                ->latest('id')
                ->first();

            if ($coupon) {
                $items->push([
                    'id' => 'coupon-' . $coupon->id,
                    'type' => 'promotion',
                    'title' => __('Promotion'),
                    'message' => __(':code is active for eligible carts.', ['code' => $coupon->code]),
                    'created_at' => optional($coupon->updated_at)->toISOString(),
                    'read' => false,
                ]);
            }
        }

        if ((bool) ($user->notify_stock_alerts ?? true)) {
            $savedProductIds = Wishlist::query()
                ->where('user_id', $user->id)
                ->pluck('product_id');
            $lowStockProducts = Product::query()
                ->whereIn('id', $savedProductIds)
                ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                ->latest('updated_at')
                ->limit(5)
                ->get();

            foreach ($lowStockProducts as $product) {
                $items->push([
                    'id' => 'stock-' . $product->id,
                    'type' => 'stock',
                    'title' => __('Stock alert'),
                    'message' => __(':name has :count left.', [
                        'name' => $product->localizedName(app()->getLocale()),
                        'count' => (int) $product->stock_quantity,
                    ]),
                    'created_at' => optional($product->updated_at)->toISOString(),
                    'read' => false,
                ]);
            }
        }

        return response()->json(['data' => $items->values()]);
    }

    public function dealerDashboard(Request $request)
    {
        $user = $request->user();
        $this->requireDealer($request);

        $dealerOrders = $this->dealerOrdersQuery($user);
        $dealerProducts = $this->dealerProductsQuery($user);
        $openOrders = (clone $dealerOrders)
            ->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_PROCESSING, Order::STATUS_SHIPPED])
            ->count();
        $totalSpend = (float) (clone $dealerOrders)->sum(DB::raw('COALESCE(grand_total, total_amount, 0)'));

        return response()->json(['data' => [
            'metrics' => [
                [
                    'label' => __('Dealer discount'),
                    'value' => number_format((float) $user->dealer_discount, 0) . '%',
                    'delta' => 0,
                ],
                [
                    'label' => __('Active products'),
                    'value' => (string) (clone $dealerProducts)->where('is_active', true)->count(),
                    'delta' => (clone $dealerProducts)->where('is_active', true)->where('created_at', '>=', now()->subDays(30))->count(),
                ],
                [
                    'label' => __('Open orders'),
                    'value' => (string) $openOrders,
                    'delta' => (clone $dealerOrders)->where('created_at', '>=', now()->subDays(30))->count(),
                ],
                [
                    'label' => __('Low stock'),
                    'value' => (string) (clone $dealerProducts)->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count(),
                    'delta' => 0,
                ],
            ],
            'operations' => [
                [
                    'title' => __('Dealer pricing'),
                    'subtitle' => __('Review dealer-specific prices and margin rules.'),
                    'type' => 'pricing',
                ],
                [
                    'title' => __('Inventory management'),
                    'subtitle' => __('Track real stock, low stock and dealer availability.'),
                    'type' => 'inventory',
                ],
                [
                    'title' => __('Dealer analytics'),
                    'subtitle' => __('Total order value: IQD :amount', ['amount' => number_format($totalSpend, 0)]),
                    'type' => 'analytics',
                ],
            ],
        ]]);
    }

    public function dealerProducts(Request $request)
    {
        $this->requireDealer($request);

        return response()->json(['data' => $this->dealerProductsQuery($request->user())
            ->with(['category', 'images', 'reviews', 'vehicleFitments.brand', 'vehicleFitments.model'])
            ->latest('id')
            ->limit(80)
            ->get()
            ->map(fn (Product $product) => $this->productPayload($product, $request->user()))
            ->values()]);
    }

    public function dealerOrders(Request $request)
    {
        $this->requireDealer($request);

        return response()->json(['data' => $this->dealerOrdersQuery($request->user())
            ->with('items.product.category', 'items.product.images', 'items.product.reviews')
            ->latest('id')
            ->limit(80)
            ->get()
            ->map(fn (Order $order) => $this->orderPayload($order))
            ->values()]);
    }

    public function dealerUpdateStock(Request $request, string $idOrSlug)
    {
        $this->requireDealer($request);
        $product = $this->findProduct($idOrSlug);
        if (! $request->user()->isAdminPanelUser()) {
            abort_unless(Schema::hasColumn('products', 'created_by') && (int) $product->created_by === (int) $request->user()->id, 403);
        }

        $data = $request->validate(['stock_quantity' => ['required', 'integer', 'min:0']]);
        $product->update(['stock_quantity' => (int) $data['stock_quantity']]);

        return response()->json(['data' => $this->productPayload($product->fresh(['category', 'images', 'reviews', 'vehicleFitments.brand', 'vehicleFitments.model']), $request->user())]);
    }

    public function adminDashboard(Request $request)
    {
        $this->requirePermission($request, User::PERMISSION_DASHBOARD_VIEW);

        $revenue = (float) Order::query()->sum(DB::raw('COALESCE(grand_total, total_amount, 0)'));

        return response()->json(['data' => [
            'metrics' => [
                ['label' => 'revenue', 'value' => 'IQD ' . number_format($revenue, 0), 'delta' => Order::query()->where('created_at', '>=', now()->subDays(30))->count()],
                ['label' => 'products', 'value' => (string) Product::query()->count(), 'delta' => Product::query()->where('created_at', '>=', now()->subDays(30))->count()],
                ['label' => 'orders', 'value' => (string) Order::query()->count(), 'delta' => Order::query()->where('created_at', '>=', now()->subDays(30))->count()],
                ['label' => 'pending_orders', 'value' => (string) Order::query()->where('status', Order::STATUS_PENDING)->count(), 'delta' => 0],
                ['label' => 'low_stock', 'value' => (string) Product::query()->lowStock()->count(), 'delta' => 0],
                ['label' => 'dealers', 'value' => (string) User::query()->where('role', User::ROLE_DEALER)->count(), 'delta' => User::query()->where('role', User::ROLE_DEALER)->where('created_at', '>=', now()->subDays(30))->count()],
                ['label' => 'users', 'value' => (string) User::query()->count(), 'delta' => User::query()->where('created_at', '>=', now()->subDays(30))->count()],
            ],
            'month_labels' => collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->format('M'))->values(),
            'month_orders' => collect(range(5, 0))->map(function ($i) {
                $date = now()->subMonths($i);
                return Order::query()->whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->count();
            })->values(),
            'stock_labels' => ['In', 'Out', 'Low', 'Active'],
            'stock_movement' => [
                InventoryMovement::query()->where('type', InventoryMovement::TYPE_IN)->sum('quantity'),
                InventoryMovement::query()->where('type', InventoryMovement::TYPE_OUT)->sum('quantity'),
                Product::query()->lowStock()->count(),
                Product::query()->where('is_active', true)->count(),
            ],
            'operations_queue' => [
                ['label' => 'pending_orders', 'value' => (string) Order::query()->where('status', Order::STATUS_PENDING)->count(), 'delta' => 0],
                ['label' => 'low_stock', 'value' => (string) Product::query()->lowStock()->count(), 'delta' => 0],
                ['label' => 'dealer_requests', 'value' => (string) User::query()->where('role', User::ROLE_DEALER)->where('dealer_status', User::DEALER_STATUS_INACTIVE)->count(), 'delta' => 0],
            ],
        ]]);
    }

    public function adminModule(Request $request, string $section)
    {
        $permission = $this->permissionForAdminSection($section);
        abort_unless($permission !== null, 404);
        $this->requirePermission($request, $permission);

        $search = trim((string) $request->query('search', ''));

        $payload = match ($section) {
            'products', 'inventory' => [
                'columns' => ['product', 'sku', 'brand', 'stock', 'price'],
                'rows' => Product::query()
                    ->when($search !== '', fn ($query) => $query->where(function ($searchQuery) use ($search): void {
                        SqlSafe::whereLike($searchQuery, 'name_en', $search);
                        SqlSafe::orWhereLike($searchQuery, 'sku', $search);
                    }))
                    ->latest('id')
                    ->limit(50)
                    ->get()
                    ->map(fn (Product $product) => [
                        'id' => $product->id,
                        'cells' => [$product->localizedName('en'), (string) $product->sku, (string) $product->brand, (string) $product->stock_quantity, number_format((float) $product->price, 0)],
                        'payload' => ['slug' => $product->slug],
                    ])->values(),
            ],
            'orders' => [
                'columns' => ['order', 'status', 'payment', 'city', 'total'],
                'rows' => Order::query()
                    ->latest('id')
                    ->limit(50)
                    ->get()
                    ->map(fn (Order $order) => [
                        'id' => $order->id,
                        'cells' => [$order->order_number, $order->status, $order->payment_status, (string) $order->delivery_city, number_format((float) ($order->grand_total ?? $order->total_amount), 0)],
                        'payload' => [],
                    ])->values(),
            ],
            'users', 'dealers' => [
                'columns' => ['name', 'email', 'role', 'status'],
                'rows' => User::query()
                    ->when($section === 'dealers', fn ($query) => $query->where('role', User::ROLE_DEALER))
                    ->when($search !== '', fn ($query) => $query->where(function ($searchQuery) use ($search): void {
                        SqlSafe::whereLike($searchQuery, 'name', $search);
                        SqlSafe::orWhereLike($searchQuery, 'email', $search);
                    }))
                    ->latest('id')
                    ->limit(50)
                    ->get()
                    ->map(fn (User $user) => [
                        'id' => $user->id,
                        'cells' => [$user->name, $user->email, $user->role, (string) ($user->dealer_status ?? 'active')],
                        'payload' => [],
                    ])->values(),
            ],
            'categories' => [
                'columns' => ['category', 'products', 'status'],
                'rows' => Category::query()->withCount('products')->limit(50)->get()->map(fn (Category $category) => [
                    'id' => $category->id,
                    'cells' => [$category->localizedName('en'), (string) $category->products_count, 'active'],
                    'payload' => [],
                ])->values(),
            ],
            'coupons' => [
                'columns' => ['code', 'type', 'value', 'status'],
                'rows' => Coupon::query()->latest('id')->limit(50)->get()->map(fn (Coupon $coupon) => [
                    'id' => $coupon->id,
                    'cells' => [$coupon->code, $coupon->type, (string) $coupon->value, $coupon->is_active ? 'active' : 'inactive'],
                    'payload' => [],
                ])->values(),
            ],
            'discount-rules' => [
                'columns' => ['name', 'scope', 'value', 'status'],
                'rows' => Discount::query()->latest('id')->limit(50)->get()->map(fn (Discount $discount) => [
                    'id' => $discount->id,
                    'cells' => [$discount->name, $discount->scope, (string) $discount->value, $discount->is_active ? 'active' : 'inactive'],
                    'payload' => [],
                ])->values(),
            ],
            default => ['columns' => ['name', 'type', 'status'], 'rows' => []],
        };

        return response()->json(['data' => $payload]);
    }

    public function adminUpdateProduct(Request $request, string $idOrSlug)
    {
        $this->requirePermission($request, User::PERMISSION_PRODUCTS_MANAGE);
        $product = $this->findProduct($idOrSlug);
        $data = $request->validate(['stock_quantity' => ['required', 'integer', 'min:0']]);
        $product->update(['stock_quantity' => (int) $data['stock_quantity']]);

        return response()->json(['data' => $this->productPayload($product->fresh(['category', 'images', 'reviews', 'vehicleFitments.brand', 'vehicleFitments.model']), $request->user())]);
    }

    public function adminUpdateOrderStatus(Request $request, Order $order)
    {
        $this->requirePermission($request, User::PERMISSION_ORDERS_MANAGE);
        $data = $request->validate(['status' => ['required', Rule::in(Order::allowedStatuses())]]);
        $order->forceFill(['status' => $data['status']])->save();

        return response()->json(['data' => $this->orderPayload($order->fresh('items.product.category', 'items.product.images', 'items.product.reviews', 'user'))]);
    }

    public function adminUpdateUserRole(Request $request, User $user)
    {
        $authUser = $this->requirePermission($request, User::PERMISSION_USERS_MANAGE);
        $data = $request->validate(['role' => ['required', Rule::in(User::allowedRoles())]]);
        $role = User::normalizeRole($data['role']);

        if (($user->isSuperAdmin() || $role === User::ROLE_SUPER_ADMIN) && ! $authUser->isSuperAdmin()) {
            abort(403);
        }

        if ((int) $authUser->id === (int) $user->id && $user->isSuperAdmin() && $role !== User::ROLE_SUPER_ADMIN) {
            abort(422, __('You cannot demote your own super admin account.'));
        }

        if (
            $user->isSuperAdmin()
            && $role !== User::ROLE_SUPER_ADMIN
            && User::query()->where('role', User::ROLE_SUPER_ADMIN)->count() <= 1
        ) {
            abort(422, __('At least one super admin account must remain.'));
        }

        $user->forceFill(['role' => $role, 'permissions' => User::defaultPermissionsForRole($role)])->save();

        return response()->json(['user' => $this->userPayload($user->fresh())]);
    }

    public function adminUpdateDealer(Request $request, User $user)
    {
        $this->requirePermission($request, User::PERMISSION_DEALERS_MANAGE);
        $data = $request->validate([
            'dealer_status' => ['required', Rule::in(User::allowedDealerStatuses())],
            'dealer_discount' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);
        $user->forceFill([
            'role' => User::ROLE_DEALER,
            'dealer_status' => $data['dealer_status'],
            'dealer_discount' => $data['dealer_discount'],
        ])->save();

        return response()->json(['user' => $this->userPayload($user->fresh())]);
    }

    public function adminCreateInventoryMovement(Request $request)
    {
        $this->requirePermission($request, User::PERMISSION_STOCK_MANAGE);
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'type' => ['required', Rule::in([InventoryMovement::TYPE_IN, InventoryMovement::TYPE_OUT])],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $product = Product::query()->findOrFail($data['product_id']);
        $before = (int) $product->stock_quantity;
        $after = $data['type'] === InventoryMovement::TYPE_IN
            ? $before + (int) $data['quantity']
            : max(0, $before - (int) $data['quantity']);
        $product->update(['stock_quantity' => $after]);
        InventoryMovement::query()->create([
            'product_id' => $product->id,
            'user_id' => $request->user()->id,
            'type' => $data['type'],
            'quantity' => (int) $data['quantity'],
            'stock_before' => $before,
            'stock_after' => $after,
            'reference' => 'mobile-admin',
            'note' => $data['note'] ?? null,
        ]);

        return response()->json(['data' => ['product_id' => $product->id, 'stock_quantity' => $after]], 201);
    }

    private function recordRecentlyViewedProduct(User $user, Product $product): void
    {
        RecentlyViewedProduct::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'product_id' => $product->id,
            ],
            ['viewed_at' => now()],
        );

        $staleIds = RecentlyViewedProduct::query()
            ->where('user_id', $user->id)
            ->latest('viewed_at')
            ->skip(60)
            ->limit(1000)
            ->pluck('id');

        if ($staleIds->isNotEmpty()) {
            RecentlyViewedProduct::query()->whereKey($staleIds)->delete();
        }
    }

    private function primaryImageUrl(Product $product): ?string
    {
        $firstImage = $product->relationLoaded('images') ? $product->images->first() : $product->images()->first();
        if ($firstImage) {
            return asset('storage/' . $firstImage->path);
        }

        return $product->image ? asset('storage/' . $product->image) : null;
    }

    private function productPayload(Product $product, ?User $user): array
    {
        $pricing = $product->pricingFor($user);
        $images = $product->images->map(fn ($image) => asset('storage/' . $image->path))->values()->all();
        if ($images === [] && $product->image) {
            $images[] = asset('storage/' . $product->image);
        }

        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'name' => $product->localizedName(),
            'description' => (string) $product->localizedDescription(),
            'category' => $product->category?->localizedName() ?? __('Uncategorized'),
            'brand' => (string) $product->brand,
            'sku' => (string) $product->sku,
            'oem_number' => (string) $product->oem_number,
            'part_number' => (string) $product->part_number,
            'price' => $pricing['price'],
            'base_price' => $pricing['base_price'],
            'dealer_price' => ($user && $user->isDealer()) ? $product->dealer_price : null,
            'discount_percent' => $pricing['discount_percent'],
            'stock_quantity' => (int) $product->stock_quantity,
            'low_stock_threshold' => (int) ($product->low_stock_threshold ?? 5),
            'compatible_models' => $product->vehicleFitments->map(fn ($fitment) => trim(($fitment->brand?->name ?? '') . ' ' . ($fitment->model?->name ?? '') . ' ' . ($fitment->year_from ?? '') . '-' . ($fitment->year_to ?? '')))->filter()->values()->all()
                ?: ($product->compatible_models ?? []),
            'images' => $images,
            'rating' => round((float) $product->reviews->avg('rating'), 1),
            'review_count' => $product->reviews->count(),
            'warranty' => (string) ($product->warranty ?? __('Warranty on request')),
            'is_active' => (bool) $product->is_active,
        ];
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => (string) $user->phone,
            'role' => $user->role,
            'email_verified' => $user->hasVerifiedEmail(),
            'permissions' => $user->effectivePermissions(),
            'dealer_status' => $user->dealer_status,
            'dealer_discount' => (float) $user->dealer_discount,
        ];
    }

    private function cartFor(User $user): Cart
    {
        return Cart::query()->firstOrCreate(['user_id' => $user->id])->load('items.product.category', 'items.product.images', 'items.product.reviews');
    }

    private function cartPayload(Cart $cart, ?User $user): array
    {
        return [
            'coupon_code' => null,
            'items' => $cart->items->map(fn (CartItem $item) => [
                'quantity' => (int) $item->quantity,
                'product' => $this->productPayload($item->product, $user),
            ])->values()->all(),
        ];
    }

    private function orderPayload(Order $order): array
    {
        return [
            'id' => $order->id,
            'number' => $order->order_number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'created_at' => optional($order->created_at)->toISOString(),
            'items' => $order->items->map(fn ($item) => [
                'quantity' => (int) $item->quantity,
                'product' => $this->productPayload($item->product, $order->user),
            ])->values()->all(),
            'subtotal' => (float) ($order->subtotal_amount ?? $order->items->sum('subtotal')),
            'shipping_fee' => (float) ($order->shipping_fee ?? 0),
            'discount_amount' => (float) ($order->discount_amount ?? 0),
            'total' => (float) ($order->grand_total ?? $order->total_amount),
            'delivery_city' => (string) $order->delivery_city,
        ];
    }

    private function addressData(Request $request): array
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20', new PhoneNumber()],
            'is_default' => ['nullable', 'boolean'],
        ]);

        return [
            'label' => $data['label'],
            'country' => 'IQ',
            'city' => $data['city'],
            'address_line1' => $data['line1'],
            'address_line2' => $data['line2'] ?? null,
            'phone' => $data['phone'] ?? null,
            'is_default' => (bool) ($data['is_default'] ?? false),
        ];
    }

    private function syncDefaultAddress(Request $request, UserAddress $address): void
    {
        if ($address->is_default) {
            $request->user()->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
        }
    }

    private function resolveOrderAddress(User $user, ?int $addressId): ?UserAddress
    {
        return $user->addresses()->whereKey($addressId)->first()
            ?: $user->addresses()->where('is_default', true)->first()
            ?: $user->addresses()->first();
    }

    private function abortIfAddressPhoneInvalid(UserAddress $address): void
    {
        $validator = ValidatorFacade::make(
            ['phone' => $address->phone],
            ['phone' => ['required', new PhoneNumber()]],
        );

        abort_if(
            $validator->fails(),
            422,
            __('validation.phone', ['attribute' => 'delivery phone']),
        );
    }

    private function addressPayload(UserAddress $address): array
    {
        $data = [
            'id' => $address->id,
            'label' => $address->label,
            'city' => $address->city,
            'line1' => $address->address_line1,
            'line2' => (string) $address->address_line2,
            'phone' => (string) $address->phone,
            'is_default' => (bool) $address->is_default,
        ];

        return $data;
    }

    private function requireAdmin(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && $user->isAdminPanelUser(), 403);
    }

    private function requirePermission(Request $request, string $permission): User
    {
        $user = $request->user();
        abort_unless($user && $user->hasPermission($permission), 403);

        return $user;
    }

    private function permissionForAdminSection(string $section): ?string
    {
        return match ($section) {
            'products', 'categories' => User::PERMISSION_PRODUCTS_MANAGE,
            'inventory' => User::PERMISSION_STOCK_MANAGE,
            'orders' => User::PERMISSION_ORDERS_MANAGE,
            'users' => User::PERMISSION_USERS_VIEW,
            'dealers' => User::PERMISSION_DEALERS_MANAGE,
            'coupons', 'discount-rules' => User::PERMISSION_FINANCE_MANAGE,
            default => null,
        };
    }

    private function requireDealer(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && ($user->isDealer() || $user->isAdminPanelUser()), 403);
    }

    private function dealerProductsQuery(User $user)
    {
        $query = Product::query();
        if ($user->isAdminPanelUser()) {
            return $query;
        }

        return Schema::hasColumn('products', 'created_by')
            ? $query->where('created_by', $user->id)
            : $query->whereRaw('1 = 0');
    }

    private function userForLogin(string $login): ?User
    {
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return User::query()
                ->where('email', $login)
                ->first();
        }

        $normalizedPhone = User::normalizePhone($login);
        if ($normalizedPhone === null) {
            return null;
        }

        return User::query()
            ->where('phone_normalized', $normalizedPhone)
            ->first();
    }

    private function debugLoginFailure(string $reason, string $login, ?User $user = null): void
    {
        if (! config('app.debug')) {
            return;
        }

        Log::debug('Mobile login failed', [
            'reason' => $reason,
            'login_hash' => hash('sha256', strtolower($login)),
            'user_id' => $user?->id,
            'role' => $user?->role,
        ]);
    }

    private function dealerOrdersQuery(User $user)
    {
        $query = Order::query();
        if ($user->isAdminPanelUser()) {
            return $query;
        }

        if (Schema::hasColumn('products', 'created_by')) {
            return $query->whereHas('items.product', fn ($product) => $product->where('created_by', $user->id));
        }

        return $query->whereRaw('1 = 0');
    }

    private function vinManufacturer(string $vin): string
    {
        $prefix = substr($vin, 0, 3);

        return match (true) {
            str_starts_with($prefix, 'JT') => 'Toyota',
            str_starts_with($prefix, 'KM') => 'Hyundai',
            str_starts_with($prefix, 'KN') => 'Kia',
            str_starts_with($prefix, 'WBA'), str_starts_with($prefix, 'WBS') => 'BMW',
            str_starts_with($prefix, 'WDB'), str_starts_with($prefix, 'WDD') => 'Mercedes',
            str_starts_with($prefix, 'JN') => 'Nissan',
            str_starts_with($prefix, 'KL'), str_starts_with($prefix, '1G') => 'Chevrolet',
            str_starts_with($prefix, 'KPT'), str_starts_with($prefix, 'KPA') => 'SsangYong',
            default => 'Unknown',
        };
    }

    private function vinYear(string $vin): int
    {
        $code = substr($vin, 9, 1);
        $years = [
            '1' => 2001, '2' => 2002, '3' => 2003, '4' => 2004, '5' => 2005,
            '6' => 2006, '7' => 2007, '8' => 2008, '9' => 2009,
            'A' => 2010, 'B' => 2011, 'C' => 2012, 'D' => 2013, 'E' => 2014,
            'F' => 2015, 'G' => 2016, 'H' => 2017, 'J' => 2018, 'K' => 2019,
            'L' => 2020, 'M' => 2021, 'N' => 2022, 'P' => 2023, 'R' => 2024,
            'S' => 2025, 'T' => 2026,
        ];

        return $years[$code] ?? 0;
    }

    private function vinModelGuess(string $manufacturer): string
    {
        return match ($manufacturer) {
            'Toyota' => 'Corolla',
            'Hyundai' => 'Elantra',
            'Kia' => 'Sportage',
            'BMW' => '3 Series',
            'Mercedes' => 'C-Class',
            'Nissan' => 'Altima',
            'Chevrolet' => 'Tahoe',
            'SsangYong' => 'Korando',
            default => 'Unknown',
        };
    }

    private function findProduct(string $idOrSlug): Product
    {
        return Product::query()
            ->with(['category', 'images', 'reviews', 'vehicleFitments.brand', 'vehicleFitments.model'])
            ->where(function ($query) use ($idOrSlug): void {
                $query->where('slug', $idOrSlug);

                if (ctype_digit($idOrSlug)) {
                    $query->orWhere('id', (int) $idOrSlug);
                }
            })
            ->firstOrFail();
    }
}
