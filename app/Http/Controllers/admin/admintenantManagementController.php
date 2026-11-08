<?php
namespace App\Http\Controllers\admin;
use App\Http\Controllers\Controller;
use App\Models\tenant\tenantModel;
use App\Models\notificationModel;
use App\Mail\AdminTenantEmail;
use App\Services\IprogSMSService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
class admintenantManagementController extends Controller
{
    public function admintenantManagemnentIndex($admin_id)
    {
        return view('admin.auth.tenantmanagement', [
            'title' => 'Admin - Tenant Management',
            'admin_id' => $admin_id,
        ]);
    }


    public function getTenants()
    {
        $tenants = tenantModel::orderBy('created_at', 'desc')->paginate(10);


        return response()->json($tenants);
    }


    public function viewTenantProfile($tenant_id)
    {
        $tenant = tenantModel::where('tenantID', $tenant_id)->first();


        if (!$tenant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tenant not found.'
            ], 404);
        }


        return response()->json($tenant);
    }


    // Shared notification function
    private function notifyTenant($tenant, $title, $message)
    {
        // In-app notification
        try {
            $notifications = notificationModel::create([
                'senderID' => session('admin_id') ?? session('adminID'),
                'senderType' => 'admin',
                'receiverID' => $tenant->tenantID,
                'receiverType' => 'tenant',
                'title' => $title,
                'message' => $message,
                'isRead' => false,
                'readAt' => null,
            ]);


            broadcast(new \App\Events\NewNotificationEvent($notifications));


        } catch (\Throwable $e) {
            Log::error('Tenant in-app notification failed: ' . $e->getMessage());
        }


        // SMS Notification
        try {
            $phoneNumber = $tenant->phoneNumber ?? $tenant->contactNumber ?? null;


            if (!empty($phoneNumber)) {
                app(IprogSMSService::class)->sendSMS(
                    $phoneNumber,
                    'DormDash: ' . $message
                );
            }
        } catch (\Throwable $e) {
            Log::error('Tenant SMS notification failed: ' . $e->getMessage());
        }


        // Email Notification
        try {
            if (!empty($tenant->email)) {
                $tenantName = $tenant->firstname . ' ' . $tenant->lastname;


                Mail::to($tenant->email)->send(
                    new AdminTenantEmail($tenantName, $message)
                );
            }
        } catch (\Throwable $e) {
            Log::error('Tenant email notification failed: ' . $e->getMessage());
        }
    }


    public function deactivatetenantAccount($tenant_id)
    {
        $tenant = tenantModel::where('tenantID', $tenant_id)->first();


        if (!$tenant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tenant account not found.'
            ], 404);
        }


        $tenant->is_deactivated = 1;
        $tenant->save();


        $messageBody = "Your tenant account has been deactivated. Please contact DormDash support if you believe this is an error.";


        $this->notifyTenant(
            $tenant,
            'Tenant Account Deactivated',
            $messageBody
        );


        return response()->json([
            'status' => 'success',
            'message' => 'Tenant account successfully deactivated',
            'tenant' => $tenant
        ]);
    }


    public function reactivetenantAccount($tenant_id)
    {
        $tenant = tenantModel::where('tenantID', $tenant_id)->first();


        if (!$tenant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tenant account not found.'
            ], 404);
        }


        $tenant->is_deactivated = 0;
        $tenant->save();


        $messageBody = "Your tenant account has been reactivated successfully. You can now log in to DormDash.";


        $this->notifyTenant(
            $tenant,
            'Tenant Account Reactivated',
            $messageBody
        );


        return response()->json([
            'status' => 'success',
            'message' => 'Tenant account successfully reactivated',
            'tenant' => $tenant
        ]);
    }


    public function sendTenantEmail(Request $request)
    {
        $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string',
            'message' => 'required|string',
        ]);


        $emailData = $request->only('to', 'subject', 'message');


        try {
            // Send Email
            Mail::to($emailData['to'])->send(
                new AdminTenantEmail(
                    $emailData['subject'],
                    $emailData['message']
                )
            );


        } catch (\Throwable $e) {
            Log::error('Admin tenant email failed: ' . $e->getMessage());


            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send email.'
            ], 500);
        }


        // Find tenant using recipient email
        $tenant = tenantModel::where('email', $emailData['to'])->first();


        if ($tenant) {
            // In-app notification and SMS
            // Email is not sent again because it was already sent above.
            try {
                $notifications = notificationModel::create([
                    'senderID' => session('admin_id') ?? session('adminID'),
                    'senderType' => 'admin',
                    'receiverID' => $tenant->tenantID,
                    'receiverType' => 'tenant',
                    'title' => $emailData['subject'],
                    'message' => $emailData['message'],
                    'isRead' => false,
                    'readAt' => null,
                ]);


                broadcast(new \App\Events\NewNotificationEvent($notifications));


            } catch (\Throwable $e) {
                Log::error('Custom tenant in-app notification failed: ' . $e->getMessage());
            }


            try {
                $phoneNumber = $tenant->phoneNumber ?? $tenant->contactNumber ?? null;


                if (!empty($phoneNumber)) {
                    app(IprogSMSService::class)->sendSMS(
                        $phoneNumber,
                        'DormDash: ' . $emailData['subject'] . ' - ' . $emailData['message']
                    );
                }


            } catch (\Throwable $e) {
                Log::error('Custom tenant SMS failed: ' . $e->getMessage());
            }
        }


        return response()->json([
            'status' => 'success',
            'message' => 'Email sent successfully'
        ]);
    }


    public function downloadTenantReport()
    {
        // Query all tenants
        $tenants = tenantModel::orderBy('created_at', 'asc')->get();


        $logoPath = public_path('images/Logo/logo.png');


        // Generate PDF
        $pdf = Pdf::loadView(
            'admin.auth.reports.tenant-report',
            compact('tenants', 'logoPath')
        );


        return $pdf->stream('tenant_report.pdf');
    }


    public function searchTenants(Request $request)
    {
        $searchTerm = $request->query('q');


        $tenants = tenantModel::query()
            ->where('firstname', 'like', "%{$searchTerm}%")
            ->orWhere('lastname', 'like', "%{$searchTerm}%")
            ->orderBy('firstname', 'asc')
            ->paginate(10);


        return response()->json([
            'status' => 'success',
            'data' => $tenants
        ]);
    }
}



