<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\PhoneNumber;
use App\Support\SecureImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('admin.profile.edit', [
            'user' => $user,
            'effectivePermissions' => $user->effectivePermissions(),
            'permissionGroups' => User::permissionGroups(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30', new PhoneNumber(), User::uniquePhoneRule($user->id)],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
            'remove_profile_photo' => ['sometimes', 'boolean'],
        ]);

        $profilePhotoPath = $user->profile_photo_path;

        if ($request->boolean('remove_profile_photo')) {
            if (! empty($profilePhotoPath)) {
                Storage::disk('public')->delete($profilePhotoPath);
            }

            $profilePhotoPath = null;
        }

        if ($request->hasFile('profile_photo')) {
            if (! empty($profilePhotoPath)) {
                Storage::disk('public')->delete($profilePhotoPath);
            }

            $profilePhotoPath = SecureImageStorage::store($request->file('profile_photo'), 'users/profile-photos');
        }

        $user->fill([
            'name' => $data['name'],
            'email' => strtolower(trim($data['email'])),
            'phone' => filled($data['phone'] ?? null) ? trim((string) $data['phone']) : null,
            'profile_photo_path' => $profilePhotoPath,
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('admin.profile.edit')->with('status', __('profile-updated'));
    }
}
