@include('tenant.auth.partials.header')

@auth('tenant')
<nav class="navbar navbar-expand-lg navbar-light fixed-top shadow-sm main-nav">
    <div class="container">
        <div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0">
            <button class="navbar-toggler border-0 shadow-none ps-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#tenantNav">
                <i class="bi bi-list fs-2 text-primary"></i>
            </button>

            <a class="navbar-brand d-flex align-items-center ms-2 ms-lg-0" href="#">
                <img src="{{ asset('images/Logo/logo.png') }}" alt="Logo" class="nav-logo">
                <span class="logo-text ms-2 fw-bold d-none d-sm-inline">Dorm<span class="text-orange">Dash</span></span>
            </a>
        </div>

        <div class="collapse navbar-collapse justify-content-center" id="navbarMain">
            <ul class="navbar-nav nav-pills gap-1">
                <li class="nav-item">
                    <a class="nav-link px-3 {{ request()->routeIs('homepage') ? 'active' : '' }}" 
                       href="{{ route('homepage', ['tenant_id' => session('tenant_id')]) }}">
                        <i class="bi bi-house-door me-1"></i> Home
                    </a>
                </li>
                {{-- <li class="nav-item">
                    <a class="nav-link px-3 {{ request()->routeIs('dorm.map') ? 'active' : '' }}" 
                       href="{{ route('dorm.map', ['tenant_id' => session('tenant_id')]) }}">
                        <i class="bi bi-geo-alt me-1"></i> Map
                    </a>
                </li> --}}
                <li class="nav-item">
                    <a class="nav-link px-3 {{ request()->routeIs('dormitories') ? 'active' : '' }}" 
                       href="{{ route('dormitories', ['tenant_id' => session('tenant_id')]) }}">
                        <i class="bi bi-building me-1"></i> Dorms
                    </a>
                </li>
                {{-- <li class="nav-item">
                    <a class="nav-link px-3 {{ request()->routeIs('tenant.message') ? 'active' : '' }}" 
                       href="{{ route('tenant.message', ['tenant_id' => session('tenant_id')]) }}">
                        <i class="bi bi-chat-left-text me-1"></i> Messages
                    </a>
                </li> --}}
            </ul>
        </div>

        <div class="d-flex align-items-center gap-3">
            <div class="dropdown">
                <button class="btn btn-icon position-relative border-0" type="button" id="notificationDropdown" data-bs-toggle="dropdown">
                    <i class="bi bi-bell fs-5"></i>
                    @if ($unread_count > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-orange">
                            {{ $unread_count }}
                        </span>
                    @endif
                </button>

                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 mt-3 py-0 notification-menu">
                    <li class="p-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">Notifications</h6>
                        <span class="badge bg-primary-light text-primary">{{ $unread_count }} Unread</span>
                    </li>
                    <div class="notif-scroll">
                        @forelse($notifications as $notif)
                            <li>
                                <a href="{{ route('view.notifications.tenant', ['tenant_id' => session('tenant_id')]) }}" 
                                   class="dropdown-item p-3 border-bottom d-flex align-items-start gap-3">
                                    <div class="bg-primary-light p-2 rounded-circle">
                                        <i class="bi bi-bell text-primary"></i>
                                    </div>
                                    <div class="text-wrap">
                                        <div class="fw-bold text-dark small">{{ $notif->title }}</div>
                                        <div class="text-muted extra-small">{{ Str::limit($notif->message, 50) }}</div>
                                    </div>
                                </a>
                            </li>
                        @empty
                            <li class="p-5 text-center">
                                <i class="bi bi-bell-slash text-muted fs-2 d-block mb-2"></i>
                                <span class="text-muted small">No new alerts</span>
                            </li>
                        @endforelse
                    </div>
                    <li class="p-2 text-center">
                        <a href="{{ route('view.notifications.tenant', ['tenant_id' => session('tenant_id')]) }}" 
                           class="btn btn-link btn-sm text-primary fw-bold text-decoration-none w-100">See All Notifications</a>
                    </li>
                </ul>
            </div>

            <div class="dropdown">
                <a class="d-flex align-items-center gap-2 text-decoration-none dropdown-toggle user-profile" 
                   href="#" id="userDropdown" data-bs-toggle="dropdown">
                    <div class="profile-img-wrapper shadow-sm">
                        <img src="{{ asset('storage/' . session('profilePicUrl')) }}" alt="User Avatar" class="rounded-circle object-fit-cover">
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 mt-3 overflow-hidden">
                    <li class="px-3 py-2 text-muted small fw-bold text-uppercase">Account Settings</li>
                    <li><a href="{{ route('tenant.update', ['tenant_id' => session('tenant_id')]) }}" class="dropdown-item py-2"><i class="bi bi-person me-2"></i> My Profile</a></li>
                    <li><hr class="dropdown-divider opacity-50"></li>
                    <li>
                        <form action="{{ route('tenant.logout') }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit" class="dropdown-item py-2 text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="offcanvas offcanvas-start border-0 shadow" tabindex="-1" id="tenantNav">
    <div class="offcanvas-header bg-primary text-white">
        <div class="d-flex align-items-center">
            <img src="{{ asset('images/Logo/logo.png') }}" alt="Logo" width="45" class="bg-white rounded p-1">
            <span class="fs-5 fw-bold ms-2 text-white">DormDash</span>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div class="list-group list-group-flush pt-3">
            <a href="{{ route('homepage', ['tenant_id' => session('tenant_id')]) }}" 
               class="list-group-item list-group-item-action py-3 border-0 d-flex align-items-center {{ request()->routeIs('homepage') ? 'active-sidebar' : '' }}">
                <i class="bi bi-house-door fs-5 me-3"></i> Home
            </a>
            <a href="{{ route('dorm.map', ['tenant_id' => session('tenant_id')]) }}" 
               class="list-group-item list-group-item-action py-3 border-0 d-flex align-items-center {{ request()->routeIs('dorm.map') ? 'active-sidebar' : '' }}">
                <i class="bi bi-geo-alt fs-5 me-3"></i> Dorm Location
            </a>
            <a href="{{ route('dormitories', ['tenant_id' => session('tenant_id')]) }}" 
               class="list-group-item list-group-item-action py-3 border-0 d-flex align-items-center {{ request()->routeIs('dormitories') ? 'active-sidebar' : '' }}">
                <i class="bi bi-building fs-5 me-3"></i> Dormitories
            </a>
           
        </div>
    </div>
</div>

<div class="content-wrapper">
    @yield('content')
</div>

<style>


    /* Navbar Styling */
    .main-nav {
        background: rgba(255, 255, 255, 0.9) !important;
        backdrop-filter: blur(10px);
        height: 75px;
        transition: all 0.3s ease;
    }

    .nav-logo { height: 45px; width: auto; }
    .logo-text { font-size: 1.5rem; color: #003C87; letter-spacing: -1px; }
    .text-orange { color: #FC7D07; }

    /* Desktop Navigation Pills */
    .nav-pills .nav-link {
        color: #555;
        font-weight: 500;
        border-radius: 12px;
        transition: all 0.3s ease;
    }
    
    .nav-pills .nav-link:hover {
        background-color: rgba(0, 60, 135, 0.05);
        color: #003C87;
    }

    .nav-pills .nav-link.active {
        background-color: #003C87 !important;
        color: white !important;
        box-shadow: 0 4px 10px rgba(0, 60, 135, 0.2);
    }

    /* Icons and Buttons */
    .btn-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #444;
        transition: 0.2s;
    }
    .btn-icon:hover { background: #eee; }
    .bg-orange { background-color: #FC7D07; }

    /* Notification Dropdown */
    .notification-menu { width: 320px; }
    .notif-scroll { max-height: 350px; overflow-y: auto; }
    .bg-primary-light { background-color: rgba(0, 60, 135, 0.1); }
    .extra-small { font-size: 0.75rem; line-height: 1.2; }

    /* Profile Wrapper */
    .profile-img-wrapper img {
        width: 42px;
        height: 42px;
        border: 2px solid white;
        transition: 0.3s;
    }
    .user-profile:hover .profile-img-wrapper img {
        border-color: #003C87;
        transform: scale(1.05);
    }

    /* Offcanvas Sidebar */
    .bg-primary { background-color: #003C87 !important; }
    .active-sidebar {
        background-color: rgba(0, 60, 135, 0.08) !important;
        color: #003C87 !important;
        font-weight: bold;
        border-left: 4px solid #003C87 !important;
    }

    /* Content Spacing */
    .content-wrapper { padding-top: 100px; padding-bottom: 50px; }

    @media (max-width: 991.98px) {
        .main-nav { height: 65px; }
        .content-wrapper { padding-top: 85px; }
    }
</style>
@endauth