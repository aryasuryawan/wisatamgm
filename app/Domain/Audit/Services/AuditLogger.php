<?php

namespace App\Domain\Audit\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public static function log(string $action, ?Model $model = null, ?array $before = null, ?array $after = null): AuditLog
    {
        /** @var User|null $user */
        $user = auth()->user();

        return AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'model_type' => $model ? $model::class : null,
            'model_id' => $model?->getKey(),
            'before' => $before,
            'after' => $after,
            'ip_address' => request()?->ip(),
        ]);
    }
}
