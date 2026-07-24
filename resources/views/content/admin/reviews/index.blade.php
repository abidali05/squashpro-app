@extends('layouts/contentNavbarLayout')

@section('title', 'Booking Reviews')

@section('content')
@component('admin.components.datatable', [
    'title'       => 'Reviews & Ratings',
    'subtitle'    => 'Moderate player reviews and ratings given to clubs and courts',
    'paginator'   => $reviews,
    'createUrl'   => null,
    'search'      => $search,
    'perPage'     => $perPage,
    'sort'        => $sort,
    'direction'   => $direction,
    'filters'     => [
        [
            'name'    => 'rating',
            'label'   => 'Rating',
            'value'   => $rating,
            'options' => [
                ''  => 'All Ratings',
                '5' => '5 Stars',
                '4' => '4 Stars',
                '3' => '3 Stars',
                '2' => '2 Stars',
                '1' => '1 Star',
            ],
        ],
        [
            'name'    => 'club_id',
            'label'   => 'Club',
            'value'   => $clubId,
            'options' => ['' => 'All Clubs'] + $clubs->pluck('club_name', 'id')->toArray(),
        ],
    ],
    'columns' => [
        ['label' => 'Player',     'sortable' => false],
        ['label' => 'Club/Court', 'sortable' => false],
        ['label' => 'Rating',     'field' => 'rating', 'sortable' => true],
        ['label' => 'Review',     'sortable' => false],
        ['label' => 'Date',       'field' => 'created_at', 'sortable' => true],
        ['label' => 'Actions',    'actions' => true],
    ],
])
    @forelse($reviews as $rev)
        <tr>
            <td>
                <div class="d-flex align-items-center gap-2">
                    @if($rev->player?->profile_image)
                        <img src="{{ asset('storage/' . $rev->player->profile_image) }}" alt="{{ $rev->player->name }}"
                             class="rounded-circle" style="width:36px;height:36px;object-fit:cover;">
                    @else
                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-label-success"
                             style="width:36px;height:36px;flex-shrink:0;">
                            <i class="mdi mdi-account" style="font-size:18px;"></i>
                        </div>
                    @endif
                    <div>
                        <span class="fw-semibold d-block">{{ $rev->player?->name ?? '—' }}</span>
                        <small class="text-muted">{{ $rev->player?->email ?? '—' }}</small>
                    </div>
                </div>
            </td>
            <td>
                <span class="fw-semibold d-block">{{ $rev->club?->club_name ?? $rev->club?->name ?? '—' }}</span>
                <span class="text-muted small">Court: {{ $rev->court?->name ?? '—' }}</span>
            </td>
            <td>
                <div class="text-warning">
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <= $rev->rating)
                            <i class="mdi mdi-star"></i>
                        @else
                            <i class="mdi mdi-star-outline"></i>
                        @endif
                    @endfor
                    <span class="text-muted small ms-1">({{ $rev->rating }})</span>
                </div>
            </td>
            <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: normal;">
                <p class="mb-0" style="font-size: 13px;">{{ $rev->review ?? 'No comment provided.' }}</p>
            </td>
            <td>
                <span class="text-muted small">{{ $rev->created_at->format('Y-m-d H:i') }}</span>
            </td>
            <td class="text-end">
                @include('admin.components.action-buttons', [
                    'type'       => 'delete',
                    'formAction' => route('admin.booking-reviews.destroy', $rev),
                    'confirm'    => 'Are you sure you want to delete this player review? This action cannot be undone.',
                    'title'      => 'Delete Review',
                ])
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="6" class="text-center py-4 text-muted">
                <i class="mdi mdi-alert-circle-outline d-block mb-2" style="font-size: 24px;"></i>
                No booking reviews found matching the filters.
            </td>
        </tr>
    @endforelse
@endcomponent
@endsection
