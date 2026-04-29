<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <!-- ! Hide app brand if navbar-full -->
    <div class="app-brand demo">
        <a href="{{ url('/') }}" class="app-brand-link">
            <span class="app-brand-logo demo me-1">
                @include('_partials.macros', ['height' => 20])
            </span>
            <span class="app-brand-text demo menu-text fw-semibold ms-2">{{ config('variables.templateName') }}</span>
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
            <i class="mdi menu-toggle-icon d-xl-block align-middle mdi-20px"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
       @php
           $user = \Auth::user();
           $url = '';
           if($user->hasRole("admin")){
                $url = route("admin.dashboard.index");
           }elseif($user->hasRole("admin_vet")){
                $url = route("adminVet.dashboard.index");
           }elseif($user->hasRole("client")){
                $url = route("client.dashboard.index");
           }elseif($user->hasRole("client_vet")){
                $url = route("clientVet.dashboard.index");
           }
       @endphp
        <li class="menu-item
        @if(request()->routeIs("admin.dashboard.index") || request()->routeIs("adminVet.dashboard.index") || request()->routeIs("client.dashboard.index") || request()->routeIs("clientVet.dashboard.index"))
           active
        @endif
        ">
            <a href="{{$url}}" class="menu-link">
                <i class="menu-icon tf-icons mdi mdi-home-outline"></i>
                <div>Dashboard</div>
            </a>
        </li>

        @if($user->hasRole("admin"))
            <li class="menu-item
            @if(request()->routeIs("admin.clients.index") || request()->routeIs("admin.vets.index"))
            open
            @endif
            ">
                <a href="#" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons mdi mdi-account-outline"></i>
                    <div>Manage Users</div>
                </a>

                {{-- submenu --}}
                <ul class="menu-sub">
                    <li class="menu-item @if(request()->routeIs("admin.clients.index")) active @endif">
                        <a href="{{route("admin.clients.index")}}" class="menu-link">
                            <div>Manage Clients</div>
                        </a>
                    </li>
                    <li class="menu-item @if(request()->routeIs("admin.vets.index")) active @endif">
                        <a href="{{route("admin.vets.index")}}" class="menu-link">
                            <div>Manage Vets</div>
                        </a>
                    </li>
                </ul>
            </li>

            <li class="menu-item @if(request()->routeIs("admin.settings.index")) active @endif">
                <a href="{{route("admin.settings.index")}}" class="menu-link">
                    <i class="menu-icon tf-icons mdi mdi-cog"></i>
                    <div>Settings</div>
                </a>
            </li>

            <li class="menu-item @if(request()->routeIs("admin.contracts.index")) active @endif">
                <a href="{{route("admin.contracts.index")}}" class="menu-link">
                    <i class="menu-icon tf-icons mdi mdi-file-sign"></i>
                    <div>Contracts</div>
                </a>
            </li>

            <li class="menu-item
            @if(request()->routeIs("admin.products.index") || request()->routeIs("admin.orders.index"))
            open
            @endif
            ">
                <a href="#" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons mdi mdi-horse"></i>
                    <div>Ecommerce</div>
                </a>

                {{-- submenu --}}
                <ul class="menu-sub">
                    <li class="menu-item @if(request()->routeIs("admin.products.index")) active @endif">
                        <a href="{{route("admin.products.index")}}" class="menu-link">
                            <div>Products</div>
                        </a>
                    </li>
                    <li class="menu-item @if(request()->routeIs("admin.orders.index")) active @endif">
                        <a href="{{route("admin.orders.index")}}" class="menu-link">
                            <div>Orders</div>
                        </a>
                    </li>
                </ul>
            </li>

            <li class="menu-item @if(request()->routeIs("admin.seasons.index")) active @endif">
                <a href="{{route('admin.seasons.index')}}" class="menu-link">
                    <i class="menu-icon tf-icons mdi mdi-file-sign"></i>
                    <div>Seasons</div>
                </a>
            </li>

        @elseif($user->hasRole("client"))
            <li class="menu-item @if(request()->routeIs("client.contracts.index")) active @endif">
                <a href="{{route("client.contracts.index")}}" class="menu-link">
                    <i class="menu-icon tf-icons mdi mdi-file-sign"></i>
                    <div>Contracts</div>
                </a>
            </li>

            <li class="menu-item @if(request()->routeIs("client.seasons.index")) active @endif">
                <a href="{{route("client.seasons.index")}}" class="menu-link">
                    <i class="menu-icon tf-icons mdi mdi-file-sign"></i>
                    <div>Seasons</div>
                </a>
            </li>

            <li class="menu-item @if(request()->routeIs("client.vets.index")) active @endif">
                <a href="{{route("client.vets.index")}}" class="menu-link">
                    <i class="menu-icon tf-icons mdi mdi-file-sign"></i>
                    <div>Vets</div>
                </a>
            </li>

            <li class="menu-item
            @if(request()->routeIs("client.products.index") || request()->routeIs("client.orders.index"))
            open
            @endif
            ">
                <a href="#" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons mdi mdi-horse"></i>
                    <div>Ecommerce</div>
                </a>

                {{-- submenu --}}
                <ul class="menu-sub">
                    <li class="menu-item @if(request()->routeIs("client.products.index")) active @endif">
                        <a href="{{route("client.products.index")}}" class="menu-link">
                            <div>Products</div>
                        </a>
                    </li>
                    <li class="menu-item @if(request()->routeIs("client.orders.index")) active @endif">
                        <a href="{{route("client.orders.index")}}" class="menu-link">
                            <div>Orders</div>
                        </a>
                    </li>
                </ul>
            </li>
        @elseif($user->hasRole("client_vet"))
            <li class="menu-item @if(request()->routeIs("clientVet.clients.index")) active @endif">
                <a href="{{route("clientVet.clients.index")}}" class="menu-link">
                    <i class="menu-icon tf-icons mdi mdi-file-sign"></i>
                    <div>Clients</div>
                </a>
            </li>

            <li class="menu-item @if(request()->routeIs("clientVet.seasons.index")) active @endif">
                <a href="{{route("clientVet.seasons.index")}}" class="menu-link">
                    <i class="menu-icon tf-icons mdi mdi-file-sign"></i>
                    <div>Seasons</div>
                </a>
            </li>

            <li class="menu-item @if(request()->routeIs("clientVet.orders.index")) active @endif">
                <a href="{{route("clientVet.orders.index")}}" class="menu-link">
                    <i class="menu-icon tf-icons mdi mdi-file-sign"></i>
                    <div>Orders</div>
                </a>
            </li>

        @elseif($user->hasRole("admin_vet"))
            <li class="menu-item @if(request()->routeIs("adminVet.contracts.index")) active @endif">
                <a href="{{route("adminVet.contracts.index")}}" class="menu-link">
                    <i class="menu-icon tf-icons mdi mdi-file-sign"></i>
                    <div>Contracts</div>
                </a>
            </li>

            <li class="menu-item @if(request()->routeIs("adminVet.seasons.index")) active @endif">
                <a href="{{route("adminVet.seasons.index")}}" class="menu-link">
                    <i class="menu-icon tf-icons mdi mdi-file-sign"></i>
                    <div>Seasons</div>
                </a>
            </li>

            <li class="menu-item @if(request()->routeIs("adminVet.products.index")) active @endif">
                <a href="{{route("adminVet.products.index")}}" class="menu-link">
                    <i class="menu-icon tf-icons mdi mdi-file-sign"></i>
                    <div>Products </div>
                </a>
            </li>

            <li class="menu-item @if(request()->routeIs("adminVet.orders.index")) active @endif">
                <a href="{{route("adminVet.orders.index")}}" class="menu-link">
                    <i class="menu-icon tf-icons mdi mdi-file-sign"></i>
                    <div>Orders</div>
                </a>
            </li>
        @endif
    </ul>

</aside>
