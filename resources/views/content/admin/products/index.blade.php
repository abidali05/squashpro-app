@extends('layouts/contentNavbarLayout')

@section('title', 'Products')

@section('page-script')
    <script src="{{ asset('assets/js/pages-account-settings-account.js') }}"></script>
@endsection

@section('content')
<div class="admin-page">

    {{-- Page Header --}}
    <div class="admin-page-header">
        <div class="admin-page-header__left">
            <h4 class="admin-page-header__title">Products</h4>
            <p class="admin-page-header__subtitle">Manage product listings, breeds, quantities, and client requests.</p>
        </div>
        <div class="admin-page-header__actions">
            <button type="button" class="admin-btn-secondary">
                <i class="mdi mdi-upload"></i> Upload Form
            </button>
            <button type="button" class="admin-btn-primary" data-bs-toggle="modal" data-bs-target="#largeModal">
                <i class="mdi mdi-plus"></i> Add Product
            </button>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="admin-table-card">
        <div class="admin-card-header">
            <div>
                <h5 class="admin-card-header__title">Product Catalogue</h5>
                <p class="admin-card-header__subtitle">All registered products with breed, quantity, and availability.</p>
            </div>
            <span class="admin-card-header__meta">Total <strong>4</strong> products</span>
        </div>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll" class="form-check-input"></th>
                        <th>Product</th>
                        <th>Breed</th>
                        <th>Quantity</th>
                        <th>Date</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><input type="checkbox" class="form-check-input"></td>
                        <td>
                            <div class="cell-avatar">
                                <img src="https://images.squarespace-cdn.com/content/v1/592074edbe659438f08000b0/1735066890317-Q0JZ3EK311O0NP0L9LQC/coming-soon-store-placeholder-image.gif?format=1000w" alt="Arainy">
                                <span class="cell-primary">Arainy</span>
                            </div>
                        </td>
                        <td class="cell-muted">Arabian</td>
                        <td class="cell-muted">5</td>
                        <td class="cell-muted">2023-05-15</td>
                        <td class="col-actions">
                            <div class="dropdown">
                                <button type="button" class="admin-action-btn dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="mdi mdi-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="javascript:void(0);"><i class="mdi mdi-pencil-outline me-1"></i> Edit</a>
                                    <a class="dropdown-item text-danger" href="javascript:void(0);"><i class="mdi mdi-trash-can-outline me-1"></i> Delete</a>
                                    <a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#largeModal2"><i class="mdi mdi-clipboard-list-outline me-1"></i> Product Request</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td><input type="checkbox" class="form-check-input"></td>
                        <td>
                            <div class="cell-avatar">
                                <img src="https://i.pinimg.com/736x/d5/a0/cf/d5a0cfb3c8a7213d2edb74e6861f7660.jpg" alt="Shomou">
                                <span class="cell-primary">Shomou</span>
                            </div>
                        </td>
                        <td class="cell-muted">Angus</td>
                        <td class="cell-muted">12</td>
                        <td class="cell-muted">2023-06-20</td>
                        <td class="col-actions">
                            <div class="dropdown">
                                <button type="button" class="admin-action-btn dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="mdi mdi-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="javascript:void(0);"><i class="mdi mdi-pencil-outline me-1"></i> Edit</a>
                                    <a class="dropdown-item text-danger" href="javascript:void(0);"><i class="mdi mdi-trash-can-outline me-1"></i> Delete</a>
                                    <a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#largeModal2"><i class="mdi mdi-clipboard-list-outline me-1"></i> Product Request</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td><input type="checkbox" class="form-check-input"></td>
                        <td>
                            <div class="cell-avatar">
                                <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT_5LKdbLhvQ7Y2w6vwtPwRz8yJ2ybMyZdsww&s" alt="Ranatunga">
                                <span class="cell-primary">Ranatunga</span>
                            </div>
                        </td>
                        <td class="cell-muted">Merino</td>
                        <td class="cell-muted">8</td>
                        <td class="cell-muted">2023-07-10</td>
                        <td class="col-actions">
                            <div class="dropdown">
                                <button type="button" class="admin-action-btn dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="mdi mdi-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="javascript:void(0);"><i class="mdi mdi-pencil-outline me-1"></i> Edit</a>
                                    <a class="dropdown-item text-danger" href="javascript:void(0);"><i class="mdi mdi-trash-can-outline me-1"></i> Delete</a>
                                    <a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#largeModal2"><i class="mdi mdi-clipboard-list-outline me-1"></i> Product Request</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td><input type="checkbox" class="form-check-input"></td>
                        <td>
                            <div class="cell-avatar">
                                <img src="https://carutharabians.com/wp-content/uploads/2023/05/CA-Rromance-_3.webp" alt="Keene">
                                <span class="cell-primary">Keene</span>
                            </div>
                        </td>
                        <td class="cell-muted">Rhode Island Red</td>
                        <td class="cell-muted">25</td>
                        <td class="cell-muted">2023-08-05</td>
                        <td class="col-actions">
                            <div class="dropdown">
                                <button type="button" class="admin-action-btn dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="mdi mdi-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="javascript:void(0);"><i class="mdi mdi-pencil-outline me-1"></i> Edit</a>
                                    <a class="dropdown-item text-danger" href="javascript:void(0);"><i class="mdi mdi-trash-can-outline me-1"></i> Delete</a>
                                    <a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#largeModal2"><i class="mdi mdi-clipboard-list-outline me-1"></i> Product Request</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- Add Product Modal --}}
