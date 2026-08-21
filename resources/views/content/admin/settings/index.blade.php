@extends('layouts/contentNavbarLayout')

@section('title', 'Platform Settings')

@section('content')
<div class="settings-page">
    
    {{-- Page Header --}}
    <div class="app-page-header mb-4">
        <h4 class="app-page-header__title mb-1">Platform Settings</h4>
        <p class="app-page-header__subtitle text-muted">Configure platform fee structure, currency options, and generic options</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="mdi mdi-check-circle-outline me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-8 col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header border-bottom py-3">
                    <h5 class="mb-0 fw-semibold">Global Platform Variables</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.settings.update') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label class="form-label" for="platform_name">Platform Name</label>
                            <input type="text" name="platform_name" id="platform_name" value="{{ old('platform_name', $settings['platform_name']) }}" class="form-control @error('platform_name') is-invalid @enderror" placeholder="e.g. Squash Pro" required>
                            @error('platform_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="contact_email">Support & Contact Email</label>
                            <input type="email" name="contact_email" id="contact_email" value="{{ old('contact_email', $settings['contact_email']) }}" class="form-control @error('contact_email') is-invalid @enderror" placeholder="e.g. support@squashpro.com" required>
                            @error('contact_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="currency">Default Currency</label>
                                <input type="text" name="currency" id="currency" value="{{ old('currency', $settings['currency']) }}" class="form-control @error('currency') is-invalid @enderror" placeholder="e.g. PKR" required>
                                @error('currency')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="commission_percentage">Club Commission (%)</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" min="0" max="100" name="commission_percentage" id="commission_percentage" value="{{ old('commission_percentage', $settings['commission_percentage']) }}" class="form-control @error('commission_percentage') is-invalid @enderror" placeholder="e.g. 10.0" required>
                                    <span class="input-group-text">%</span>
                                </div>
                                @error('commission_percentage')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="service_fee">Default Service Fee</label>
                            <input type="number" step="0.01" min="0" name="service_fee" id="service_fee" value="{{ old('service_fee', $settings['service_fee']) }}" class="form-control @error('service_fee') is-invalid @enderror" placeholder="e.g. 50.00" required>
                            @error('service_fee')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="maintenance_mode" id="maintenance_mode" value="1" @checked(old('maintenance_mode', $settings['maintenance_mode']))>
                                <label class="form-check-label fw-semibold" for="maintenance_mode">Enable Maintenance Mode</label>
                            </div>
                            <small class="text-muted d-block mt-1">If enabled, the player and club APIs will return a service unavailable message.</small>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="reset" class="btn btn-outline-secondary">Reset Changes</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-content-save-outline me-1"></i> Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header border-bottom py-3">
                    <h5 class="mb-0 fw-semibold">Settings Information</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small">Global system variables are stored securely and take effect immediately across all client applications and API responses.</p>
                    <hr>
                    <div class="d-flex align-items-center mb-3">
                        <i class="mdi mdi-file-document-outline fs-3 text-primary me-2"></i>
                        <div>
                            <span class="fw-semibold d-block">Configuration File</span>
                            <code class="small text-muted">storage/app/settings.json</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
