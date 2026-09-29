@include('landlord.auth.partials.header')

<div class="col-md-2 sidebar-modern" id="sidebarMenu">
    <div class="brand-wrapper p-4">
        <a class="navbar-brand d-flex align-items-center text-white text-decoration-none">
            <div class="logo-container me-2">
                <img src="{{ asset('images/Logo/logo.png') }}" alt="Logo" width="40">
            </div>
            <span class="logo-text fw-bold fs-4">DormDash</span>
        </a>
    </div>

    <div class="nav-scrollable">
        <ul class="nav flex-column px-3">
            
            <li class="nav-label mt-2">MAIN</li>
            <li class="nav-item mb-1">
                <a href="{{ route('landlord.dashboard', ['landlordId' => session('landlord_id')]) }}"
                    class="nav-link-modern {{ request()->routeIs('landlord.dashboard') ? 'active-item' : '' }}">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="nav-label mt-4">PROPERTY MANAGEMENT</li>
            <li class="nav-item mb-1">
                <a href="{{ route('landlord.dormanagement', ['landlordId' => session('landlord_id')]) }}"
                    class="nav-link-modern {{ request()->routeIs('landlord.dormanagement') ? 'active-item' : '' }}">
                    <i class="bi bi-house-add"></i>
                    <span>Dorm Management</span>
                </a>
            </li>

            <li class="nav-item mb-1">
                <a href="{{ route('landlordRoomManagement', ['landlordId' => session('landlord_id')]) }}"
                    class="nav-link-modern {{ request()->routeIs('landlordRoomManagement') ? 'active-item' : '' }}">
                    <i class="bi bi-door-open"></i>
                    <span>Manage Rooms</span>
                </a>
            </li>

            <li class="nav-label mt-4">BOOKINGS & APPROVALS</li>
            <li class="nav-item mb-1">
                <a class="nav-link-modern dropdown-toggle-custom {{ request()->routeIs('booking.index') || request()->routeIs('reservation.index') ? 'parent-active' : '' }}"
                    data-bs-toggle="collapse" href="#bookingMenu" role="button"
                    aria-expanded="{{ request()->routeIs('booking.index') || request()->routeIs('reservation.index') ? 'true' : 'false' }}">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-calendar2-check"></i>
                        <span>Approvals</span>
                    </div>
                    <i class="bi bi-chevron-down ms-auto small"></i>
                </a>

                <div class="collapse {{ request()->routeIs('booking.index') || request()->routeIs('reservation.index') ? 'show' : '' }}"
                    id="bookingMenu">
                    <ul class="nav flex-column ms-3 mt-1 submenu-list">
                        <li class="nav-item">
                            <a href="{{ route('booking.index', ['landlord_id' => session('landlord_id')]) }}"
                                class="sub-link {{ request()->routeIs('booking.index') ? 'sub-active' : '' }}">
                                Booking Approval
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('reservation.index', ['landlord_id' => session('landlord_id')]) }}"
                                class="sub-link {{ request()->routeIs('reservation.index') ? 'sub-active' : '' }}">
                                Reservation Approval
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="nav-label mt-4">USER RELATIONS</li>
            <li class="nav-item mb-1">
                <a href="{{ route('all.tenants.index', ['landlord_id' => session('landlord_id')]) }}"
                    class="nav-link-modern {{ request()->routeIs('all.tenants.index') ? 'active-item' : '' }}">
                    <i class="bi bi-person-lines-fill"></i>
                    <span>All Tenants</span>
                </a>
            </li>

          
            <li class="nav-item mb-1">
                <a href="{{ route('notifications.landlord', ['landlord_id' => session('landlord_id')]) }}"
                    class="nav-link-modern {{ request()->routeIs('notifications.landlord') ? 'active-item' : '' }}">
                    <i class="bi bi-bell-fill"></i>
                    <span>Notifications</span>
                </a>
            </li>

        </ul>
    </div>
</div>

    <link rel="stylesheet" href="{{ asset('css/landlord/sidebar.css') }}">
