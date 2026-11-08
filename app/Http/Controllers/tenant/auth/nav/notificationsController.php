<?php

namespace App\Http\Controllers\tenant\auth\nav;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\notificationModel;
use App\Models\tenant\tenantModel;
use App\Models\landlord\landlordModel;

class notificationsController extends Controller
{
    // Ipakita ang notifications page sa tenant.
    public function notificationstenant($tenant_id)
    {
        // Kuhaon ang tenant ID nga naka-login sa session.
        $sessiontenantID = session('tenant_id');

        // Kuhaon ang pinakabag-ong lima ka notifications nga nadawat sa tenant.
        $notifications = notificationModel::where('receiverID', $sessiontenantID)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Ihap ang notifications nga wala pa mabasa sa tenant.
        $unreadCount = notificationModel::where('receiverID', $tenant_id)
            ->where('isRead', false)
            ->count();

        // Pangitaon ang tenant record gamit ang tenant ID.
        $tenant = tenantModel::find($tenant_id);

        // I-load ang notifications view uban sa tenant ug notification information.
        return view('tenant.auth.nav.notificationstenant', [
            "title" => "Tenant - Notifications",
            "cssPath" => "",
            'headerName' => 'Notifications',
            'tenant' => $tenant,
            'tenant_id' => $tenant_id,
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    // Kuhaon ang kompleto nga notification list sa tenant.
    public function gettenantotificationsList($tenant_id)
    {
        // I-load ang notification uban sa impormasyon sa sender ug receiver.
        $notifications = notificationModel::with('sender', 'receiver')

            // Kuhaon lang ang notifications nga nadawat sa tenant.
            ->where('receiverID', $tenant_id)

            // Ipakita una ang pinakabag-ong notifications.
            ->orderBy('created_at', 'desc')

            // I-filter ang notifications base sa receiver type nga tenant.
            ->where('receiverType', 'tenant') // String ra kay naka-morphMap na.
            ->get();

        // Ibalik ang notification list isip JSON response.
        return response()->json([
            'status' => 'success',
            'notifications' => $notifications
        ]);
    }

    // Markahan nga nabasa na ang usa ka notification.
    public function markAsRead($notificationId)
    {
        // Pangitaon ang notification gamit ang ID.
        $notification = notificationModel::findOrFail($notificationId);

        // I-update ang read status ug oras kung kanus-a kini gibasa.
        $notification->update([
            'isRead' => true,
            'readAt' => now(),
        ]);

        // Ibalik ang success response uban sa updated notification.
        return response()->json([
            'status' => 'success',
            'message' => 'Notification marked as read',
            'notification' => $notification
        ]);
    }

    // Markahan nga nabasa na ang tanang notifications sa tenant.
    public function markAllAsRead(Request $request)
    {
        // Kuhaon ang tenant ID gikan sa request.
        $tenantId = $request->tenant_id;

        // Pangitaon ug i-update ang tanang wala pa mabasa nga notifications sa tenant.
        notificationModel::where('receiverID', $tenantId)
            ->where('isRead', false) // Aron dili na apilon ang mga notifications nga nabasa na.
            ->update([
                'isRead' => true,
                'readAt' => now(),
            ]);

        // Ibalik ang success response human ma-update ang notifications.
        return response()->json([
            'status' => 'success',
            'message' => 'All notifications marked as read.'
        ]);
    }

    // I-delete ang tanang notifications sa tenant.
    public function clearAll(Request $request)
    {
        // Kuhaon ang tenant ID gikan sa request.
        $tenantId = $request->tenant_id;

        // I-delete ang tanang notification records nga nadawat sa tenant.
        notificationModel::where('receiverID', $tenantId)->delete();

        // Ibalik ang success response human ma-clear ang notifications.
        return response()->json([
            'status' => 'success',
            'message' => 'All notifications cleared successfully.'
        ]);
    }
}

