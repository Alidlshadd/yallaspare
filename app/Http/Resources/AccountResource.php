<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * What an account may be told about itself over the API.
 *
 * GET /api/user used to return the user model as it stood, which is every
 * column the table has or ever gains: the staff's private notes and ban
 * reason, stored permissions, the Google and Apple account ids, internal
 * flags. The reader was only ever the account's own holder, but a list of
 * columns nobody chose is not an API, and the next sensitive column added to
 * the table would have been published by default.
 *
 * This is the list that was chosen. A field is sent because a client needs
 * it; anything not named here stays on the server.
 *
 * @mixin User
 */
class AccountResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'phone_verified_at' => $this->phone_verified_at?->toISOString(),
            'locale_preference' => $this->locale_preference,
            'theme_preference' => $this->resource->getAttribute('theme_preference'),
            'dealer_status' => $this->dealer_status,
            'dealer_discount' => (float) $this->dealer_discount,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
