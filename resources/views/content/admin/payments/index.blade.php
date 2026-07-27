@extends('layouts/contentNavbarLayout')

@section('title', 'Payments Management')

@section('content')
<div class="mb-4">
    <div class="nav-align-top mb-4">
        <ul class="nav nav-pills mb-3" role="tablist">
            <li class="nav-item">
                <a href="{{ route('admin.payments.index', ['type' => 'booking']) }}" class="nav-link {{ $type === 'booking' ? 'active' : '' }}">
                    <i class="mdi mdi-calendar-check me-1"></i> Court Bookings
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.payments.index', ['type' => 'tournament']) }}" class="nav-link {{ $type === 'tournament' ? 'active' : '' }}">
                    <i class="mdi mdi-trophy me-1"></i> Tournament Registrations
                </a>
            </li>
        </ul>
    </div>
</div>

@if($type === 'tournament')
    @component('admin.components.datatable', [
        'title'       => 'Tournament Payments',
        'subtitle'    => 'Track payments and transaction histories for tournament entries',
        'paginator'   => $payments,
        'createUrl'   => null,
        'search'      => $search,
        'perPage'     => $perPage,
        'sort'        => $sort,
        'direction'   => $direction,
        'columns' => [
            ['label' => 'Player',              'sortable' => false],
            ['label' => 'Tournament',          'sortable' => false],
            ['label' => 'Payment Method',      'sortable' => false],
            ['label' => 'Payment Status',      'field' => 'payment_status', 'sortable' => true],
            ['label' => 'Amount',              'field' => 'amount', 'sortable' => true],
            ['label' => 'Paid At',             'field' => 'created_at', 'sortable' => true],
        ],
    ])
        @forelse($payments as $payment)
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        @if($payment->player?->profile_image)
                            <img src="{{ asset('storage/' . $payment->player->profile_image) }}" alt="{{ $payment->player->name }}"
                                 class="rounded-circle" style="width:36px;height:36px;object-fit:cover;">
                        @else
                            <div class="rounded-circle d-flex align-items-center justify-content-center bg-label-success"
                                 style="width:36px;height:36px;flex-shrink:0;">
                                <i class="mdi mdi-account" style="font-size:18px;"></i>
                            </div>
                        @endif
                        <div>
                            <span class="fw-semibold d-block">{{ $payment->player?->name ?? '—' }}</span>
                            <small class="text-muted">{{ $payment->player?->email ?? '—' }}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <div>
                        <span class="fw-semibold d-block">{{ $payment->tournament?->name ?? '—' }}</span>
                        <small class="text-muted">Host: {{ $payment->tournament?->club?->club_name ?? $payment->tournament?->club?->name ?? '—' }}</small>
                    </div>
                </td>
                <td>
                    <span class="text-uppercase fw-semibold">{{ str_replace('_', ' ', $payment->payment_method_id) }}</span>
                </td>
                <td>
                    @php
                        $payStatusMap = [
                            'paid'    => 'bg-label-success',
                            'pending' => 'bg-label-warning',
                            'failed'  => 'bg-label-danger',
                        ];
                        $payBadge = $payStatusMap[$payment->payment_status] ?? 'bg-label-secondary';
                    @endphp
                    <span class="badge {{ $payBadge }}">{{ ucfirst($payment->payment_status) }}</span>
                </td>
                <td>
                    <span class="fw-semibold text-success">{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</span>
                </td>
                <td>
                    {{ $payment->created_at?->format('Y-m-d H:i') ?? '—' }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center py-4 text-muted">
                    <i class="mdi mdi-alert-circle-outline d-block mb-2" style="font-size: 24px;"></i>
                    No tournament payments found.
                </td>
            </tr>
        @endforelse
    @endcomponent
@else
    @component('admin.components.datatable', [
        'title'       => 'Court Booking Payments',
        'subtitle'    => 'Track payments and transaction histories for court reservations',
        'paginator'   => $payments,
        'createUrl'   => null,
        'search'      => $search,
        'perPage'     => $perPage,
        'sort'        => $sort,
        'direction'   => $direction,
        'columns' => [
            ['label' => 'Player',              'sortable' => false],
            ['label' => 'Club / Court',        'sortable' => false],
            ['label' => 'Transaction ID',      'sortable' => false],
            ['label' => 'Payment Method',      'sortable' => false],
            ['label' => 'Payment Status',      'field' => 'payment_status', 'sortable' => true],
            ['label' => 'Amount',              'field' => 'total_amount', 'sortable' => true],
            ['label' => 'Paid At',             'field' => 'created_at', 'sortable' => true],
        ],
    ])
        @forelse($payments as $payment)
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        @if($payment->player?->profile_image)
                            <img src="{{ asset('storage/' . $payment->player->profile_image) }}" alt="{{ $payment->player->name }}"
                                 class="rounded-circle" style="width:36px;height:36px;object-fit:cover;">
                        @else
                            <div class="rounded-circle d-flex align-items-center justify-content-center bg-label-success"
                                 style="width:36px;height:36px;flex-shrink:0;">
                                <i class="mdi mdi-account" style="font-size:18px;"></i>
                            </div>
                        @endif
                        <div>
                            <span class="fw-semibold d-block">{{ $payment->player?->name ?? '—' }}</span>
                            <small class="text-muted">{{ $payment->player?->email ?? '—' }}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <div>
                        <span class="fw-semibold d-block">{{ $payment->club?->club_name ?? $payment->club?->name ?? '—' }}</span>
                        <small class="text-muted">{{ $payment->court?->name ?? '—' }}</small>
                    </div>
                </td>
                <td>
                    <code class="text-uppercase">{{ $payment->payment_transaction_id ?? '—' }}</code>
                </td>
                <td>
                    <span class="text-uppercase fw-semibold">{{ str_replace('_', ' ', $payment->payment_method ?? '—') }}</span>
                </td>
                <td>
                    @php
                        $payStatusMap = [
                            'paid'    => 'bg-label-success',
                            'pending' => 'bg-label-warning',
                            'failed'  => 'bg-label-danger',
                            'refunded'=> 'bg-label-info',
                        ];
                        $payBadge = $payStatusMap[$payment->payment_status] ?? 'bg-label-secondary';
                    @endphp
                    <span class="badge {{ $payBadge }}">{{ ucfirst($payment->payment_status) }}</span>
                </td>
                <td>
                    <span class="fw-semibold text-success">{{ $payment->currency }} {{ number_format($payment->total_amount, 2) }}</span>
                </td>
                <td>
                    {{ $payment->created_at?->format('Y-m-d H:i') ?? '—' }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">
                    <i class="mdi mdi-alert-circle-outline d-block mb-2" style="font-size: 24px;"></i>
                    No booking payments found.
                </td>
            </tr>
        @endforelse
    @endcomponent
@endif
@endsection