<div class="modal fade" id="largeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="d-flex align-items-start align-items-sm-center gap-4 mb-4">
                        <img src="{{ asset('assets/img/avatars/1.png') }}" alt="user-avatar"
                            class="d-block w-px-120 h-px-120 rounded" id="uploadedAvatar" />
                        <div class="button-wrapper">
                            <label for="upload" class="btn btn-primary me-2 mb-3" tabindex="0">
                                <span class="d-none d-sm-block">Add Photo</span>
                                <i class="mdi mdi-tray-arrow-up d-block d-sm-none"></i>
                                <input type="file" id="upload" class="account-file-input" hidden accept="image/png, image/jpeg" />
                            </label>
                            <button type="button" class="btn btn-outline-danger account-image-reset mb-3">
                                <i class="mdi mdi-reload d-block d-sm-none"></i>
                                <span class="d-none d-sm-block">Reset</span>
                            </button>
                            <div class="text-muted small">Allowed JPG, GIF or PNG. Max size of 800K</div>
                        </div>
                    </div>
                    <div class="input-group input-group-merge mb-4">
                        <span class="input-group-text"><i class="mdi mdi-account-outline"></i></span>
                        <input type="text" class="form-control" placeholder="Product Name" />
                    </div>
                    <div class="input-group input-group-merge mb-4">
                        <span class="input-group-text"><i class="mdi mdi-account-outline"></i></span>
                        <select class="form-select">
                            <option value="" disabled selected hidden>Select a Breed</option>
                            <option value="1">Arabian</option>
                            <option value="2">Angus</option>
                            <option value="3">Merino</option>
                        </select>
                    </div>
                    <div class="input-group input-group-merge mb-4">
                        <span class="input-group-text"><i class="mdi mdi-calendar"></i></span>
                        <input type="text" class="form-control" id="date-range-picker" placeholder="Select date range">
                    </div>
                    <div class="input-group input-group-merge mb-4">
                        <span class="input-group-text"><i class="mdi mdi-calculator-variant-outline"></i></span>
                        <input type="text" class="form-control" placeholder="Quantity" />
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

{{-- Product Request Modal --}}
<div class="modal fade" id="largeModal2" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Product Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-end mb-3">
                    <button type="button" onclick="toggleInputs()" class="btn btn-outline-secondary btn-sm">Edit</button>
                </div>
                <form>
                    <div class="input-group input-group-merge mb-4">
                        <span class="input-group-text"><i class="mdi mdi-account-outline"></i></span>
                        <input type="text" class="form-control toggle-input" disabled id="reqProductName"
                            placeholder="Product Name" value="Product Name" />
                    </div>
                    <div class="input-group input-group-merge mb-4">
                        <span class="input-group-text"><i class="mdi mdi-calendar"></i></span>
                        <input type="text" class="form-control toggle-input" disabled id="reqShipping"
                            placeholder="Shipping Address" value="Shipping Address">
                    </div>
                    <div class="input-group input-group-merge mb-4">
                        <span class="input-group-text"><i class="mdi mdi-calculator-variant-outline"></i></span>
                        <input type="text" class="form-control toggle-input" disabled id="reqInstructions"
                            placeholder="Special Instructions" value="Special Instructions" />
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary">Submit Request</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('my-styles')
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endpush

@push('my-script')
<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script>
    $(function () {
        $('#date-range-picker').daterangepicker({
            opens: 'right',
            locale: { format: 'YYYY-MM-DD' }
        });
    });

    document.getElementById('selectAll').addEventListener('change', function () {
        document.querySelectorAll('tbody .form-check-input').forEach(cb => cb.checked = this.checked);
    });

    let isDisabled = true;
    function toggleInputs() {
        isDisabled = !isDisabled;
        document.querySelectorAll('.toggle-input').forEach(input => input.disabled = isDisabled);
    }
</script>
@endpush
