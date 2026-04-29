@extends('layouts/contentNavbarLayout')

@section('title', 'Tables - Users')
@section('page-script')
    <script src="{{ asset('assets/js/pages-account-settings-account.js') }}"></script>
@endsection
@section('content')


    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="py-3"><span class="text-muted fw-light">Products</span>
            </h4>
        </div>
        <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#largeModal">
               Upload Products Forms
            </button>
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

                        <th>Name</th>
                        <th>Breed</th>
                        <th>Quantity</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input">
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="https://images.squarespace-cdn.com/content/v1/592074edbe659438f08000b0/1735066890317-Q0JZ3EK311O0NP0L9LQC/coming-soon-store-placeholder-image.gif?format=1000w"
                                    class="rounded-circle me-3" alt="Product Image" width="40" height="40">
                                <span class="fw-medium">Arainy</span>
                            </div>
                        </td>
                        <td class="small-text">Arabian</td>
                        <td class="small-text">5</td>
                        <td class="small-text">2023-05-15</td>
                        <td>
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown"><i class="mdi mdi-dots-vertical"></i></button>
                                <div class="dropdown-menu">
                                            <a class="dropdown-item"
                                     data-bs-toggle="modal" data-bs-target="#largeModal2"><i
                                            class="mdi mdi-trash-can-outline me-1"></i>Edit Request Form</a>
                                 </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input">
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="https://i.pinimg.com/736x/d5/a0/cf/d5a0cfb3c8a7213d2edb74e6861f7660.jpg"
                                    class="rounded-circle me-3" alt="Product Image" width="40" height="40">
                                <span class="fw-medium">Shomou</span>
                            </div>

                        </td>

                        <td class="small-text">Angus</td>
                        <td class="small-text">12</td>
                        <td class="small-text">2023-06-20</td>
                        <td>
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown"><i class="mdi mdi-dots-vertical"></i></button>
                                <div class="dropdown-menu">
                                            <a class="dropdown-item"
                                     data-bs-toggle="modal" data-bs-target="#largeModal2"><i
                                            class="mdi mdi-trash-can-outline me-1"></i>Edit Request Form</a>
                                 </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input">
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT_5LKdbLhvQ7Y2w6vwtPwRz8yJ2ybMyZdsww&s"
                                    class="rounded-circle me-3" alt="Product Image" width="40" height="40">
                                <span class="fw-medium">Ranatunga</span>
                            </div>
                        </td>


                        <td class="small-text">Merino</td>
                        <td class="small-text">8</td>
                        <td class="small-text">2023-07-10</td>
                        <td>
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown"><i class="mdi mdi-dots-vertical"></i></button>
                                <div class="dropdown-menu">
                                            <a class="dropdown-item"
                                     data-bs-toggle="modal" data-bs-target="#largeModal2"><i
                                            class="mdi mdi-trash-can-outline me-1"></i>Edit Request Form</a>
                                 </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input">
                        </td>

                        <td>
                            <div class="d-flex align-items-center">
                                <img src="https://carutharabians.com/wp-content/uploads/2023/05/CA-Rromance-_3.webp"
                                    class="rounded-circle me-3" alt="Product Image" width="40" height="40">
                                <span class="fw-medium">Keene</span>
                            </div>
                        </td>

                        <td class="small-text">Rhode Island Red</td>
                        <td class="small-text">25</td>
                        <td class="small-text">2023-08-05</td>
                        <td>
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown"><i class="mdi mdi-dots-vertical"></i></button>
                                <div class="dropdown-menu">
                                            <a class="dropdown-item"
                                     data-bs-toggle="modal" data-bs-target="#largeModal2"><i
                                            class="mdi mdi-trash-can-outline me-1"></i>Edit Request Form</a>
                                 </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="largeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="exampleModalLabel3">PRODUCT REQUEST</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="d-flex align-items-start align-items-sm-center gap-4">
                            <img src="{{ asset('assets/img/avatars/1.png') }}" alt="user-avatar"
                                class="d-block w-px-120 h-px-120 rounded" id="uploadedAvatar" />
                            <div class="button-wrapper">
                                <label for="upload" class="btn btn-primary me-2 mb-3" tabindex="0">
                                    <span class="d-none d-sm-block">Add Photo</span>
                                    <i class="mdi mdi-tray-arrow-up d-block d-sm-none"></i>
                                    <input type="file" id="upload" class="account-file-input" hidden
                                        accept="image/png, image/jpeg" />
                                </label>
                                <button type="button" class="btn btn-outline-danger account-image-reset mb-3">
                                    <i class="mdi mdi-reload d-block d-sm-none"></i>
                                    <span class="d-none d-sm-block">Reset</span>
                                </button>

                                <div class="text-muted small">Allowed JPG, GIF or PNG. Max size of 800K</div>
                            </div>
                        </div>
                        <div class="input-group input-group-merge mb-4 mt-4">
                            <span id="basic-icon-default-fullname2" class="input-group-text"><i
                                    class="mdi mdi-account-outline"></i></span>
                            <input type="text" class="form-control" id="basic-icon-default-fullname"
                                placeholder="Product Name" aria-label="Product Name"
                                aria-describedby="basic-icon-default-fullname2" />
                        </div>
                        <div class="input-group input-group-merge mb-4">
                            <span class="input-group-text" id="client-icon">
                                <i class="mdi mdi-account-outline"></i>
                            </span>
                            <select class="form-select" aria-label="Default select example"
                                aria-placeholder="Horse Breed">
                                <option value="" disabled selected hidden>Select a Breed</option>
                                <option value="1">John Doe</option>
                                <option value="2">Jane Smith</option>
                                <option value="3">Michael Johnson</option>
                                <option value="4">Emily Davis</option>
                                <option value="5">Chris Thompson</option>
                            </select>

                        </div>
                        <div class="input-group input-group-merge mb-4">
                            <span class="input-group-text">
                                <i class="mdi mdi-calendar"></i>
                            </span>
                            <input type="text" class="form-control" id="date-range-picker"
                                placeholder="Select date range">
                        </div>

                        <div class="input-group input-group-merge mb-4">
                            <span id="basic-icon-default-phone2" class="input-group-text"><i
                                    class="mdi mdi-calculator-variant-outline"></i></span>
                            <input type="text" id="basic-icon-default-phone" class="form-control phone-mask"
                                placeholder="Quantity" aria-label="Quantity"
                                aria-describedby="basic-icon-default-phone2" />
                        </div>
                        {{-- <div class="input-group input-group-merge mb-4">
                            <span id="basic-icon-default-message2" class="input-group-text"><i
                                    class="mdi mdi-message-outline"></i></span>
                            <textarea id="basic-icon-default-message" class="form-control" placeholder="Message" aria-label="Message"
                                aria-describedby="basic-icon-default-message2" style="height: 60px;"></textarea>
                        </div> --}}
                        {{-- <button type="submit" class="btn btn-primary">Send</button> --}}
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="largeModal2" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="exampleModalLabel3">Product Request </h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                <div class="modal-footer">
                    <button type="button" onclick="toggleInputs()" class="btn btn-outline-secondary"
                     >Edit</button>


                </div>
                    <form>

                        <div class="input-group input-group-merge mb-4 mt-2">
                            <span id="basic-icon-default-fullname2" class="input-group-text"><i
                                    class="mdi mdi-account-outline"></i></span>
                            <input type="text" class="form-control pl-3 toggle-input" style="padding-left: 12px;"  disabled id="basic-icon-default-fullname"
                                placeholder="Product Name" value="Product Name" aria-label="Product Name"
                                aria-describedby="basic-icon-default-fullname2" />
                        </div>

                        <div class="input-group input-group-merge mb-4">
                            <span class="input-group-text">
                                <i class="mdi mdi-calendar"></i>
                            </span>
                            <input type="text" class="form-control toggle-input" style="padding-left:12px;"  disabled  id="date-range-picker"
                                placeholder=" Shipping Address" value="Shipping Address">
                        </div>

                        <div class="input-group input-group-merge mb-4">
                            <span id="basic-icon-default-phone2" class="input-group-text"><i
                                    class="mdi mdi-calculator-variant-outline"></i></span>
                            <input type="text" id="basic-icon-default-phone"  style="padding-left:12px;"  disabled  class=" form-control toggle-input phone-mask"
                                placeholder="Special Instructions"
                                aria-label="Quantity"
                                value="Special Instructions"
                                aria-describedby="basic-icon-default-phone2" />
                        </div>
                        {{-- <div class="input-group input-group-merge mb-4">
                            <span id="basic-icon-default-message2" class="input-group-text"><i
                                    class="mdi mdi-message-outline"></i></span>
                            <textarea id="basic-icon-default-message" class="form-control"
                            value="Message"
                            placeholder="Message" aria-label="Message"
                                aria-describedby="basic-icon-default-message2" style="height: 160px;"></textarea>
                        </div> --}}
                        {{-- <button type="submit" class="btn btn-primary">Send</button> --}}
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary"
                     data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary">Submit Request</button>
                </div>
            </div>
        </div>
    </div>
    <!--/ Hoverable Table rows -->



@endsection
@push('my-styles')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endpush

@push('my-script')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
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
    </script>
    <script>
    let isDisabled = true;

    function toggleInputs() {
      const inputs = document.querySelectorAll(".toggle-input");
      const status = document.getElementById("status");

      isDisabled = !isDisabled;

      inputs.forEach(input => {
        input.disabled = isDisabled;
      });

      status.textContent = "Disabled: " + isDisabled;
      console.log("Disabled:", isDisabled);
    }
  </script>
@endpush
