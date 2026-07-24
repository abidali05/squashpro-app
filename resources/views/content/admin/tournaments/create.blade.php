@extends('layouts/contentNavbarLayout')

@section('title', 'Add Tournament')

@section('content')
<div class="admin-page tournaments-page">

    <div class="admin-page-header">
        <div class="admin-page-header__left">
            <h4 class="admin-page-header__title">Add Tournament</h4>
            <p class="admin-page-header__subtitle">Schedule a new tournament on behalf of a club or nationally.</p>
        </div>
        <div class="admin-page-header__actions">
            <a href="{{ route('admin.tournaments.index') }}" class="btn btn-outline-secondary">
                <i class="mdi mdi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <form action="{{ route('admin.tournaments.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row g-4">
            <div class="col-12 col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Tournament Details</h6>
                    </div>
                    <div class="card-body">
                        @include('content.admin.tournaments._form')
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Creation Guide</h6>
                    </div>
                    <div class="card-body">
                        <div class="tournaments-note-box">
                            <p class="mb-2 fw-semibold">Tournament Types</p>
                            <ul class="mb-3 ps-3 text-muted small">
                                <li><strong>Club Members Only</strong>: Exclusive to approved players at the hosting club.</li>
                                <li><strong>Club to Club</strong>: Invites an opponent club to compete.</li>
                                <li><strong>Open to All</strong>: Publicly discoverable by any matching player nationally.</li>
                            </ul>
                            <p class="mb-2 fw-semibold">Tips</p>
                            <ul class="mb-0 ps-3 text-muted small">
                                <li>The registration deadline must be on or before the start date.</li>
                                <li>Max player count governs how many player spots are available.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body d-grid gap-2">
                        <button type="submit" class="btn btn-dark">
                            <i class="mdi mdi-check me-1"></i> Save Tournament
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
