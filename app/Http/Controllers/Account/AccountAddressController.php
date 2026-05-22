<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\StoreAddressRequest;
use App\Http\Requests\Account\UpdateAddressRequest;
use App\Models\UserAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountAddressController extends Controller
{
    public function index(): View
    {
        return view('account.addresses.index', [
            'addresses' => auth()->user()->addresses()->latest('is_default')->latest('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('account.addresses.create', [
            'address' => new UserAddress(),
        ]);
    }

    public function store(StoreAddressRequest $request): RedirectResponse
    {
        $address = new UserAddress($this->validatedAddressData($request));
        $address->user()->associate($request->user());
        $address->save();

        return redirect()
            ->route('account.addresses.index')
            ->with('status', __('Address added successfully.'));
    }

    public function edit(UserAddress $address): View
    {
        $this->authorizeAddress($address);

        return view('account.addresses.edit', [
            'address' => $address,
        ]);
    }

    public function update(UpdateAddressRequest $request, UserAddress $address): RedirectResponse
    {
        $this->authorizeAddress($address);

        $address->fill($this->validatedAddressData($request));
        $address->save();

        return redirect()
            ->route('account.addresses.index')
            ->with('status', __('Address updated successfully.'));
    }

    public function setDefault(Request $request, UserAddress $address): RedirectResponse
    {
        $this->authorizeAddress($address);

        $address->forceFill(['is_default' => true])->save();

        return redirect()
            ->route('account.addresses.index')
            ->with('status', __('Default address updated.'));
    }

    public function destroy(UserAddress $address): RedirectResponse
    {
        $this->authorizeAddress($address);

        $wasDefault = $address->is_default;
        $user = $address->user;

        $address->delete();

        if ($wasDefault) {
            $fallback = $user->addresses()->oldest('id')->first();

            if ($fallback) {
                $fallback->forceFill(['is_default' => true])->save();
            }
        }

        return redirect()
            ->route('account.addresses.index')
            ->with('status', __('Address removed successfully.'));
    }

    protected function authorizeAddress(UserAddress $address): void
    {
        abort_unless($address->user_id === auth()->id(), 404);
    }

    protected function validatedAddressData(StoreAddressRequest|UpdateAddressRequest $request): array
    {
        $data = $request->validated();
        $data['is_default'] = $request->boolean('is_default');

        return $data;
    }
}
