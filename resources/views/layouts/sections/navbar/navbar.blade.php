@php
    $containerNav = $containerNav ?? 'container-fluid';
    $navbarDetached = $navbarDetached ?? '';

@endphp

<!-- Navbar -->
@if (isset($navbarDetached) && $navbarDetached == 'navbar-detached')
    <nav class="layout-navbar {{ $containerNav }} navbar navbar-expand-xl {{ $navbarDetached }} align-items-center bg-navbar-theme"
        id="layout-navbar">
@endif
@if (isset($navbarDetached) && $navbarDetached == '')
    <nav class="layout-navbar navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
        <div class="{{ $containerNav }}">
@endif

<!--  Brand demo (display only for navbar-full and hide on below xl) -->
@if (isset($navbarFull))
    <div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-4">
        <a href="{{ url('/') }}" class="app-brand-link gap-2">
            <span class="app-brand-logo demo">
                @include('_partials.macros', ['height' => 20])
            </span>
            <span class="app-brand-text demo menu-text fw-semibold ms-1">{{ config('variables.templateName') }}</span>
        </a>
        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="mdi menu-toggle-icon d-xl-block align-middle mdi-20px"></i>
        </a>
    </div>
@endif

<!-- ! Not required for layout-without-menu -->
@if (!isset($navbarHideToggle))
    <div
        class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0{{ isset($menuHorizontal) ? ' d-xl-none ' : '' }} {{ isset($contentNavbar) ? ' d-xl-none ' : '' }}">
        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
            <i class="mdi mdi-menu mdi-24px"></i>
        </a>
    </div>
@endif

