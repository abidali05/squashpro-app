@extends('layouts/contentNavbarLayout')

@section('title', 'Orders')

@section('page-script')
    <script src="{{ asset('assets/js/pages-account-settings-account.js') }}"></script>
@endsection

@section('content')
<div class="admin-page">

    {{-- Page Header --}}
    <div class="admin-page-header">
        <div class="admin-page-header__left">
            <h4 class="admin-page-header__title">Orders</h4>
            <p class="admin-page-header__subtitle">Track shipment status, manage order lifecycle, and generate invoices.</p>
        </div>
        <div class="admin-page-header__actions">
            <button type="button" class="admin-btn-secondary" data-bs-toggle="modal" data-bs-target="#largeModal">
                <i class="mdi mdi-export"></i> Export
            </button>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="admin-filter-card">
        <span class="admin-filter-card__label">Filter Orders</span>
        <div class="admin-filter-grid">
            <div>
                <label for="breedFilter" class="form-label">Breed</label>
                <select id="breedFilter" class="form-select">
                    <option value="">All Breeds</option>
                    <option value="Arabian">Arabian</option>
                    <option value="Angus">Angus</option>
                    <option value="Merino">Merino</option>
                    <option value="Rhode Island Red">Rhode Island Red</option>
                </select>
            </div>
            <div>
                <label for="statusFilter" class="form-label">Status</label>
                <select id="statusFilter" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="Confirmed">Shipped</option>
                    <option value="Pending">UnShipped</option>
                </select>
            </div>
            <div>
                <label for="orderIdFilter" class="form-label">Order ID</label>
                <input type="text" id="orderIdFilter" class="form-control" placeholder="Search by Order ID">
            </div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="admin-table-card">
        <div class="admin-card-header">
            <div>
                <h5 class="admin-card-header__title">Order List</h5>
                <p class="admin-card-header__subtitle">All orders with shipment status, breed, and quantity details.</p>
            </div>
            <span class="admin-card-header__meta">Total <strong>4</strong> orders</span>
        </div>
        <div class="table-responsive">
            <table class="admin-table" id="ordersTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll" class="form-check-input"></th>
                        <th>Order ID</th>
                        <th>Product</th>
                        <th>Breed</th>
                        <th>Qty</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><input type="checkbox" class="form-check-input"></td>
                        <td class="cell-muted">123456789</td>
                        <td>
                            <div class="cell-avatar">
                                <img src="https://carutharabians.com/wp-content/uploads/2023/05/CA-Rromance-_3.webp" alt="Arainy">
                                <span class="cell-primary" role="button" data-bs-toggle="modal"
                                    data-bs-target="#orderDetailsModal"
                                    data-image="https://carutharabians.com/wp-content/uploads/2023/05/CA-Rromance-_3.webp"
                                    data-name="Arainy" data-breed="Arabian" data-quantity="5" data-status="Confirmed"
                                    data-date="2023-05-15" data-shipping="123 Desert Rd, Dubai, UAE"
                                    data-expected-date="2023-05-20" data-tracking="UAE123456789" data-carrier="DHL"
                                    data-notes="Please contact recipient before delivery">Arainy</span>
                            </div>
                        </td>
                        <td class="cell-muted">Arabian</td>
                        <td class="cell-muted">5</td>
                        <td><span class="admin-badge admin-badge-primary">Shipped</span></td>
                        <td class="cell-muted">2023-05-15</td>
                        <td class="col-actions">
                            <div class="dropdown">
                                <button type="button" class="admin-action-btn dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="mdi mdi-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="javascript:void(0);"><i class="mdi mdi-pencil-outline me-1"></i> Edit</a>
                                    <a class="dropdown-item" href="javascript:void(0);"><i class="mdi mdi-download me-1"></i> Download Invoice</a>
                                    <a class="dropdown-item" href="javascript:void(0);" data-action="mark-dispensed"><i class="mdi mdi-align-vertical-distribute me-1"></i> Mark Dispensed</a>
                                    <a class="dropdown-item text-danger" href="javascript:void(0);" data-action="mark-failure"><i class="mdi mdi-message-alert-outline me-1"></i> Mark Failure</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td><input type="checkbox" class="form-check-input"></td>
                        <td class="cell-muted">123456789</td>
                        <td>
                            <div class="cell-avatar">
                                <img src="https://carutharabians.com/wp-content/uploads/2023/05/CA-Rromance-_3.webp" alt="Arainy">
                                <span class="cell-primary" role="button" data-bs-toggle="modal"
                                    data-bs-target="#orderDetailsModal"
                                    data-image="https://carutharabians.com/wp-content/uploads/2023/05/CA-Rromance-_3.webp"
                                    data-name="Arainy" data-breed="Angus" data-quantity="12" data-status="Pending"
                                    data-date="2023-06-20" data-shipping="123 Desert Rd, Dubai, UAE"
                                    data-expected-date="2023-06-25" data-tracking="UAE987654321" data-carrier="FedEx"
                                    data-notes="Handle with care">Arainy</span>
                            </div>
                        </td>
                        <td class="cell-muted">Angus</td>
                        <td class="cell-muted">12</td>
                        <td><span class="admin-badge admin-badge-warning">UnShipped</span></td>
                        <td class="cell-muted">2023-06-20</td>
                        <td class="col-actions">
                            <div class="dropdown">
                                <button type="button" class="admin-action-btn dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="mdi mdi-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="javascript:void(0);"><i class="mdi mdi-pencil-outline me-1"></i> Edit</a>
                                    <a class="dropdown-item" href="javascript:void(0);" data-action="mark-dispensed"><i class="mdi mdi-align-vertical-distribute me-1"></i> Mark Dispensed</a>
                                    <a class="dropdown-item text-danger" href="javascript:void(0);" data-action="mark-failure"><i class="mdi mdi-message-alert-outline me-1"></i> Mark Failure</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td><input type="checkbox" class="form-check-input"></td>
                        <td class="cell-muted">123456789</td>
                        <td>
                            <div class="cell-avatar">
                                <img src="https://carutharabians.com/wp-content/uploads/2023/05/CA-Rromance-_3.webp" alt="Arainy">
                                <span class="cell-primary" role="button" data-bs-toggle="modal"
                                    data-bs-target="#orderDetailsModal"
                                    data-image="https://carutharabians.com/wp-content/uploads/2023/05/CA-Rromance-_3.webp"
                                    data-name="Arainy" data-breed="Merino" data-quantity="8" data-status="Pending"
                                    data-date="2023-07-10" data-shipping="123 Desert Rd, Dubai, UAE"
                                    data-expected-date="2023-07-15" data-tracking="UAE112233445" data-carrier="DHL"
                                    data-notes="Fragile">Arainy</span>
                            </div>
                        </td>
                        <td class="cell-muted">Merino</td>
                        <td class="cell-muted">8</td>
                        <td><span class="admin-badge admin-badge-warning">UnShipped</span></td>
                        <td class="cell-muted">2023-07-10</td>
                        <td class="col-actions">
                            <div class="dropdown">
                                <button type="button" class="admin-action-btn dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="mdi mdi-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="javascript:void(0);"><i class="mdi mdi-pencil-outline me-1"></i> Edit</a>
                                    <a class="dropdown-item" href="javascript:void(0);" data-action="mark-dispensed"><i class="mdi mdi-align-vertical-distribute me-1"></i> Mark Dispensed</a>
                                    <a class="dropdown-item text-danger" href="javascript:void(0);" data-action="mark-failure"><i class="mdi mdi-message-alert-outline me-1"></i> Mark Failure</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td><input type="checkbox" class="form-check-input"></td>
                        <td class="cell-muted">123456789</td>
                        <td>
                            <div class="cell-avatar">
                                <img src="https://carutharabians.com/wp-content/uploads/2023/05/CA-Rromance-_3.webp" alt="Arainy">
                                <span class="cell-primary" role="button" data-bs-toggle="modal"
                                    data-bs-target="#orderDetailsModal"
                                    data-image="https://carutharabians.com/wp-content/uploads/2023/05/CA-Rromance-_3.webp"
                                    data-name="Arainy" data-breed="Rhode Island Red" data-quantity="25" data-status="Confirmed"
                                    data-date="2023-08-05" data-shipping="123 Desert Rd, Dubai, UAE"
                                    data-expected-date="2023-08-10" data-tracking="UAE556677889" data-carrier="Aramex"
                                    data-notes="Priority delivery">Arainy</span>
                            </div>
                        </td>
                        <td class="cell-muted">Rhode Island Red</td>
                        <td class="cell-muted">25</td>
                        <td><span class="admin-badge admin-badge-primary">Shipped</span></td>
                        <td class="cell-muted">2023-08-05</td>
                        <td class="col-actions">
                            <div class="dropdown">
                                <button type="button" class="admin-action-btn dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="mdi mdi-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="javascript:void(0);"><i class="mdi mdi-pencil-outline me-1"></i> Edit</a>
                                    <a class="dropdown-item" href="javascript:void(0);"><i class="mdi mdi-download me-1"></i> Download Invoice</a>
                                    <a class="dropdown-item" href="javascript:void(0);" data-action="mark-dispensed"><i class="mdi mdi-align-vertical-distribute me-1"></i> Mark Dispensed</a>
                                    <a class="dropdown-item text-danger" href="javascript:void(0);" data-action="mark-failure"><i class="mdi mdi-message-alert-outline me-1"></i> Mark Failure</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- Order Details Modal --}}
