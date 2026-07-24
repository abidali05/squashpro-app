<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = in_array((int) $request->integer('per_page', 10), [10, 25, 50, 100], true)
            ? (int) $request->integer('per_page', 10) : 10;

        $search = trim($request->string('search')->toString());
        $actionFilter = trim($request->string('action_filter')->toString());

        $query = AuditLog::query()
            ->with(['actor:id,name,role'])
            ->when($search !== '', function (Builder $q) use ($search) {
                $q->where(function (Builder $sub) use ($search) {
                    $sub->where('action', 'like', "%{$search}%")
                        ->orWhere('entity_type', 'like', "%{$search}%")
                        ->orWhereHas('actor', function (Builder $actorQuery) use ($search) {
                            $actorQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($actionFilter !== '', fn (Builder $q) => $q->where('action', $actionFilter))
            ->orderBy('created_at', 'desc');

        $logs = $query->paginate($perPage)->withQueryString();

        // Get unique action types for filter dropdown
        $actionsList = AuditLog::query()
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->toArray();

        return view('content.admin.audit_logs.index', compact(
            'logs',
            'actionsList',
            'search',
            'actionFilter',
            'perPage'
        ));
    }
}
