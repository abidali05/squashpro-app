@extends('layouts/contentNavbarLayout')

@section('title', 'Tables - Orders')
@section('page-script')
    <script src="{{ asset('assets/js/pages-account-settings-account.js') }}"></script>
@endsection
@section('content')


    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="py-3"><span class="text-muted fw-medium">Contracts</span>
            </h4>
        </div>
    </div>

    <div class="card">
        <!-- Filter Section -->
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Filters</h5>
        </div>
        <div class="card-body pt-0">
            <div class="row mb-4">

                <div class="col-md-6">
                    <label for="statusFilter" class="form-label">Status</label>
                    <select id="statusFilter" class="form-select">
                        <option value="">Choose Status</option>
                        <option value="Confirmed">Signed</option>
                        <option value="Pending">Un Signed</option>

                    </select>
                </div>
                <div class="col-md-6">
                    <label for="orderIdFilter" class="form-label">Date</label>
                    <input type="date" id="orderIdFilter" class="form-control" placeholder="Search by Order ID">
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="table-responsive text-nowrap">
            <table class="table table-hover" id="ordersTable">
                <!-- Your existing table content -->
            </table>
        </div>
    </div>


    <!-- Hoverable Table rows -->
    <div class="card">

        <div class="table-responsive text-nowrap">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" id="selectAll" class="form-check-input"
                                style="width: 18px; height: 18px;">
                        </th>
                        <th>Contract Id</th>

                        <th>Status</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input">
                        </td>
                        <td class="small-text">123456789</td>

                        <td class="small-text"><span
                                class="badge rounded-pill bg-label-primary me-1 small-text">Signed</span></td>
                        <td class="small-text">2023-05-15</td>
                        <td>
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                    <i class="mdi mdi-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item view-contract" href="javascript:void(0);"
                                        data-contract-id="123456789" data-status="Signed" data-created-at="2023-05-15"
                                        data-signed-by="John Doe" data-signed-date="2023-05-15"
                                        data-signature-image="/path/to/signature.png"
                                        data-contract-text="This is the signed contract content...">
                                        <i class="mdi mdi-eye-outline me-1"></i> View
                                    </a>
                                    <a class="dropdown-item" href="javascript:void(0);">
                                        <i class="mdi mdi-download me-1"></i> Download
                                    </a>

                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input">
                        </td>
                        <td class="small-text">123456789</td>

                        <td class="small-text"><span class="badge rounded-pill bg-label-warning me-1 small-text">Un
                                Signed</span></td>
                        <td class="small-text">2023-06-20</td>
                        <td>
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                    <i class="mdi mdi-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item view-contract" href="javascript:void(0);"
                                        data-contract-id="987654321" data-status="Un Signed" data-created-at="2023-06-20"
                                        data-contract-text="This is the unsigned contract content...">
                                        <i class="mdi mdi-pencil-outline me-1"></i> Sign
                                    </a>
                                    <a class="dropdown-item" href="javascript:void(0);">
                                        <i class="mdi mdi-eye-outline me-1"></i> View
                                    </a>

                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input">
                        </td>
                        <td class="small-text">123456789</td>

                        <td class="small-text"><span class="badge rounded-pill bg-label-warning me-1 small-text">Un
                                Signed</span></td>
                        <td class="small-text">2023-07-10</td>
                        <td>
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                    <i class="mdi mdi-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item view-contract" href="javascript:void(0);"
                                        data-contract-id="987654321" data-status="Un Signed" data-created-at="2023-06-20"
                                        data-contract-text="This is the unsigned contract content...">
                                        <i class="mdi mdi-pencil-outline me-1"></i> Sign
                                    </a>
                                    <a class="dropdown-item" href="javascript:void(0);">
                                        <i class="mdi mdi-eye-outline me-1"></i> View
                                    </a>

                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input">
                        </td>
                        <td class="small-text">123456789</td>

                        <td class="small-text"><span
                                class="badge rounded-pill bg-label-primary me-1 small-text">Signed</span></td>
                        <td class="small-text">2023-08-05</td>
                        <td>
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown">
                                    <i class="mdi mdi-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item view-contract" href="javascript:void(0);"
                                        data-contract-id="123456789" data-status="Signed" data-created-at="2023-05-15"
                                        data-signed-by="John Doe" data-signed-date="2023-05-15"
                                        data-signature-image="/path/to/signature.png"
                                        data-contract-text="This is the signed contract content...">
                                        <i class="mdi mdi-eye-outline me-1"></i> View
                                    </a>
                                    <a class="dropdown-item" href="javascript:void(0);">
                                        <i class="mdi mdi-download me-1"></i> Download
                                    </a>

                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

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
                                    <p class="mb-1"><strong>Expected Shipping:</strong> <span
                                            id="detailExpectedDate"></span></p>
                                    <p class="mb-1"><strong>Tracking Number:</strong> <span id="detailTracking"></span>
                                    </p>
                                    <p class="mb-1"><strong>Carrier:</strong> <span id="detailCarrier"></span></p>
                                    <div class="mb-1">
                                        <label for="retryCount" class="form-label"><strong>Retry Count:</strong></label>
                                        <input type="number" id="retryCount"
                                            class="form-control form-control-sm d-inline-block w-auto" value="0"
                                            min="0">
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <p class="mb-1"><strong>Shipping Address:</strong></p>
                            <p id="detailShipping" class="text-muted"></p>
                            <p class="mb-1"><strong>Special Notes:</strong></p>
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

    <!-- Contract Details Modal -->
    <div class="modal fade" id="contractDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Contract Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <h4 class="mb-3">Contract #<span id="contractId"></span></h4>
                            <div class="mb-3">
                                <p><strong>Status:</strong> <span id="contractStatus" class="badge"></span></p>
                                <p><strong>Created At:</strong> <span id="contractCreatedAt"></span></p>
                            </div>

                            <div class="contract-content mb-4 p-3 border rounded bg-light">
                                <!-- Contract content will be displayed here -->
                                <div id="contractText"></div>

                                <!-- Signature section (only shown for unsigned contracts) -->
                                <div id="signatureSection" class="mt-4 d-none">
                                    <hr>
                                    <h5>Sign Contract</h5>
                                    <div class="mb-3">
                                        <label for="signatureName" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="signatureName" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="signatureDate" class="form-label">Date</label>
                                        <input type="date" class="form-control" id="signatureDate" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Signature</label>
                                        <div id="signaturePad" class="border rounded" style="height: 150px;"></div>
                                        <button type="button" id="clearSignature"
                                            class="btn btn-sm btn-secondary mt-2">Clear Signature</button>
                                    </div>
                                </div>

                                <!-- Signed info (only shown for signed contracts) -->
                                <div id="signedInfo" class="mt-4 d-none">
                                    <hr>
                                    <h5>Signature Details</h5>
                                    <p><strong>Signed By:</strong> <span id="signedByName"></span></p>
                                    <p><strong>Signed On:</strong> <span id="signedDate"></span></p>
                                    <div class="signature-display">
                                        <p class="mb-1"><strong>Signature:</strong></p>
                                        <img id="signatureImage" src="" class="img-fluid"
                                            style="max-width: 200px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" id="signContractBtn" class="btn btn-primary d-none">Sign Contract</button>
                    <button type="button" id="downloadContractBtn" class="btn btn-success d-none">
                        <i class="mdi mdi-download me-1"></i> Download
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!--/ Hoverable Table rows -->
    <!-- Add Contract Modal -->
    <div class="modal fade" id="largeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Contract</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="clientSelect" class="form-label">Select Client</label>
                            <select class="form-select" id="clientSelect">
                                <option value="">Select a client</option>
                                <option value="1">Client 1</option>
                                <option value="2">Client 2</option>
                                <option value="3">Client 3</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <ul class="nav nav-tabs" id="contractTypeTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="create-tab" data-bs-toggle="tab"
                                        data-bs-target="#create" type="button" role="tab" aria-controls="create"
                                        aria-selected="true">Create New</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="upload-tab" data-bs-toggle="tab"
                                        data-bs-target="#upload" type="button" role="tab" aria-controls="upload"
                                        aria-selected="false">Upload PDF</button>
                                </li>
                            </ul>
                            <div class="tab-content p-3 border border-top-0 rounded-bottom" id="contractTypeContent">
                                <div class="tab-pane fade show active" id="create" role="tabpanel"
                                    aria-labelledby="create-tab">
                                    <div id="contractEditor" style="min-height: 300px;"></div>
                                </div>
                                <div class="tab-pane fade" id="upload" role="tabpanel" aria-labelledby="upload-tab">
                                    <div class="mb-3">
                                        <label for="contractFile" class="form-label">Upload Contract PDF</label>
                                        <input class="form-control" type="file" id="contractFile" accept=".pdf">
                                    </div>
                                    <div class="alert alert-info">
                                        <small>Upload a PDF file if you don't want to create a new contract from
                                            scratch.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="contractTitle" class="form-label">Contract Title</label>
                            <input type="text" class="form-control" id="contractTitle"
                                placeholder="e.g., Service Agreement">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="expiryDate" class="form-label">Expiry Date (Optional)</label>
                            <input type="date" class="form-control" id="expiryDate">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveContractBtn">Save Contract</button>
                </div>
            </div>
        </div>
    </div>


