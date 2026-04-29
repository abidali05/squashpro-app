@extends('layouts/contentNavbarLayout')

@section('title', 'Clients')

@section('content')
<div class="admin-page">

    {{-- Page Header --}}
    <div class="admin-page-header">
        <div class="admin-page-header__left">
            <h4 class="admin-page-header__title">
                <span class="text-muted fw-light" style="font-weight:400;">Users /</span> Clients
            </h4>
            <p class="admin-page-header__subtitle">Manage client records, contract progress, and billing lifecycle.</p>
        </div>
        <div class="admin-page-header__actions">
            <button type="button" class="admin-btn-primary" data-bs-toggle="modal" data-bs-target="#largeModal">
                <i class="mdi mdi-plus"></i> Add New Client
            </button>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="admin-table-card">
        <div class="admin-card-header">
            <div>
                <h5 class="admin-card-header__title">Client Directory</h5>
                <p class="admin-card-header__subtitle">Contracts, payment milestones, and product fulfillment status.</p>
            </div>
            <span class="admin-card-header__meta">
                Total <strong>4</strong> clients
            </span>
        </div>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Contract</th>
                        <th>Deposit</th>
                        <th>Final Invoice</th>
                        <th>Product</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="cell-primary">Tours Project</td>
                        <td class="cell-muted">0322-544-2821</td>
                        <td class="cell-muted">admin@admin.com</td>
                        <td><span class="admin-badge admin-badge-primary">Signed</span></td>
                        <td><span class="admin-badge admin-badge-secondary">Issued</span></td>
                        <td><span class="admin-badge admin-badge-primary">Active</span></td>
                        <td><span class="admin-badge admin-badge-info">Scheduled</span></td>
                        <td class="col-actions">
                            <div class="dropdown">
                                <button type="button" class="admin-action-btn dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="mdi mdi-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="javascript:void(0);"><i class="mdi mdi-pencil-outline me-1"></i> Edit</a>
                                    <a class="dropdown-item text-danger" href="javascript:void(0);"><i class="mdi mdi-trash-can-outline me-1"></i> Delete</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="cell-primary">Sports Project</td>
                        <td class="cell-muted">0322-544-2821</td>
                        <td class="cell-muted">client1@client1.com</td>
                        <td><span class="admin-badge admin-badge-warning">Pending</span></td>
                        <td><span class="admin-badge admin-badge-success">Paid</span></td>
                        <td><span class="admin-badge admin-badge-info">Scheduled</span></td>
                        <td><span class="admin-badge admin-badge-warning">Pending</span></td>
                        <td class="col-actions">
                            <div class="dropdown">
                                <button type="button" class="admin-action-btn dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="mdi mdi-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="javascript:void(0);"><i class="mdi mdi-pencil-outline me-1"></i> Edit</a>
                                    <a class="dropdown-item text-danger" href="javascript:void(0);"><i class="mdi mdi-trash-can-outline me-1"></i> Delete</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="cell-primary">Greenhouse Project</td>
                        <td class="cell-muted">0322-544-2821</td>
                        <td class="cell-muted">client2@client2.com</td>
                        <td><span class="admin-badge admin-badge-warning">Pending</span></td>
                        <td><span class="admin-badge admin-badge-success">Paid</span></td>
                        <td><span class="admin-badge admin-badge-info">Scheduled</span></td>
                        <td><span class="admin-badge admin-badge-primary">Active</span></td>
                        <td class="col-actions">
                            <div class="dropdown">
                                <button type="button" class="admin-action-btn dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="mdi mdi-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="javascript:void(0);"><i class="mdi mdi-pencil-outline me-1"></i> Edit</a>
                                    <a class="dropdown-item text-danger" href="javascript:void(0);"><i class="mdi mdi-trash-can-outline me-1"></i> Delete</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="cell-primary">Bank Project</td>
                        <td class="cell-muted">0322-544-2821</td>
                        <td class="cell-muted">client3@client3.com</td>
                        <td><span class="admin-badge admin-badge-warning">Pending</span></td>
                        <td><span class="admin-badge admin-badge-secondary">Issued</span></td>
                        <td><span class="admin-badge admin-badge-warning">Pending</span></td>
                        <td><span class="admin-badge admin-badge-secondary">Issued</span></td>
                        <td class="col-actions">
                            <div class="dropdown">
                                <button type="button" class="admin-action-btn dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="mdi mdi-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="javascript:void(0);"><i class="mdi mdi-pencil-outline me-1"></i> Edit</a>
                                    <a class="dropdown-item text-danger" href="javascript:void(0);"><i class="mdi mdi-trash-can-outline me-1"></i> Delete</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- Add New Client Modal --}}
<div class="modal fade" id="largeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="input-group input-group-merge mb-4">
                        <span class="input-group-text"><i class="mdi mdi-account-outline"></i></span>
                        <input type="text" class="form-control" placeholder="Full Name" />
                    </div>
                    <div class="mb-4">
                        <div class="input-group input-group-merge">
                            <span class="input-group-text"><i class="mdi mdi-email-outline"></i></span>
                            <input type="email" class="form-control" placeholder="Email" />
                            <span class="input-group-text">@example.com</span>
                        </div>
                        <div class="form-text">You can use letters, numbers &amp; periods</div>
                    </div>
                    <div class="input-group input-group-merge mb-4">
                        <span class="input-group-text"><i class="mdi mdi-lock-outline"></i></span>
                        <input type="password" id="basic-icon-default-password" class="form-control" placeholder="Password" />
                        <span class="input-group-text cursor-pointer" id="togglePassword">
                            <i class="mdi mdi-eye-outline" id="togglePasswordIcon"></i>
                        </span>
                    </div>
                    <div class="input-group input-group-merge mb-4">
                        <span class="input-group-text"><i class="mdi mdi-phone"></i></span>
                        <input type="text" class="form-control phone-mask" placeholder="Phone No" />
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('my-script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggle = document.querySelector('#togglePassword');
        const pwd    = document.querySelector('#basic-icon-default-password');
        const icon   = document.querySelector('#togglePasswordIcon');
        if (!toggle) return;
        toggle.addEventListener('click', function () {
            const isPassword = pwd.getAttribute('type') === 'password';
            pwd.setAttribute('type', isPassword ? 'text' : 'password');
            icon.classList.toggle('mdi-eye-outline', !isPassword);
            icon.classList.toggle('mdi-eye-off-outline', isPassword);
        });
    });
</script>
@endpush
