<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClubMembership;
use App\Models\ClubMembershipRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MembershipManagementController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = in_array((int) $request->integer('per_page', 10), [10, 25, 50, 100], true)
            ? (int) $request->integer('per_page', 10) : 10;

        $sort = in_array($request->string('sort', 'created_at')->toString(), ['membership_number', 'status', 'created_at'], true)
            ? $request->string('sort', 'created_at')->toString() : 'created_at';

        $direction = in_array($request->string('direction', 'desc')->toString(), ['asc', 'desc'], true)
            ? $request->string('direction', 'desc')->toString() : 'desc';

        $search = trim($request->string('search')->toString());
        $status = trim($request->string('status')->toString());
        $clubId = (int) $request->integer('club_id');

        $clubs = User::query()
            ->where('role', 'club')
            ->orderBy('club_name')
            ->get(['id', 'club_name', 'name']);

        $memberships = ClubMembership::query()
            ->with(['club:id,club_name,name', 'player:id,name,email'])
            ->when($search !== '', function (Builder $q) use ($search) {
                $q->where(function (Builder $sub) use ($search) {
                    $sub->where('membership_number', 'like', "%{$search}%")
                        ->orWhereHas('player', function (Builder $playerQuery) use ($search) {
                            $playerQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('club', function (Builder $clubQuery) use ($search) {
                            $clubQuery->where('club_name', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== '', fn (Builder $q) => $q->where('status', $status))
            ->when($clubId > 0, fn (Builder $q) => $q->where('club_id', $clubId))
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return view('content.admin.memberships.index', compact('memberships', 'clubs', 'search', 'status', 'clubId', 'sort', 'direction', 'perPage'));
    }

    public function destroy(ClubMembership $membership): RedirectResponse
    {
        $membership->update([
            'status' => ClubMembership::STATUS_REMOVED,
            'removed_at' => now(),
            'removal_reason' => 'Removed by system administrator.',
        ]);

        return redirect()->route('admin.memberships.index')->with('success', 'Club membership removed successfully.');
    }

    public function requestsIndex(Request $request): View
    {
        $perPage = in_array((int) $request->integer('per_page', 10), [10, 25, 50, 100], true)
            ? (int) $request->integer('per_page', 10) : 10;

        $sort = in_array($request->string('sort', 'created_at')->toString(), ['status', 'created_at'], true)
            ? $request->string('sort', 'created_at')->toString() : 'created_at';

        $direction = in_array($request->string('direction', 'desc')->toString(), ['asc', 'desc'], true)
            ? $request->string('direction', 'desc')->toString() : 'desc';

        $search = trim($request->string('search')->toString());
        $status = trim($request->string('status')->toString());
        $clubId = (int) $request->integer('club_id');

        $clubs = User::query()
            ->where('role', 'club')
            ->orderBy('club_name')
            ->get(['id', 'club_name', 'name']);

        $requests = ClubMembershipRequest::query()
            ->with(['club:id,club_name,name', 'player:id,name,email'])
            ->when($search !== '', function (Builder $q) use ($search) {
                $q->where(function (Builder $sub) use ($search) {
                    $sub->where('membership_number', 'like', "%{$search}%")
                        ->orWhereHas('player', function (Builder $playerQuery) use ($search) {
                            $playerQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('club', function (Builder $clubQuery) use ($search) {
                            $clubQuery->where('club_name', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== '', fn (Builder $q) => $q->where('status', $status))
            ->when($clubId > 0, fn (Builder $q) => $q->where('club_id', $clubId))
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return view('content.admin.memberships.requests', compact('requests', 'clubs', 'search', 'status', 'clubId', 'sort', 'direction', 'perPage'));
    }
}
