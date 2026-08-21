<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingReviewManagementController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = in_array((int) $request->integer('per_page', 10), [10, 25, 50, 100], true)
            ? (int) $request->integer('per_page', 10) : 10;

        $sort = in_array($request->string('sort', 'created_at')->toString(), ['rating', 'created_at'], true)
            ? $request->string('sort', 'created_at')->toString() : 'created_at';

        $direction = in_array($request->string('direction', 'desc')->toString(), ['asc', 'desc'], true)
            ? $request->string('direction', 'desc')->toString() : 'desc';

        $search = trim($request->string('search')->toString());
        $rating = $request->input('rating');
        $clubId = (int) $request->integer('club_id');

        $clubs = User::query()
            ->where('role', 'club')
            ->orderBy('club_name')
            ->get(['id', 'club_name', 'name']);

        $reviews = BookingReview::query()
            ->with(['player:id,name,email', 'club:id,club_name,name', 'court:id,name'])
            ->when($search !== '', function (Builder $q) use ($search) {
                $q->where(function (Builder $sub) use ($search) {
                    $sub->where('review', 'like', "%{$search}%")
                        ->orWhereHas('player', function (Builder $playerQuery) use ($search) {
                            $playerQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('club', function (Builder $clubQuery) use ($search) {
                            $clubQuery->where('club_name', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when(filled($rating), fn (Builder $q) => $q->where('rating', (int) $rating))
            ->when($clubId > 0, fn (Builder $q) => $q->where('club_id', $clubId))
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return view('content.admin.reviews.index', compact(
            'reviews',
            'clubs',
            'search',
            'rating',
            'clubId',
            'sort',
            'direction',
            'perPage'
        ));
    }

    public function destroy(BookingReview $review): RedirectResponse
    {
        $review->delete();

        return redirect()->route('admin.booking-reviews.index')->with('success', 'Booking review deleted successfully.');
    }
}
