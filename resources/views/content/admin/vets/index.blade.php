@extends('layouts/contentNavbarLayout')

@section('title', 'Veterans')

@section('content')
<div class="admin-page">

    {{-- Page Header --}}
    <div class="admin-page-header">
        <div class="admin-page-header__left">
            <h4 class="admin-page-header__title">
                <span class="text-muted fw-light" style="font-weight:400;">Users /</span> Veterans
            </h4>
            <p class="admin-page-header__subtitle">View and manage all veterinarians linked to client accounts.</p>
        </div>
        <div class="admin-page-header__actions">
            <button type="button" class="admin-btn-primary" data-bs-toggle="modal" data-bs-target="#largeModal">
                <i class="mdi mdi-plus"></i> Add New Vet
            </button>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="admin-table-card">
        <div class="admin-card-header">
            <div>
                <h5 class="admin-card-header__title">Veterinarian Directory</h5>
                <p class="admin-card-header__subtitle">All registered vets and their associated client accounts.</p>
            </div>
            <span class="admin-card-header__meta">Total <strong>4</strong> vets</span>
        </div>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Client Name</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="cell-primary">Tours Project</td>
                        <td class="cell-muted">0322-544-2821</td>
                        <td class="cell-muted">admin@admin.com</td>
                        <td>Albert Cook</td>
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
                        <td>Albert Cook</td>
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
                        <td>Albert Cook</td>
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
                        <td>Albert Cook</td>
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

{{-- Add New Vet Modal --}}
<div class="modal fade" id="largeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Veteran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="input-group input-group-merge mb-4">
                        <span id="basic-icon-default-fullname2" class="input-group-text"><i class="mdi mdi-account-outline"></i></span>
                        <input type="text" class="form-control" id="basic-icon-default-fullname" placeholder="Full Name"
                            aria-label="Full Name" aria-describedby="basic-icon-default-fullname2" />
                    </div>
                    <div class="mb-4">
                        <div class="input-group input-group-merge">
                            <span class="input-group-text"><i class="mdi mdi-email-outline"></i></span>
                            <input type="text" id="basic-icon-default-email" class="form-control" placeholder="Email"
                                aria-label="Email" aria-describedby="basic-icon-default-email2" />
                            <span id="basic-icon-default-email2" class="input-group-text">@example.com</span>
                        </div>
                        <div class="form-text">You can use letters, numbers &amp; periods</div>
                    </div>
                    <div class="input-group input-group-merge mb-4">
                        <span id="basic-icon-default-password2" class="input-group-text"><i class="mdi mdi-lock-outline"></i></span>
                        <input type="password" id="basic-icon-default-password" class="form-control" placeholder="Password"
                            aria-label="Password" aria-describedby="basic-icon-default-password2" />
                        <span class="input-group-text cursor-pointer" id="togglePassword">
                            <i class="mdi mdi-eye-outline" id="togglePasswordIcon"></i>
                        </span>
                    </div>
                    <div class="input-group input-group-merge mb-4">
                        <span id="basic-icon-default-phone2" class="input-group-text"><i class="mdi mdi-phone"></i></span>
                        <input type="text" id="basic-icon-default-phone" class="form-control phone-mask" placeholder="Phone No"
                            aria-label="Phone No" aria-describedby="basic-icon-default-phone2" />
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
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const togglePassword    = document.querySelector('#togglePassword');
        const password          = document.querySelector('#basic-icon-default-password');
        const togglePasswordIcon = document.querySelector('#togglePasswordIcon');
        if (!togglePassword) return;
        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            if (type === 'password') {
                togglePasswordIcon.classList.remove('mdi-eye-off-outline');
                togglePasswordIcon.classList.add('mdi-eye-outline');
            } else {
                togglePasswordIcon.classList.remove('mdi-eye-outline');
                togglePasswordIcon.classList.add('mdi-eye-off-outline');
            }
        });
    });
</script>
@endpush
