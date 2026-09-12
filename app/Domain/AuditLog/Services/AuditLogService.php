<?php

declare(strict_types=1);

namespace App\Domain\AuditLog\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * @domain AuditLog
 */
class AuditLogService
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function record(string $module, string $action, ?Model $subject = null, array $before = [], array $after = []): AuditLog
    {
        return AuditLog::query()->create([
            'user_id' => Auth::id(),
            'module' => $module,
            'action' => $action,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'before' => $before === [] ? null : $before,
            'after' => $after === [] ? null : $after,
            'ip_address' => Request::ip(),
        ]);
    }
}
