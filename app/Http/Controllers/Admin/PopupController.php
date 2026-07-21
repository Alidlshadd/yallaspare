<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Popup;
use App\Support\SecureImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PopupController extends Controller
{
    public function index(): View
    {
        $popups = Popup::query()->latest('id')->paginate(20);

        return view('admin.popups.index', compact('popups'));
    }

    public function create(): View
    {
        return view('admin.popups.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            $data['image_path'] = SecureImageStorage::store($request->file('image'), 'popups');
        }

        Popup::create($data);

        return redirect()
            ->route('admin.popups.index')
            ->with('success', __('Popup created successfully.'));
    }

    public function edit(Popup $popup): View
    {
        return view('admin.popups.edit', compact('popup'));
    }

    public function update(Request $request, Popup $popup): RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            $this->deleteImage($popup);
            $data['image_path'] = SecureImageStorage::store($request->file('image'), 'popups');
        } elseif ($request->boolean('remove_image')) {
            $this->deleteImage($popup);
            $data['image_path'] = null;
        }

        $popup->update($data);

        return redirect()
            ->route('admin.popups.index')
            ->with('success', __('Popup updated successfully.'));
    }

    public function toggle(Popup $popup): RedirectResponse
    {
        $popup->update(['is_active' => ! $popup->is_active]);

        return redirect()
            ->route('admin.popups.index')
            ->with('success', $popup->is_active ? __('Popup activated.') : __('Popup deactivated.'));
    }

    public function destroy(Popup $popup): RedirectResponse
    {
        $this->deleteImage($popup);
        $popup->delete();

        return redirect()
            ->route('admin.popups.index')
            ->with('success', __('Popup deleted.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'title_en' => ['required', 'string', 'max:160'],
            'title_ar' => ['nullable', 'string', 'max:160'],
            'title_ku' => ['nullable', 'string', 'max:160'],
            'description_en' => ['nullable', 'string', 'max:1000'],
            'description_ar' => ['nullable', 'string', 'max:1000'],
            'description_ku' => ['nullable', 'string', 'max:1000'],
            'button_label_en' => ['nullable', 'string', 'max:60'],
            'button_label_ar' => ['nullable', 'string', 'max:60'],
            'button_label_ku' => ['nullable', 'string', 'max:60'],
            'button_url' => [
                'nullable',
                'string',
                'max:2048',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $url = self::sanitizeButtonUrl($value);

                    if ($url === '') {
                        return;
                    }

                    // Relative path (but not scheme-relative //host).
                    if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
                        return;
                    }

                    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

                    if (! in_array($scheme, ['http', 'https'], true)) {
                        $fail(__('This link type is not allowed.'));
                    }
                },
            ],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'remove_image' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'pages' => ['required', 'array', 'min:1'],
            'pages.*' => [Rule::in(Popup::PAGE_KEYS)],
            'frequency' => ['required', Rule::in(Popup::FREQUENCIES)],
            'frequency_days' => ['required_if:frequency,once_per_days', 'nullable', 'integer', 'min:1', 'max:365'],
            'delay_seconds' => ['required', 'integer', 'min:0', 'max:120'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['button_url'] = self::sanitizeButtonUrl($validated['button_url'] ?? null) ?: null;
        $validated['frequency_days'] = (int) ($validated['frequency_days'] ?? 7) ?: 7;
        $validated['pages'] = in_array('all', $validated['pages'], true) ? ['all'] : array_values($validated['pages']);

        unset($validated['image'], $validated['remove_image']);

        return $validated;
    }

    /**
     * Strip ASCII control characters so a scheme like "java\tscript:" cannot
     * be smuggled past validation, then trim. The stored value goes through
     * the same normalization as the validated one.
     */
    private static function sanitizeButtonUrl(mixed $value): string
    {
        return trim((string) preg_replace('/[\x00-\x1F\x7F]/', '', (string) $value));
    }

    private function deleteImage(Popup $popup): void
    {
        if (! empty($popup->image_path)) {
            Storage::disk('public')->delete($popup->image_path);
        }
    }
}
