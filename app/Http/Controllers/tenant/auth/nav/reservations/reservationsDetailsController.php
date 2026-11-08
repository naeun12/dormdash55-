<?php
namespace App\Http\Controllers\tenant\auth\nav\reservations;

use App\Http\Controllers\Controller;
use App\Models\tenant\tenantModel;
use App\Models\notificationModel;
use Illuminate\Http\Request;

class reservationsDetailsController extends Controller
{
    // Ipakita ang reservation details page sa tenant.
    public function viewReservationDetails($tenant_id)
    {
        // Kuhaon ang tenant ID nga naka-login sa session.
        $sessionTenant_id = session('tenant_id');
         $notifications = notificationModel::where('receiverID', $sessionTenant_id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // I-count ang tanang unread notifications sa landlord.
        $unreadCount = notificationModel::where('receiverID', $tenant_id)
            ->where('isRead', false)
            ->count();

        // Ibalik sa login page kung walay naka-login nga tenant.
        if (!$sessionTenant_id) {
            return redirect()->route('tenant-login')->with('error', 'Please log in as a landlord.');
        }

        // Siguraduhon nga ang tenant ID sa URL parehas sa naka-login nga tenant.
        if ($tenant_id !== $sessionTenant_id) {
            return redirect()->route('tenant-login')->with('error', 'Unauthorized access.');
        }

        // Pangitaon ang tenant record sa database gamit ang tenant ID.
        $tenant = tenantModel::find($tenant_id);

        // Ibalik sa login page kung walay nakit-an nga tenant.
        if (!$tenant) {
            return redirect()->route('tenant-login')->with('error', 'Landlord not found.');
        }

        // Title nga gamiton sa reservation details page.
        $title = 'Tenant room Details - DormDash';

        // I-load ang reservation view uban sa tenant ug notification information.
        return view('tenant.auth.nav.reservations.reservation', [
            'title' => 'Reservations',
            'tenant_id' => $tenant_id,
            'cssPath' => asset('css/tenantpage/auth/roomdetails.css'),
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }
}