@endsection
@push('my-styles')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <style>
        #signaturePad {
            border: 1px solid #ddd;
            background-color: #f8f9fa;
            cursor: crosshair;
        }

        .contract-content {
            max-height: 500px;
            overflow-y: auto;
        }

        .tox-tinymce {
            border-radius: 0 0 6px 6px !important;
        }

        .contract-tabs .nav-link {
            padding: 0.75rem 1.5rem;
        }
    </style>
@endpush

@push('my-script')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"
        src="https://cdn.tiny.cloud/1/YOUR_API_KEY/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        $(function() {
            $('#date-range-picker').daterangepicker({
                opens: 'right',
                locale: {
                    format: 'YYYY-MM-DD',
                    applyLabel: 'Apply',
                    cancelLabel: 'Cancel',
                    fromLabel: 'From',
                    toLabel: 'To',
                    customRangeLabel: 'Custom',
                    daysOfWeek: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
                    monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August',
                        'September', 'October', 'November', 'December'
                    ],
                    firstDay: 1
                }
            });
        });
    </script>
    <script>
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('tbody .form-check-input');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
        // Add this to your existing script section
    </script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable if you're using it
            // const table = $('#ordersTable').DataTable();

            // Filter functionality
            $('#breedFilter, #statusFilter, #orderIdFilter').on('change keyup', function() {
                const breed = $('#breedFilter').val().toLowerCase();
                const status = $('#statusFilter').val().toLowerCase();
                const orderId = $('#orderIdFilter').val().toLowerCase();

                $('#ordersTable tbody tr').each(function() {
                    const rowBreed = $(this).find('td:nth-child(3)').text().toLowerCase();
                    const rowStatus = $(this).find('td:nth-child(5)').text().toLowerCase();
                    const rowOrderId = $(this).find('td:nth-child(1)').text().toLowerCase();

                    const breedMatch = breed === '' || rowBreed.includes(breed);
                    const statusMatch = status === '' || rowStatus.includes(status);
                    const orderIdMatch = orderId === '' || rowOrderId.includes(orderId);

                    if (breedMatch && statusMatch && orderIdMatch) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize TinyMCE editor
            tinymce.init({
                selector: '#contractEditor',
                plugins: 'advlist autolink lists link image charmap print preview anchor',
                toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat',
                height: 300,
                menubar: false,
                skin: 'oxide',
                content_css: 'default',
                setup: function(editor) {
                    editor.on('change', function() {
                        editor.save();
                    });
                }
            });

            // Handle save contract button
            document.getElementById('saveContractBtn').addEventListener('click', function() {
                const clientId = document.getElementById('clientSelect').value;
                const contractTitle = document.getElementById('contractTitle').value;
                const expiryDate = document.getElementById('expiryDate').value;
                const activeTab = document.querySelector('#contractTypeTabs .nav-link.active').id;

                if (!clientId) {
                    alert('Please select a client');
                    return;
                }

                if (!contractTitle) {
                    alert('Please enter a contract title');
                    return;
                }

                if (activeTab === 'create-tab') {
                    // Get content from TinyMCE
                    const contractContent = tinymce.get('contractEditor').getContent();
                    if (!contractContent) {
                        alert('Please enter contract content');
                        return;
                    }

                    // Here you would send the data to your server
                    console.log('Creating new contract:', {
                        clientId,
                        contractTitle,
                        contractContent,
                        expiryDate
                    });

                    alert('Contract created successfully!');
                    $('#largeModal').modal('hide');

                } else if (activeTab === 'upload-tab') {
                    const fileInput = document.getElementById('contractFile');
                    if (!fileInput.files.length) {
                        alert('Please upload a contract file');
                        return;
                    }

                    const file = fileInput.files[0];
                    // Here you would handle the file upload
                    console.log('Uploading contract file:', {
                        clientId,
                        contractTitle,
                        file,
                        expiryDate
                    });

                    alert('Contract uploaded successfully!');
                    $('#largeModal').modal('hide');
                }
            });

            // Reset form when modal is closed
            $('#largeModal').on('hidden.bs.modal', function() {
                document.getElementById('clientSelect').value = '';
                document.getElementById('contractTitle').value = '';
                document.getElementById('expiryDate').value = '';
                document.getElementById('contractFile').value = '';
                tinymce.get('contractEditor').setContent('');
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            let signaturePad;

            // Initialize signature pad when modal is shown
            $('#contractDetailsModal').on('shown.bs.modal', function() {
                const canvas = document.getElementById('signaturePad');
                if (canvas) {
                    signaturePad = new SignaturePad(canvas, {
                        backgroundColor: 'rgb(255, 255, 255)',
                        penColor: '#ccc'
                    });
                }
            });

            // Clear signature
            $('#clearSignature').on('click', function() {
                if (signaturePad) {
                    signaturePad.clear();
                }
            });

            // View contract handler
            $(document).on('click', '.view-contract', function(e) {
                e.preventDefault();
                const contractId = $(this).data('contract-id');
                const status = $(this).data('status');
                const createdAt = $(this).data('created-at');
                const signedBy = $(this).data('signed-by') || '';
                const signedDate = $(this).data('signed-date') || '';
                const signatureImage = $(this).data('signature-image') || '';
                const contractText = $(this).data('contract-text');

                // Set modal content
                $('#contractId').text(contractId);
                $('#contractCreatedAt').text(createdAt);
                $('#contractText').html(contractText);

                // Set status badge
                const statusBadge = $('#contractStatus');
                statusBadge.text(status);
                statusBadge.removeClass('bg-label-primary bg-label-warning');

                if (status === 'Signed') {
                    statusBadge.addClass('bg-label-primary');
                    $('#signedInfo').removeClass('d-none');
                    $('#signatureSection').addClass('d-none');
                    $('#signedByName').text(signedBy);
                    $('#signedDate').text(signedDate);
                    $('#signatureImage').attr('src', signatureImage);
                    $('#signContractBtn').addClass('d-none');
                    $('#downloadContractBtn').removeClass('d-none');
                } else {
                    statusBadge.addClass('bg-label-warning');
                    $('#signedInfo').addClass('d-none');
                    $('#signatureSection').removeClass('d-none');
                    $('#signContractBtn').removeClass('d-none');
                    $('#downloadContractBtn').addClass('d-none');

                    // Set today's date as default
                    $('#signatureDate').val(new Date().toISOString().split('T')[0]);
                }

                // Show modal
                $('#contractDetailsModal').modal('show');
            });

            // Sign contract button handler
            $('#signContractBtn').on('click', function() {
                const signatureName = $('#signatureName').val();
                const signatureDate = $('#signatureDate').val();

                if (!signatureName || !signatureDate || (signaturePad && signaturePad.isEmpty())) {
                    alert('Please fill all fields and provide a signature');
                    return;
                }

                // Get signature as image
                const signatureData = signaturePad.toDataURL();

                // Here you would typically send this data to your server via AJAX
                // Example AJAX call:
                /*
                $.ajax({
                    url: '/contracts/' + $('#contractId').text() + '/sign',
                    method: 'POST',
                    data: {
                        name: signatureName,
                        date: signatureDate,
                        signature: signatureData
                    },
                    success: function(response) {
                        // Handle success
                        updateContractStatus(response);
                    },
                    error: function(error) {
                        // Handle error
                        alert('Error signing contract');
                    }
                });
                */

                // For demo purposes, we'll just update the UI
                updateContractStatus({
                    status: 'Signed',
                    signed_by: signatureName,
                    signed_date: signatureDate,
                    signature_image: signatureData
                });
            });

            function updateContractStatus(data) {
                // Update the modal
                $('#contractStatus').text('Signed').removeClass('bg-label-warning').addClass('bg-label-primary');
                $('#signedInfo').removeClass('d-none');
                $('#signatureSection').addClass('d-none');
                $('#signedByName').text(data.signed_by);
                $('#signedDate').text(data.signed_date);
                $('#signatureImage').attr('src', data.signature_image);
                $('#signContractBtn').addClass('d-none');
                $('#downloadContractBtn').removeClass('d-none');

                // Update the table row
                const contractId = $('#contractId').text();
                $(`[data-contract-id="${contractId}"]`).each(function() {
                    // Update the status badge in table
                    const row = $(this).closest('tr');
                    row.find('.badge')
                        .removeClass('bg-label-warning')
                        .addClass('bg-label-primary')
                        .text('Signed');

                    // Update the dropdown menu
                    const dropdown = row.find('.dropdown-menu');
                    dropdown.html(`
                      <a class="dropdown-item view-contract" href="javascript:void(0);"
                         data-contract-id="${contractId}"
                         data-status="Signed"
                         data-created-at="${$('#contractCreatedAt').text()}"
                         data-signed-by="${data.signed_by}"
                         data-signed-date="${data.signed_date}"
                         data-signature-image="${data.signature_image}"
                         data-contract-text="${$('#contractText').html()}">
                         <i class="mdi mdi-eye-outline me-1"></i> View
                      </a>
                      <a class="dropdown-item" href="javascript:void(0);">
                          <i class="mdi mdi-download me-1"></i> Download
                      </a>
                      <a class="dropdown-item" href="javascript:void(0);">
                          <i class="mdi mdi-pencil-outline me-1"></i> Edit
                      </a>
                  `);
                });

                alert('Contract signed successfully!');
            }

            // Download contract button handler
            $('#downloadContractBtn').on('click', function() {
                const contractId = $('#contractId').text();
                // Implement your download functionality here
                window.location.href = `/contracts/${contractId}/download`;
            });
        });
    </script>
@endpush
