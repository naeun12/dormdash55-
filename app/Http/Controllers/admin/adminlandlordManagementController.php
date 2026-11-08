<?php
namespace App\Http\Controllers\admin;
use App\Http\Controllers\Controller;
use App\Models\landlord\landlordModel;
use App\Services\IprogSMSService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\AdminTenantEmail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use App\Models\notificationModel;
class adminlandlordManagementController extends Controller
{
    public function landlordManagementIndex($admin_id)
    {
        return view('admin.auth.landlordmanagement', [
            'title' => 'Admin - Landlord Management',
            'admin_id' => $admin_id,
        ]);
    }
     public function getLandlords()
    {
        $landlords = landlordModel::orderBy('created_at','desc')->paginate(10); // 10 tenants per page
        return response()->json($landlords);
    }
    public function viewLandlordProfile($lanlord_id)
    {
        $landlord = landlordModel::where('landlordID',$lanlord_id)->first();
        return response()->json($landlord);


    }
   
   public function deactivateLandlordAccount($landlord_id)
{
    $landlord = landlordModel::where('landlordID', $landlord_id)->first();


    if (!$landlord) {
        return response()->json([
            'status' => 'error',
            'message' => 'Landlord account not found.'
        ], 404);
    }


    // Deactivate landlord account
    $landlord->is_deactivated = 1;
    $landlord->save();


    $landlordName = $landlord->firstname . ' ' . $landlord->lastname;


    $messageBody = "Your Landlord account has been deactivated. Please contact DormDash support if you believe this is an error.";


    // In-app notification
    try {
        $notifications = notificationModel::create([
            'senderID' => $landlord->landlordID,
            'senderType' => 'landlord',
            'receiverID' => $landlord->landlordID,
            'receiverType' => 'landlord',
            'title' => 'Landlord Account Deactivated',
            'message' => $messageBody,
            'isRead' => false,
            'readAt' => null,
        ]);


        broadcast(new \App\Events\NewNotificationEvent($notifications));


    } catch (\Throwable $e) {
        Log::error('Landlord deactivation notification failed: ' . $e->getMessage());
    }


    // SMS Notification
    try {
        if (!empty($landlord->phoneNumber)) {
            app(IprogSMSService::class)->sendSMS(
                $landlord->phoneNumber,
                "DormDash: " . $messageBody
            );
        }
    } catch (\Throwable $e) {
        Log::error('Landlord deactivation SMS failed: ' . $e->getMessage());
    }


    // Email Notification
    try {
        if (!empty($landlord->email)) {
            Mail::to($landlord->email)->send(
                new AdminTenantEmail($landlordName, $messageBody)
            );
        }
    } catch (\Throwable $e) {
        Log::error('Landlord deactivation email failed: ' . $e->getMessage());
    }


    return response()->json([
        'status' => 'success',
        'message' => 'Landlord account successfully deactivated',
        'landlord' => $landlord
    ]);
}
    public function reactivelandlordAccount($landlord_id)
{
    $landlord = landlordModel::where('landlordID', $landlord_id)->first();


    if (!$landlord) {
        return response()->json([
            'status' => 'error',
            'message' => 'Landlord account not found.'
        ], 404);
    }


    // Reactivate landlord account
    $landlord->is_deactivated = 0;
    $landlord->save();


    $landlordName = $landlord->firstname . ' ' . $landlord->lastname;


    $messageBody = "Your landlord account has been reactivated successfully. You can now log in to DormDash.";


    // In-app notification
    try {
        $notifications = notificationModel::create([
            'senderID' => $landlord->landlordID,
            'senderType' => 'landlord',
            'receiverID' => $landlord->landlordID,
            'receiverType' => 'landlord',
            'title' => 'Landlord Account Reactivated',
            'message' => $messageBody,
            'isRead' => false,
            'readAt' => null,
        ]);


        broadcast(new \App\Events\NewNotificationEvent($notifications));


    } catch (\Throwable $e) {
        Log::error('Landlord reactivation notification failed: ' . $e->getMessage());
    }


    // SMS Notification
    try {
        if (!empty($landlord->phoneNumber)) {
            app(IprogSMSService::class)->sendSMS(
                $landlord->phoneNumber,
                "DormDash: " . $messageBody
            );
        }
    } catch (\Throwable $e) {
        Log::error('Landlord reactivation SMS failed: ' . $e->getMessage());
    }


    // Email Notification
    try {
        if (!empty($landlord->email)) {
            Mail::to($landlord->email)->send(
                new AdminTenantEmail($landlordName, $messageBody)
            );
        }
    } catch (\Throwable $e) {
        Log::error('Landlord reactivation email failed: ' . $e->getMessage());
    }


    return response()->json([
        'status' => 'success',
        'message' => 'Landlord account successfully reactivated',
        'landlord' => $landlord
    ]);
}
public function sendLandlordEmail(Request $request)
{
    $request->validate([
        'to' => 'required|email',
        'subject' => 'required|string',
        'message' => 'required|string',
    ]);


    $emailData = $request->only('to', 'subject', 'message');


    // Find landlord using recipient email
    $landlord = landlordModel::where('email', $emailData['to'])->first();


    // Send Email
    try {
        Mail::to($emailData['to'])->send(new AdminTenantEmail(
            $emailData['subject'],
            $emailData['message']
        ));
    } catch (\Throwable $e) {
        Log::error('Admin landlord email failed: ' . $e->getMessage());


        return response()->json([
            'status' => 'error',
            'message' => 'Failed to send email.'
        ], 500);
    }


    if ($landlord) {


        // In-app notification
        try {
            $notifications = notificationModel::create([
                'senderID' => $landlord->landlordID,
                'senderType' => 'landlord',
                'receiverID' => $landlord->landlordID,
                'receiverType' => 'landlord',
                'title' => $emailData['subject'],
                'message' => $emailData['message'],
                'isRead' => false,
                'readAt' => null,
            ]);


            broadcast(new \App\Events\NewNotificationEvent($notifications));


        } catch (\Throwable $e) {
            Log::error('Admin landlord in-app notification failed: ' . $e->getMessage());
        }


        // SMS Notification
        try {
            if (!empty($landlord->phoneNumber)) {
                app(IprogSMSService::class)->sendSMS(
                    $landlord->phoneNumber,
                    "DormDash: {$emailData['subject']} - {$emailData['message']}"
                );
            }
        } catch (\Throwable $e) {
            Log::error('Admin landlord SMS failed: ' . $e->getMessage());
        }
    }


    return response()->json([
        'status' => 'success',
        'message' => 'Email sent successfully'
    ]);
}
public function downloadLandlordReport()
{
    // Query subscriptions per month
    $landlords = landlordModel::orderBy('created_at','asc')->get();  
    $logoPath = public_path('images/Logo/logo.png');


    // Pass to view
    $pdf = Pdf::loadView('admin.auth.reports.landlord-report', compact('landlords','logoPath'));


    // Download as PDF
        return $pdf->stream("landlord_report.pdf");


}
public function searchLandlords(Request $request)
{
    $searchTerm = $request->query('q'); // get ?q= from URL


    $landlords = landlordModel::query()
        ->where('firstname', 'like', "%{$searchTerm}%")
        ->orWhere('lastname', 'like', "%{$searchTerm}%")
        ->orderBy('created_at', 'desc')
        ->paginate(10);


    return response()->json([
        'status' => 'success',
        'data' => $landlords
    ]);
}






}



