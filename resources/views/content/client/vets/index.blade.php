@extends('layouts/contentNavbarLayout')

@section('title', 'Tables - Veterans')

@section('content')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="py-3"><span class="text-muted fw-light">Users /</span> Veterans
            </h4>
        </div>
        <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#largeModal">
                Add new Vet
            </button>
        </div>

    </div>
    <!-- Hoverable Table rows -->
    <div class="card">
        <div class="table-responsive text-nowrap">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone #</th>
                        <th>Email</th>
                        <th>Client Name</th>
                        <th>Actions</th>

                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    <tr>
                        <td><span class="fw-medium">Tours
                                Project</span></td>
                        <td class="small-text">0322-544-2821</td>
                        <td class="small-text">admin@admin.com</td>
                        <td class="small-text">Albert Cook</td>

                        <td class="small-text">
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown"><i class="mdi mdi-dots-vertical"></i></button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="javascript:void(0);"><i
                                            class="mdi mdi-pencil-outline me-1"></i> Edit</a>
                                    <a class="dropdown-item" href="javascript:void(0);"><i
                                            class="mdi mdi-trash-can-outline me-1"></i> Delete</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="fw-medium">Sports
                                Project</span></td>
                        <td class="small-text">0322-544-2821</td>
                        <td class="small-text">client1@client1.com</td>
                        <td class="small-text">Albert Cook</td>


                        <td class="small-text">
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown"><i class="mdi mdi-dots-vertical"></i></button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="javascript:void(0);"><i
                                            class="mdi mdi-pencil-outline me-1"></i> Edit</a>
                                    <a class="dropdown-item" href="javascript:void(0);"><i
                                            class="mdi mdi-trash-can-outline me-1"></i> Delete</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="fw-medium">Greenhouse
                                Project</span></td>
                        <td class="small-text">0322-544-2821</td>
                        <td class="small-text">client2@client2.com</td>
                        <td class="small-text">Albert Cook</td>

                        <td class="small-text">
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown"><i class="mdi mdi-dots-vertical"></i></button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="javascript:void(0);"><i
                                            class="mdi mdi-pencil-outline me-1"></i> Edit</a>
                                    <a class="dropdown-item" href="javascript:void(0);"><i
                                            class="mdi mdi-trash-can-outline me-1"></i> Delete</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="fw-medium">Bank
                                Project</span></td>
                        <td class="small-text">0322-544-2821</td>
                        <td class="small-text">client3@client3.com</td>
                        <td class="small-text">Albert Cook</td>

                        <td>
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown"><i class="mdi mdi-dots-vertical"></i></button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="javascript:void(0);"><i
                                            class="mdi mdi-pencil-outline me-1"></i> Edit</a>
                                    <a class="dropdown-item" href="javascript:void(0);"><i
                                            class="mdi mdi-trash-can-outline me-1"></i> Delete</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <!--/ Hoverable Table rows -->

    <div class="modal fade" id="largeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="exampleModalLabel3">New Veteran</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="input-group input-group-merge mb-4">
                            <span id="basic-icon-default-fullname2" class="input-group-text"><i
                                    class="mdi mdi-account-outline"></i></span>
                            <input type="text" class="form-control" id="basic-icon-default-fullname"
                                placeholder="Full Name" aria-label="Full Name"
                                aria-describedby="basic-icon-default-fullname2" />
                        </div>

                        <div class="mb-4">
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="mdi mdi-email-outline"></i></span>
                                <input type="text" id="basic-icon-default-email" class="form-control"
                                    placeholder="Email" aria-label="Email"
                                    aria-describedby="basic-icon-default-email2" />
                                <span id="basic-icon-default-email2" class="input-group-text">@example.com</span>
                            </div>
                            <div class="form-text"> You can use letters, numbers & periods </div>
                        </div>
                        <div class="input-group input-group-merge mb-4">
                            <span id="basic-icon-default-password2" class="input-group-text"><i
                                    class="mdi mdi-lock-outline"></i></span>
                            <input type="password" id="basic-icon-default-password" class="form-control"
                                placeholder="Password" aria-label="Password"
                                aria-describedby="basic-icon-default-password2" />
                            <span class="input-group-text cursor-pointer" id="togglePassword">
                                <i class="mdi mdi-eye-outline" id="togglePasswordIcon"></i>
                            </span>
                        </div>
                        <div class="input-group input-group-merge mb-4">
                            <span id="basic-icon-default-phone2" class="input-group-text"><i
                                    class="mdi mdi-phone"></i></span>
                            <input type="text" id="basic-icon-default-phone" class="form-control phone-mask"
                                placeholder="Phone No" aria-label="Phone No"
                                aria-describedby="basic-icon-default-phone2" />
                        </div>
                        {{-- <div class="input-group input-group-merge mb-4">
                            <span class="input-group-text" id="client-icon">
                                <i class="mdi mdi-account-outline"></i>
                            </span>
                            <select class="form-select" aria-label="Default select example"
                                aria-placeholder="Select Client">
                                <option value="" disabled selected hidden>Select a client</option>
                                <option value="1">John Doe</option>
                                <option value="2">Jane Smith</option>
                                <option value="3">Michael Johnson</option>
                                <option value="4">Emily Davis</option>
                                <option value="5">Chris Thompson</option>
                            </select>

                        </div> --}}

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
@endsection



@push('my-script')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            $('#clientSelect').select({
                dropdownParent: $('#largeModal'),
                placeholder: 'Select a client',
                allowClear: true
            });
            const togglePassword = document.querySelector('#togglePassword');
            const password = document.querySelector('#basic-icon-default-password');
            const togglePasswordIcon = document.querySelector('#togglePasswordIcon');

            togglePassword.addEventListener('click', function() {

                // Toggle the type attribute
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);

                // Toggle the eye icon
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
