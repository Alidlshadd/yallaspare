<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Governorate;
use App\Rules\IraqiMobileNumber;
use App\Support\AdminLogger;
use App\Support\IraqiPhoneNumber;
use App\Support\SqlSafe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'string', 'max:32', new IraqiMobileNumber],
            'whatsapp' => ['nullable', 'string', 'max:32', new IraqiMobileNumber],
            'city' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        // 0770…, 770… and +964770… all become the one stored form.
        $data['phone'] = IraqiPhoneNumber::toE164($data['phone']);
        $data['whatsapp'] = filled($data['whatsapp'] ?? null) ? IraqiPhoneNumber::toE164($data['whatsapp']) : null;
        $data['name'] = trim($data['name']);

        return $data;
    }

    /**
     * @param  Builder<Customer>  $query
     */
    private function applySearch(Builder $query, string $search): void
    {
        $term = SqlSafe::searchTerm($search);
        // A number is stored as +9647…; someone searching types 0770… or 770….
        $digits = ltrim(preg_replace('/\D+/', '', $search) ?? '', '0');
        $digits = str_starts_with($digits, '964') ? substr($digits, 3) : $digits;

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
