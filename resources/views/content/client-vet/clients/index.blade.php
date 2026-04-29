@extends('layouts/contentNavbarLayout')

@section('title', 'Tables - Users')

@section('content')


    <div class="dashboard-shell">
        <div class="page-header-shell">
            <div>
                <h4 class="page-title"><span class="text-muted fw-light">Users /</span> Clients</h4>
                <p class="page-subtitle">Track client account details with a clear and responsive layout.</p>
            </div>
        </div>
    </div>


    <!-- Hoverable Table rows -->
    <div class="dashboard-shell">
        <div class="table-card">
            <div class="table-card-header">
                <div>
                    <h5 class="table-card-title">Client Overview</h5>
                    <p class="table-card-subtitle">Quick visibility into contact details and current product status.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-premium">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone #</th>
                        <th>Email</th>
                        <th>Product Status</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    <tr>
                        <td><span class="fw-medium">Tours Project</span></td>
                        <td class="small-text">0322-544-2821</td>
                        <td class="small-text">admin@admin.com</td>
                        <td class="small-text"><span class="badge badge-soft rounded-pill bg-label-success me-1">Active</span></td>
                    </tr>
                    <tr>
                        <td><span class="fw-medium">Tours Project</span></td>
                        <td class="small-text">0322-544-2821</td>
                        <td class="small-text">admin@admin.com</td>
                        <td class="small-text"><span class="badge badge-soft rounded-pill bg-label-success me-1">Active</span></td>
                    </tr>
                    <tr>
                        <td><span class="fw-medium">Tours Project</span></td>
                        <td class="small-text">0322-544-2821</td>
                        <td class="small-text">admin@admin.com</td>
                        <td class="small-text"><span class="badge badge-soft rounded-pill bg-label-warning me-1">Pending</span></td>
                    </tr>
                    <tr>
                        <td><span class="fw-medium">Tours Project</span></td>
                        <td class="small-text">0322-544-2821</td>
                        <td class="small-text">admin@admin.com</td>
                        <td class="small-text"><span class="badge badge-soft rounded-pill bg-label-warning me-1">Pending</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    </div>

    <div class="modal fade" id="largeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="exampleModalLabel3">New Client</h4>
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
    <!--/ Hoverable Table rows -->



@endsection

@push('my-script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
