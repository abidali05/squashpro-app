<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="{{ url('/') }}" class="app-brand-link">
            <span class="app-brand-logo demo me-1">
                @include('_partials.macros', ['height' => 20, 'color' => '#B5F23C'])
            </span>
            <span class="app-brand-text demo menu-text fw-semibold ms-2">Squash Pro</span>
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
            <i class="mdi menu-toggle-icon d-xl-block align-middle mdi-20px"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        <li class="menu-item {{ request()->routeIs('admin.dashboard.index') ? 'active' : '' }}">
            <a href="{{ route('admin.dashboard.index') }}" class="menu-link">
                <i class="menu-icon tf-icons mdi mdi-home-outline"></i>
                <div>Dashboard</div>
            </a>
        </li>

        <li class="menu-item {{ request()->routeIs('admin.clubs.*') || request()->routeIs('admin.courts.*') || request()->routeIs('admin.memberships.*') || request()->routeIs('admin.membership-requests.*') ? 'open' : '' }}">
            <a href="#" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons mdi mdi-domain"></i>
                <div>Club Management</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('admin.clubs.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.clubs.index') }}" class="menu-link"><div>Clubs</div></a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.courts.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.courts.index') }}" class="menu-link"><div>Courts</div></a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.memberships.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.memberships.index') }}" class="menu-link"><div>Memberships</div></a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.membership-requests.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.membership-requests.index') }}" class="menu-link"><div>Membership Requests</div></a>
                </li>
            </ul>
        </li>

        <li class="menu-item {{ request()->routeIs('admin.players.*') ? 'active' : '' }}">
            <a href="{{ route('admin.players.index') }}" class="menu-link">
                <i class="menu-icon tf-icons mdi mdi-account-group-outline"></i>
                <div>Player Management</div>
            </a>
        </li>

        <li class="menu-item {{ request()->routeIs('admin.bookings.*') || request()->routeIs('admin.booking-reviews.*') ? 'open' : '' }}">
            <a href="#" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons mdi mdi-calendar-check-outline"></i>
                <div>Booking Management</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('admin.bookings.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.bookings.index') }}" class="menu-link"><div>Bookings</div></a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.booking-reviews.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.booking-reviews.index') }}" class="menu-link"><div>Reviews & Ratings</div></a>
                </li>
            </ul>
        </li>

        <li class="menu-item {{ request()->routeIs('admin.tournaments.*') || request()->routeIs('admin.tournament-registrations.*') || request()->routeIs('admin.tournament-rules.*') || request()->routeIs('admin.tournament-pools.*') || request()->routeIs('admin.fixtures.*') ? 'open' : '' }}">
            <a href="#" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons mdi mdi-trophy-outline"></i>
                <div>Tournament Management</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('admin.tournaments.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.tournaments.index') }}" class="menu-link"><div>Tournaments</div></a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.tournament-registrations.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.tournament-registrations.index') }}" class="menu-link"><div>Registrations & Teams</div></a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.tournament-rules.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.tournament-rules.index') }}" class="menu-link"><div>Tournament Rules</div></a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.tournament-pools.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.tournament-pools.index') }}" class="menu-link"><div>Tournament Pools</div></a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.fixtures.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.fixtures.index') }}" class="menu-link"><div>Fixtures Management</div></a>
                </li>
            </ul>
        </li>

        <li class="menu-item {{ request()->routeIs('admin.payments.*') || request()->routeIs('admin.revenue.*') ? 'open' : '' }}">
            <a href="#" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons mdi mdi-cash-multiple"></i>
                <div>Finance</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.payments.index') }}" class="menu-link"><div>Payments</div></a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.revenue.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.revenue.index') }}" class="menu-link"><div>Revenue Reports</div></a>
                </li>
            </ul>
        </li>

        <li class="menu-item {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}">
            <a href="{{ route('admin.notifications.index') }}" class="menu-link">
                <i class="menu-icon tf-icons mdi mdi-bell-outline"></i>
                <div>Notifications</div>
            </a>
        </li>

        <li class="menu-item {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
            <a href="{{ route('admin.reports.index') }}" class="menu-link">
                <i class="menu-icon tf-icons mdi mdi-chart-box-outline"></i>
                <div>Reports</div>
            </a>
        </li>

        <li class="menu-item {{ request()->routeIs('admin.support-options.*') || request()->routeIs('admin.privacy-policy.*') ? 'open' : '' }}">
            <a href="#" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons mdi mdi-file-document-edit-outline"></i>
                <div>Content Management</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('admin.support-options.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.support-options.index') }}" class="menu-link"><div>Help & Support</div></a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.privacy-policy.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.privacy-policy.edit') }}" class="menu-link"><div>Privacy Policy</div></a>
                </li>
            </ul>
        </li>

        <li class="menu-item {{ request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') || request()->routeIs('admin.users.*') ? 'open' : '' }}">
            <a href="#" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons mdi mdi-shield-account-outline"></i>
                <div>Access Control</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.roles.index') }}" class="menu-link"><div>Roles</div></a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.permissions.index') }}" class="menu-link"><div>Permissions</div></a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.users.index') }}" class="menu-link"><div>Users</div></a>
                </li>
            </ul>
        </li>

        <li class="menu-item {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}">
            <a href="{{ route('admin.audit-logs.index') }}" class="menu-link">
                <i class="menu-icon tf-icons mdi mdi-history"></i>
                <div>Activity Logs</div>
            </a>
        </li>

        <li class="menu-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
            <a href="{{ route('admin.settings.index') }}" class="menu-link">
                <i class="menu-icon tf-icons mdi mdi-cog-outline"></i>
                <div>Settings</div>
            </a>
        </li>
    </ul>
</aside>
