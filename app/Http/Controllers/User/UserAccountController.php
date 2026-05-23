<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\Order;
use App\Support\SecureImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class UserAccountController extends Controller
{
    public function edit(): View
    {
        return view('user.account', $this->buildAccountViewData());
    }

    public function personal(): View
    {
        return view('user.account-personal', $this->buildAccountViewData());
    }

    public function securityPage(): View
    {
        return view('user.account-security', $this->buildAccountViewData());
    }

    public function addressesPage(): View
    {
        return view('user.account-addresses', $this->buildAccountViewData());
    }

    public function activity(): View
    {
        return view('user.account-activity', $this->buildAccountViewData());
    }

    public function actions(): View
    {
        return view('user.account-actions', $this->buildAccountViewData());
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $fullName = trim(implode(' ', array_filter([
            $data['first_name'] ?? null,
            $data['last_name'] ?? null,
        ])));

        $dateOfBirth = null;
        if (isset($data['dob_day'], $data['dob_month'], $data['dob_year'])) {
            $dateOfBirth = sprintf(
                '%04d-%02d-%02d',
                (int) $data['dob_year'],
                (int) $data['dob_month'],
                (int) $data['dob_day']
            );
        }

        $profilePhotoPath = $user->profile_photo_path;
        if ($request->boolean('remove_profile_photo')) {
            if (!empty($profilePhotoPath)) {
                Storage::disk('public')->delete($profilePhotoPath);
            }
            $profilePhotoPath = null;
        }

        if ($request->hasFile('profile_photo')) {
            if (!empty($profilePhotoPath)) {
                Storage::disk('public')->delete($profilePhotoPath);
            }
            $profilePhotoPath = SecureImageStorage::store($request->file('profile_photo'), 'users/profile-photos');
        }

        $user->forceFill([
            'name' => $fullName,
            'phone' => $data['phone'] ?? null,
            'date_of_birth' => $dateOfBirth,
            'profile_photo_path' => $profilePhotoPath,
        ])->save();

        $address = $user->addresses()
            ->where('is_default', true)
            ->first() ?? $user->addresses()->latest('id')->first();

        $addressPayload = [
            'label' => __('user.default_delivery'),
            'country' => $data['country'],
            'city' => $data['city'],
            'address_line1' => $data['address_line1'],
            'address_line2' => $data['address_line2'] ?? null,
            'phone' => $data['phone'] ?? null,
            'is_default' => true,
            'notes' => $data['notes'] ?? null,
        ];

        if ($address) {
            $address->update($addressPayload);
        } else {
            $user->addresses()->create($addressPayload);
        }

        return redirect()
            ->route('user.account.edit')
            ->with('success', __('user.profile_updated'));
    }

    public function password(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => Hash::make($request->validated()['password']),
        ]);

        return redirect()
            ->route('user.account.edit')
            ->with('password_success', __('user.password_updated'));
    }

    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $firstName = array_shift($parts) ?? '';
        $lastName = implode(' ', $parts);

        return [$firstName, $lastName];
    }

    private function buildAccountViewData(): array
    {
        $user = auth()->user();
        [$firstName, $lastName] = $this->splitName((string) $user->name);
        $addresses = $user->addresses()
            ->latest('is_default')
            ->latest('id')
            ->get();
        $address = $addresses->firstWhere('is_default', true) ?? $addresses->first();
        $recentOrders = $user->orders()
            ->latest('id')
            ->take(5)
            ->get();
        $totalOrders = $user->orders()->count();
        $pendingOrders = $user->orders()->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_PROCESSING])->count();
        $deliveredOrders = $user->orders()->where('status', Order::STATUS_DELIVERED)->count();
        $totalSpend = (float) $user->orders()->sum('total_amount');

        return [
            'user' => $user,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'address' => $address,
            'addresses' => $addresses,
            'recentOrders' => $recentOrders,
            'totalOrders' => $totalOrders,
            'pendingOrders' => $pendingOrders,
            'deliveredOrders' => $deliveredOrders,
            'totalSpend' => $totalSpend,
        ];
    }
}