<div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4">
                        <img id="detailImage" src="" class="img-fluid rounded mb-3" alt="Product Image">
                    </div>
                    <div class="col-md-8">
                        <h4 id="detailName" class="mb-3"></h4>
                        <div class="row">
                            <div class="col-5">
                                <p class="mb-1"><strong>Breed:</strong> <span id="detailBreed"></span></p>
                                <p class="mb-1"><strong>Quantity:</strong> <span id="detailQuantity"></span></p>
                                <p class="mb-1"><strong>Status:</strong> <span id="detailStatus"></span></p>
                                <p class="mb-1"><strong>Order Date:</strong> <span id="detailDate"></span></p>
                            </div>
                            <div class="col-7">
                                <p class="mb-1"><strong>Expected Shipping:</strong> <span id="detailExpectedDate"></span></p>
                                <p class="mb-1"><strong>Tracking Number:</strong> <span id="detailTracking"></span></p>
                                <p class="mb-1"><strong>Carrier:</strong> <span id="detailCarrier"></span></p>
                                <div class="mb-1">
                                    <label for="retryCount" class="form-label"><strong>Retry Count:</strong></label>
                                    <input type="number" id="retryCount" class="form-control form-control-sm d-inline-block w-auto" value="0" min="0">
                                </div>
                            </div>
                        </div>
                        <hr>
                        <p class="mb-1"><strong>Shipping Address:</strong></p>
                        <p id="detailShipping" class="text-muted"></p>
                        <p class="mb-1"><strong>Notes:</strong></p>
                        <p id="detailNotes" class="text-muted"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Generate Invoice</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Mark Dispensed Modal --}}
