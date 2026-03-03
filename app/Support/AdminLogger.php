<?php

namespace App\Support;

use App\Models\AdminActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AdminLogger
{
    public static function log(string $action, mixed $subject = null, array $meta = []): void
    {
        try {
            $subjectType = null;
            $subjectId = null;

            if ($subject instanceof Model) {
                $subjectType = $subject->getMorphClass();
                $subjectId = $subject->getKey();
            } elseif (is_array($subject)) {
                $subjectType = $subject['type'] ?? null;
                $subjectId = $subject['id'] ?? null;
            }

            AdminActivityLog::query()->create([
                'user_id' => Auth::id(),
                'action' => $action,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'meta' => $meta,
            ]);
        } catch (\Throwable $e) {
            // fail silently
        }
    }
}
