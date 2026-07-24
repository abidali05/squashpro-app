@extends('layouts/contentNavbarLayout')

@section('title', 'Edit Tournament')

@section('content')
<div class="admin-page tournaments-page">

    <div class="admin-page-header">
        <div class="admin-page-header__left">
            <h4 class="admin-page-header__title">Edit Tournament</h4>
            <p class="admin-page-header__subtitle">Update tournament schedule, format, eligibility rules, or cover image.</p>
        </div>
        <div class="admin-page-header__actions">
            <a href="{{ route('admin.tournaments.index') }}" class="btn btn-outline-secondary">
                <i class="mdi mdi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <form action="{{ route('admin.tournaments.update', $tournament) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-12 col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Edit Details</h6>
                    </div>
                    <div class="card-body">
                        @include('content.admin.tournaments._form')
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                @if($tournament->tournament_image)
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Current Cover Image</h6>
                        </div>
                        <div class="card-body text-center">
                            <img src="{{ str_starts_with($tournament->tournament_image, 'http') ? $tournament->tournament_image : asset('storage/' . $tournament->tournament_image) }}" 
                                alt="{{ $tournament->name }}" style="max-width: 100%; border-radius: 12px; height: 180px; object-fit: cover;">
                        </div>
                    </div>
                @endif

                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Status Guide</h6>
                    </div>
                    <div class="card-body">
                        <div class="tournaments-note-box">
                            <p class="mb-2 fw-semibold">Status Keys</p>
                            <ul class="mb-0 ps-3 text-muted small">
                                <li><strong>Open</strong>: Accepting registrations.</li>
                                <li><strong>Full</strong>: Maximum capacity reached.</li>
                                <li><strong>Completed</strong>: Tournament matches completed.</li>
                                <li><strong>Cancelled</strong>: Cancelled by host or admin.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body d-grid gap-2">
                        <button type="submit" class="btn btn-dark">
                            <i class="mdi mdi-check me-1"></i> Update Tournament
                        </button>
                        <a href="{{ route('admin.tournaments.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </form>

</div>
@endsection

@push('my-styles')
<style>
    .tournaments-note-box {
        background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
        border: 1px solid #e6ebf2;
        border-radius: 16px;
        padding: 1rem;
    }
</style>
@endpush