<div class="modal fade" id="markDispensedModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mark Order as Dispensed</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="dispensedForm">
                    <div class="mb-3">
                        <label class="form-label">Order ID</label>
                        <input type="text" class="form-control" id="dispensedOrderId" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" rows="3" placeholder="Enter additional notes..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Mark Failure Modal --}}
<div class="modal fade" id="markFailureModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mark Order as Failed</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="failureForm">
                    <div class="mb-3">
                        <label class="form-label">Order ID</label>
                        <input type="text" class="form-control" id="failureOrderId" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason for Failure</label>
                        <textarea class="form-control" rows="3" placeholder="Enter reason for failure..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger">Submit Report</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Export Modal --}}
<div class="modal fade" id="largeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Export Orders</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Select export format and date range.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Export</button>
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
    // Select all
    document.getElementById('selectAll').addEventListener('change', function () {
        document.querySelectorAll('tbody .form-check-input').forEach(cb => cb.checked = this.checked);
    });

    // Filter
    $(document).ready(function () {
        $('#breedFilter, #statusFilter, #orderIdFilter').on('change keyup', function () {
            const breed   = $('#breedFilter').val().toLowerCase();
            const status  = $('#statusFilter').val().toLowerCase();
            const orderId = $('#orderIdFilter').val().toLowerCase();
            $('#ordersTable tbody tr').each(function () {
                const rowBreed   = $(this).find('td:nth-child(4)').text().toLowerCase();
                const rowStatus  = $(this).find('td:nth-child(6)').text().toLowerCase();
                const rowOrderId = $(this).find('td:nth-child(2)').text().toLowerCase();
                $(this).toggle(
                    (breed   === '' || rowBreed.includes(breed))   &&
                    (status  === '' || rowStatus.includes(status)) &&
                    (orderId === '' || rowOrderId.includes(orderId))
                );
            });
        });
    });

    // Order details modal
    const statusStyles = {
        'Confirmed': 'admin-badge-primary',
        'Pending':   'admin-badge-warning',
        'Shipped':   'admin-badge-success',
        'Cancelled': 'admin-badge-danger'
    };

    document.getElementById('orderDetailsModal').addEventListener('show.bs.modal', function (event) {
        const btn   = event.relatedTarget;
        const modal = this;
        const status      = btn.dataset.status;
        const statusClass = statusStyles[status] || 'admin-badge-primary';
        modal.querySelector('#detailImage').src              = btn.dataset.image;
        modal.querySelector('#detailName').textContent       = btn.dataset.name;
        modal.querySelector('#detailBreed').textContent      = btn.dataset.breed;
        modal.querySelector('#detailQuantity').textContent   = btn.dataset.quantity;
        modal.querySelector('#detailStatus').innerHTML       = `<span class="admin-badge ${statusClass}">${status}</span>`;
        modal.querySelector('#detailDate').textContent       = btn.dataset.date;
        modal.querySelector('#detailShipping').textContent   = btn.dataset.shipping;
        modal.querySelector('#detailExpectedDate').textContent = btn.dataset.expectedDate;
        modal.querySelector('#detailTracking').textContent   = btn.dataset.tracking;
        modal.querySelector('#detailCarrier').textContent    = btn.dataset.carrier;
        modal.querySelector('#detailNotes').textContent      = btn.dataset.notes;
    });

    // Mark dispensed / failure
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-action="mark-dispensed"]').forEach(btn => {
            btn.addEventListener('click', function () {
                const orderId = this.closest('tr').querySelector('td:nth-child(2)').textContent;
                document.getElementById('dispensedOrderId').value = orderId;
                new bootstrap.Modal(document.getElementById('markDispensedModal')).show();
            });
        });

        document.querySelectorAll('[data-action="mark-failure"]').forEach(btn => {
            btn.addEventListener('click', function () {
                const orderId = this.closest('tr').querySelector('td:nth-child(2)').textContent;
                document.getElementById('failureOrderId').value = orderId;
                new bootstrap.Modal(document.getElementById('markFailureModal')).show();
            });
        });

        document.getElementById('dispensedForm').addEventListener('submit', function (e) {
            e.preventDefault();
            bootstrap.Modal.getInstance(document.getElementById('markDispensedModal')).hide();
        });

        document.getElementById('failureForm').addEventListener('submit', function (e) {
            e.preventDefault();
            bootstrap.Modal.getInstance(document.getElementById('markFailureModal')).hide();
        });
    });
</script>
@endpush
