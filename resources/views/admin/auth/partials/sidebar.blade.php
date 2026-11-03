@include('admin.auth.partials.header')

<div class="d-flex admin-layout">
    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column p-4 shadow-sm">
        <div class="d-flex align-items-center mb-3 px-2">
            <div class="sidebar-icon-wrapper me-3">
                <i class="bi bi-person-badge-fill fs-4 text-white"></i>
            </div>
            <h4 class="text-white mb-0 fs-5 fw-bold tracking-wide">Admin Panel</h4>
        </div>

        <hr class="sidebar-divider my-3">

        <ul class="nav nav-pills flex-column mb-auto gap-1">
            <li class="nav-item">
                <a href="{{ route('admin.dashboard', ['admin_id' => session('admin_id')]) }}"
                    class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2 me-2 fs-5"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="{{ route('admin.tenantmanagemnt', ['admin_id' => session('admin_id')]) }}"
                    class="nav-link {{ request()->routeIs('admin.tenantmanagemnt') ? 'active' : '' }}">
                    <i class="bi bi-people me-2 fs-5"></i> Tenant Management
                </a>
            </li>
            <li>
                <a href="{{ route('admin.landlordmanagement', ['admin_id' => session('admin_id')]) }}"
                    class="nav-link {{ request()->routeIs('admin.landlordmanagement') ? 'active' : '' }}">
                    <i class="bi bi-building me-2 fs-5"></i> Landlord Management
                </a>
            </li>
        </ul>

        <hr class="sidebar-divider my-3">

        <!-- Logout Form -->
        <div class="pt-2">
            <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit"
                    class="btn logout-btn w-100 py-2.5 rounded-pill fw-semibold d-flex align-items-center justify-content-center">
                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                </button>
            </form>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="content-wrapper flex-grow-1">
        <admin-dashboard></admin-dashboard> <!-- Vue component mounted here -->
    </div>
</div>
<style>
    body {
        min-height: 100vh;
        margin: 0;
        font-family: 'Inter', 'Arial', sans-serif;
        background: #f4f7fa;
        color: #2A2D34;
    }

    /* Sidebar Styling */
    .sidebar {
        width: 280px;
        background: linear-gradient(135deg, #003C87 0%, #002B66 100%);
        color: #fff;
        flex-shrink: 0;
        height: 100vh;
        position: sticky;
        top: 0;
        box-shadow: 4px 0 20px rgba(0, 60, 135, 0.08);
    }

    .sidebar-icon-wrapper {
        background: rgba(255, 255, 255, 0.12);
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .sidebar-divider {
        border-color: rgba(255, 255, 255, 0.15) !important;
    }

    /* Navigation Links */
    .sidebar .nav-link {
        color: rgba(255, 255, 255, 0.75);
        padding: 12px 18px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        font-weight: 500;
        transition: all 0.25s ease-in-out;
    }

    .sidebar .nav-link:hover {
        color: #fff;
        background-color: rgba(255, 255, 255, 0.08);
        transform: translateX(4px);
    }

    .sidebar .nav-link.active {
        color: #fff;
        background-color: #FC7D07;
        box-shadow: 0 4px 12px rgba(252, 125, 7, 0.3);
        font-weight: 600;
    }

    /* Logout Button */
    .logout-btn {
        background-color: rgba(255, 255, 255, 0.08);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.15);
        transition: all 0.2s ease-in-out;
    }

    .logout-btn:hover {
        background-color: #dc3545;
        border-color: #dc3545;
        color: #fff;
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.25);
    }

    /* Main Content Workspace */
    .content-wrapper {
        padding: 35px;
        background-color: #f8fafc;
        min-height: 100vh;
        overflow-x: hidden;
    }
</style>