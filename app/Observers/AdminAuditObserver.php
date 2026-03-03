<?php

namespace App\Observers;

use App\Models\AdminActivityLog;
use App\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AdminAuditObserver
{
    public function created(Model $model): void
    {
        $this->log($model, $this->actionPrefix($model) . '.created', [
            'new' => $this->filterAttributes($model->getAttributes()),
            'changed' => array_keys($model->getAttributes()),
        ]);
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        if (empty($changes)) {
            return;
        }

        if ($model instanceof Order && array_keys($changes) === ['status']) {
            return;
        }

        $old = [];
        foreach ($changes as $key => $_value) {
            $old[$key] = $model->getOriginal($key);
        }

        $this->log($model, $this->actionPrefix($model) . '.updated', [
            'old' => $this->filterAttributes($old),
            'new' => $this->filterAttributes($changes),
            'changed' => array_keys($changes),
        ]);
    }

    public function deleted(Model $model): void
    {
        $this->log($model, $this->actionPrefix($model) . '.deleted', [
            'old' => $this->filterAttributes($model->getAttributes()),
            'changed' => array_keys($model->getAttributes()),
        ]);
    }

    public function restored(Model $model): void
    {
        $this->log($model, $this->actionPrefix($model) . '.restored', [
            'new' => $this->filterAttributes($model->getAttributes()),
            'changed' => array_keys($model->getAttributes()),
        ]);
    }

    private function log(Model $model, string $action, array $meta = []): void
    {
        try {
            AdminActivityLog::query()->create([
                'user_id' => Auth::id(),
                'action' => $action,
                'subject_type' => $model->getMorphClass(),
                'subject_id' => $model->getKey(),
                'meta' => $meta,
            ]);
        } catch (\Throwable $e) {
            // fail silently
        }
    }

    private function actionPrefix(Model $model): string
    {
        $short = class_basename($model);

        return match ($short) {
            'Category' => 'catalog',
            'Product' => 'product',
            'User' => 'user',
            'Order' => 'order',
            default => strtolower($short),
        };
    }

    private function filterAttributes(array $attributes): array
    {
        $exclude = ['password', 'remember_token'];
        foreach ($exclude as $key) {
            unset($attributes[$key]);
        }

        return $attributes;
    }
}