<div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
    <!-- Search -->
    <div class="navbar-nav align-items-center">
        <div class="nav-item d-flex align-items-center">
            <i class="mdi mdi-magnify mdi-24px lh-0"></i>
            <input type="text" class="form-control border-0 shadow-none bg-body" placeholder="Search..."
                aria-label="Search...">
        </div>
    </div>
    <!-- /Search -->
    <ul class="navbar-nav flex-row align-items-center ms-auto">

        <!-- Place this tag where you want the button to render. -->
        <li class="nav-item lh-1 me-3">
            <a class="github-button"
                href="https://github.com/themeselection/materio-bootstrap-html-laravel-admin-template-free"
                data-icon="octicon-star" data-size="large" data-show-count="true"
                aria-label="Star themeselection/materio-bootstrap-html-laravel-admin-template-free on GitHub">Star</a>
        </li>
        <!-- Notification -->
        <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3 ">
            <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                <span class="mdi mdi-bell-outline"></span>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    5
                    <span class="visually-hidden">unread notifications</span>
                </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end py-0 w-px-400">
                <li class="dropdown-menu-header border-bottom">
                    <div class="dropdown-header d-flex align-items-center py-3">
                        <h5 class="text-body mb-0 me-auto">Notifications</h5>
                        <a href="javascript:void(0)" class="dropdown-notifications-all text-body"
                            data-bs-toggle="tooltip" data-bs-placement="top" title="Mark all as read">
                            <i class="mdi mdi-mailbox-outline fs-4"></i>
                        </a>
                    </div>
                </li>
                <li class="dropdown-notifications-list scrollable-container">
                    <ul class="list-group list-group-flush">
                        <!-- Notification item with image -->
                        <li class="list-group-item list-group-item-action dropdown-notifications-item">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar">
                                        <img src="{{ asset('assets/img/avatars/5.png') }}" alt
                                            class="h-auto rounded-circle">
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">New message from Jane</h6>
                                    <p class="mb-0">Hey there, how are you doing? Just checking in...</p>
                                    <small class="text-muted">Today, 10:30 AM</small>
                                </div>
                                <div class="flex-shrink-0 dropdown-notifications-actions">
                                    <a href="javascript:void(0)" class="dropdown-notifications-read">
                                        <span class="badge badge-dot bg-success"></span>
                                    </a>
                                </div>
                            </div>
                        </li>

                        <!-- Notification item with icon -->
                        <li class="list-group-item list-group-item-action dropdown-notifications-item">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar">
                                        <span class="avatar-initial rounded-circle bg-label-warning">
                                            <i class="mdi mdi-alert-circle-outline"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">System Alert</h6>
                                    <p class="mb-0">Your monthly storage is almost full (85% used)</p>
                                    <small class="text-muted">Yesterday, 3:45 PM</small>
                                </div>
                                <div class="flex-shrink-0 dropdown-notifications-actions">
                                    <a href="javascript:void(0)" class="dropdown-notifications-read">
                                        <span class="badge badge-dot bg-danger"></span>
                                    </a>
                                </div>
                            </div>
                        </li>

                        <!-- Notification item -->
                        <li class="list-group-item list-group-item-action dropdown-notifications-item">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar">
                                        <span class="avatar-initial rounded-circle bg-label-success">
                                            <i class="mdi mdi-cart-outline"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">Order Completed</h6>
                                    <p class="mb-0">Your order #12345 has been shipped</p>
                                    <small class="text-muted">May 12, 2024</small>
                                </div>
                                <div class="flex-shrink-0 dropdown-notifications-actions">
                                    <a href="javascript:void(0)" class="dropdown-notifications-read">
                                        <span class="badge badge-dot bg-success"></span>
                                    </a>
                                </div>
                            </div>
                        </li>

                        <!-- More notifications can be added here -->
                    </ul>
                </li>
                <li class="dropdown-menu-footer border-top">
                    <a href="javascript:void(0);" class="dropdown-item d-flex justify-content-center p-3">
                        View all notifications
                    </a>
                </li>
            </ul>
        </li>
        <!-- User -->
        <li class="nav-item navbar-dropdown dropdown-user dropdown">
            <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
                <div class="avatar avatar-online">
                    <img src="{{ asset('assets/img/avatars/1.png') }}" alt class="w-px-40 h-auto rounded-circle">
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end mt-3 py-2">
                <li>
                    <a class="dropdown-item pb-2 mb-1" href="javascript:void(0);">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-2 pe-1">
                                <div class="avatar avatar-online">
                                    <img src="{{ asset('assets/img/avatars/1.png') }}" alt
                                        class="w-px-40 h-auto rounded-circle">
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0">John Doe</h6>
                                <small class="text-muted">Admin</small>
                            </div>
                        </div>
                    </a>
                </li>
                <li>
                    <div class="dropdown-divider my-1"></div>
                </li>
                <li>
                    <a class="dropdown-item" href="javascript:void(0);">
                        <i class="mdi mdi-account-outline me-1 mdi-20px"></i>
                        <span class="align-middle">My Profile</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="pages/account-settings-account">
                        <i class='mdi mdi-cog-outline me-1 mdi-20px'></i>
                        <span class="align-middle">Settings</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="javascript:void(0);">
                        <span class="d-flex align-items-center align-middle">
                            <i class="flex-shrink-0 mdi mdi-credit-card-outline me-1 mdi-20px"></i>
                            <span class="flex-grow-1 align-middle ms-1">Billing</span>
                            <span
                                class="flex-shrink-0 badge badge-center rounded-pill bg-danger w-px-20 h-px-20">4</span>
                        </span>
                    </a>
                </li>
                <li>
                    <div class="dropdown-divider my-1"></div>
                </li>
                <li>
                    <form action="{{route('logout')}}" method="POST">
                        @csrf
                        <button type="submit" class="dropdown-item "><i class="bx bx-power-off font-size-16 align-middle me-1"></i> <span key="t-logout">Logout</span></button>
                    </form>
                </li>
            </ul>
        </li>
        <!--/ User -->
    </ul>
</div>

@if (!isset($navbarDetached))
    </div>
@endif
</nav>
<!-- / Navbar -->
