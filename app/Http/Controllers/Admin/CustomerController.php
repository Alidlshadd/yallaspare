<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Governorate;
use App\Support\AdminLogger;
use App\Support\InternationalPhone;
use App\Support\SqlSafe;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The customer directory: people the shop invoices by hand.
 *
 * Saving someone here creates no site account and sends them nothing.
 */
class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $customers = Customer::query()
            ->withCount('invoices')
            ->when($search !== '', fn (Builder $query) => $this->applySearch($query, $search))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.customers.index', compact('customers', 'search'));
    }

    /**
     * The picker on the invoice form: a few matches by name or number.
     */
    public function search(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        $customers = Customer::query()
            ->when($search !== '', fn (Builder $query) => $this->applySearch($query, $search))
            ->orderBy('name')
            ->limit(10)
            ->get();

        return response()->json([
            'data' => $customers->map(fn (Customer $customer) => $this->present($customer))->values(),
        ]);
    }

    public function create(): View
    {
        return view('admin.customers.create', ['cities' => $this->cities()]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validated($request);

        // One number, one customer. Typing it again is far more often a
        // mistake than a second person, so the existing entry is handed back
        // untouched rather than duplicated or silently overwritten.
        $existing = Customer::query()->where('phone', $data['phone'])->first();

        if ($existing) {
            if ($request->expectsJson()) {
                return response()->json([
                    'data' => $this->present($existing),
                    'existing' => true,
                    'message' => __('This phone number already belongs to :name. The existing customer was selected.', ['name' => $existing->name]),
                ]);
            }

            return redirect()
                ->route('admin.customers.edit', $existing)
                ->with('warning', __('This phone number already belongs to :name. No new customer was created.', ['name' => $existing->name]));
        }

        $customer = Customer::query()->create($data + ['created_by' => $request->user()?->id]);

        AdminLogger::log('customer.created', $customer, ['name' => $customer->name]);

        if ($request->expectsJson()) {
            return response()->json(['data' => $this->present($customer), 'existing' => false], 201);
        }

        return redirect()
            ->route('admin.customers.index')
            ->with('success', __('Customer saved.'));
    }

    public function edit(Customer $customer): View
    {
        $invoices = $customer->invoices()->latest('id')->limit(10)->get();

        return view('admin.customers.edit', [
            'customer' => $customer,
            'invoices' => $invoices,
            'cities' => $this->cities(),
        ]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $data = $this->validated($request);

        $owner = Customer::query()->where('phone', $data['phone'])->whereKeyNot($customer->id)->first();

        if ($owner) {
            throw ValidationException::withMessages([
                'phone' => __('This phone number already belongs to :name.', ['name' => $owner->name]),
            ]);
        }

        $customer->update($data);

        AdminLogger::log('customer.updated', $customer, ['name' => $customer->name]);

        return redirect()
            ->route('admin.customers.index')
            ->with('success', __('Customer updated.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $countries = array_keys(InternationalPhone::countries());
        $phoneCountry = $this->countryFrom($request->input('phone_country'));
        $whatsappCountry = $this->countryFrom($request->input('whatsapp_country'), $phoneCountry);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'string', 'max:32', $this->validPhoneFor($phoneCountry)],
            'phone_country' => ['nullable', 'string', Rule::in($countries)],
            'whatsapp' => ['nullable', 'string', 'max:32', $this->validPhoneFor($whatsappCountry)],
            'whatsapp_country' => ['nullable', 'string', Rule::in($countries)],
            'country' => ['nullable', 'string', Rule::in($countries)],
            'city' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        // Each number is read by its own country's rules and stored in full:
        // 0770… with Iraq and +964770… are one number, and 0532… with Türkiye
        // becomes +90532…, never +964….
        $data['phone'] = InternationalPhone::toE164($data['phone'], $phoneCountry);
        $data['whatsapp'] = filled($data['whatsapp'] ?? null)
            ? InternationalPhone::toE164($data['whatsapp'], $whatsappCountry)
            : null;
        $data['country'] = $data['country'] ?? $phoneCountry;
        $data['name'] = trim($data['name']);

        unset($data['phone_country'], $data['whatsapp_country']);

        return $data;
    }

    private function countryFrom(mixed $value, string $fallback = InternationalPhone::DEFAULT_COUNTRY): string
    {
        return InternationalPhone::isKnownCountry($value) ? strtoupper((string) $value) : $fallback;
    }

    /**
     * A rule rather than a check after validation, so a bad number is reported
     * alongside the form's other mistakes instead of one round later.
     */
    private function validPhoneFor(string $country): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($country): void {
            if (InternationalPhone::toE164($value, $country) === null) {
                $fail(__('This is not a valid phone number for :country. Check the number, or type it in full starting with + and the country code.', [
                    'country' => InternationalPhone::countryName($country),
                ]));
            }
        };
    }

    /**
     * @param  Builder<Customer>  $query
     */
    private function applySearch(Builder $query, string $search): void
    {
        $term = SqlSafe::searchTerm($search);
        // A number is stored as +9647…; someone searching types 0770…, 770…
        // or the whole thing. Dropping the leading zeros makes all three a
        // substring of what is stored, for any country.
        $digits = ltrim(preg_replace('/\D+/', '', $search) ?? '', '0');

        $query->where(function (Builder $nested) use ($term, $digits): void {
            SqlSafe::whereLike($nested, 'name', $term);
            SqlSafe::orWhereLike($nested, 'city', $term);

            if (strlen($digits) >= 3) {
                SqlSafe::orWhereLike($nested, 'phone', $digits);
                SqlSafe::orWhereLike($nested, 'whatsapp', $digits);
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'whatsapp' => $customer->whatsapp,
            'country' => $customer->country,
            'country_name' => InternationalPhone::countryName($customer->country),
            'city' => $customer->city,
            'address' => $customer->address,
        ];
    }

    /**
     * Suggestions for the city field. Free text is still accepted: the shop
     * serves towns that are not a governorate.
     *
     * @return array<int, string>
     */
    private function cities(): array
    {
        return Governorate::query()->orderBy('name_en')->get()
            ->map(fn (Governorate $governorate): string => (string) ($governorate->{'name_'.app()->getLocale()} ?: $governorate->name_en))
            ->filter()
            ->values()
            ->all();
    }
}
