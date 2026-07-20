<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;

class AuditLogger
{
    public static function log(
        ?int $actorId,
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $before = null,
        ?array $after = null
    ): void {
        try {
            AuditLog::create([
                'actor_id' => $actorId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'before' => $before,
                'after' => $after,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to write audit log: ' . $e->getMessage(), [
                'actor_id' => $actorId,
                'action' => $action,
            ]);
        }
    }
}
