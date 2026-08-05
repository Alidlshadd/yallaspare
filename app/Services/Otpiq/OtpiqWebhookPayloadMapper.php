<?php

namespace App\Services\Otpiq;

class OtpiqWebhookPayloadMapper
{
    /**
     * Extract only optional, commonly used fields. The complete payload remains
     * the source of truth when OTPiQ sends a different or newer schema.
     *
     * @return array{sender_phone: ?string, sender_name: ?string, external_message_id: ?string, message_type: ?string, message_text: ?string}
     */
    public function map(array $payload): array
    {
        return [
            'sender_phone' => $this->firstScalar($payload, [
                'sender_phone', 'sender.phone', 'from', 'message.from', 'data.from',
                'data.sender.phone', 'data.contact.phone', 'contacts.0.wa_id',
                'entry.0.changes.0.value.messages.0.from',
            ], 255),
            'sender_name' => $this->firstScalar($payload, [
                'sender_name', 'sender.name', 'contact.name', 'data.sender.name',
                'data.contact.name', 'contacts.0.profile.name',
                'entry.0.changes.0.value.contacts.0.profile.name',
            ], 255),
            'external_message_id' => $this->firstScalar($payload, [
                'external_message_id', 'message_id', 'message.id', 'data.message.id',
                'data.message_id', 'entry.0.changes.0.value.messages.0.id',
            ], 255),
            'message_type' => $this->firstScalar($payload, [
                'message_type', 'message.type', 'data.message.type', 'data.message_type',
                'entry.0.changes.0.value.messages.0.type',
            ], 255),
            'message_text' => $this->firstScalar($payload, [
                'message_text', 'message.text.body', 'message.text', 'message.body',
                'data.message.text.body', 'data.message.text', 'data.message.body', 'body',
                'entry.0.changes.0.value.messages.0.text.body',
            ], 16000),
        ];
    }

    private function firstScalar(array $payload, array $paths, int $maxCharacters): ?string
    {
        foreach ($paths as $path) {
            $value = data_get($payload, $path);

            if (! is_scalar($value)) {
                continue;
            }

            $value = trim((string) $value);

            if ($value !== '') {
                return mb_substr($value, 0, $maxCharacters);
            }
        }

        return null;
    }
}
