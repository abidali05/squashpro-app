<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\TournamentRegistration;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $perPage = $request->integer('per_page', 10);
        $sort = $request->string('sort', 'created_at')->toString();
        $direction = $request->string('direction', 'desc')->toString();
        $type = $request->string('type', 'booking')->toString();

        if ($type === 'tournament') {
            $query = TournamentRegistration::with(['player', 'tournament.club']);

            if (filled($search)) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('player', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })->orWhereHas('tournament', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%");
                    })->orWhere('payment_method_id', 'like', "%{$search}%");
                });
            }

            if (in_array($sort, ['amount', 'payment_status', 'created_at'], true)) {
                $query->orderBy($sort, $direction);
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $payments = $query->paginate($perPage)->withQueryString();
        } else {
            // Default: booking payments
            $query = Booking::with(['player', 'club', 'court']);

            if (filled($search)) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('player', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })->orWhereHas('club', function ($sub) use ($search) {
                        $sub->where('club_name', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    })->orWhere('payment_transaction_id', 'like', "%{$search}%")
                      ->orWhere('payment_method', 'like', "%{$search}%");
                });
            }

            if (in_array($sort, ['total_amount', 'payment_status', 'created_at'], true)) {
                $query->orderBy($sort, $direction);
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $payments = $query->paginate($perPage)->withQueryString();
        }

        return view('content.admin.payments.index', compact(
            'payments',
            'type',
            'search',
            'perPage',
            'sort',
            'direction'
        ));
    }
}
