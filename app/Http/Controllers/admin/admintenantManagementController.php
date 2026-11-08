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
    // Ipakita ang tenant management page ug ipasa ang admin ID.
    public function admintenantManagemnentIndex($admin_id)
    {
        return view('admin.auth.tenantmanagement', [
            'title' => 'Admin - Tenant Management',
            'admin_id' => $admin_id,
        ]);
    }

    // Kuhaon ang listahan sa tenants nga naka-sort gikan sa pinakabag-o.
    public function getTenants()
    {
        // 10 ka tenants ang ipakita kada page.
        $tenants = tenantModel::orderBy('created_at', 'desc')->paginate(10);

        // Ibalik ang tenant records isip JSON response.
        return response()->json($tenants);
    }

    // Kuhaon ang profile information sa specific tenant.
    public function viewTenantProfile($tenant_id)
    {
        // Pangitaon ang tenant gamit ang iyang ID.
        $tenant = tenantModel::where('tenantID', $tenant_id)->first();

        // Kung walay makita nga tenant, ibalik ang 404 error.
        if (!$tenant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tenant not found.'
            ], 404);
        }

        // Ibalik ang tenant profile isip JSON response.
        return response()->json($tenant);
    }

    // Shared function para sa in-app notification, SMS, ug email sa tenant.
    private function notifyTenant($tenant, $title, $message)
    {
        // Maghimo og in-app notification para mahibaloan sa tenant ang update.
        try {
            $notifications = notificationModel::create([
                // Kuhaon ang admin ID gikan sa session.
                'senderID' => session('admin_id') ?? session('adminID'),
                'senderType' => 'admin',
                'receiverID' => $tenant->tenantID,
                'receiverType' => 'tenant',
                'title' => $title,
                'message' => $message,
                'isRead' => false,
                'readAt' => null,
            ]);

            // I-broadcast ang notification aron ma-receive kini sa tenant.
            broadcast(new \App\Events\NewNotificationEvent($notifications));

        } catch (\Throwable $e) {
            // I-save sa Laravel log kung napakyas ang in-app notification.
            Log::error('Tenant in-app notification failed: ' . $e->getMessage());
        }

        // Ipadala ang SMS notification gamit ang registered phone number sa tenant.
        try {
            // Susihon ang phoneNumber ug contactNumber kung asa adunay value.
            $phoneNumber = $tenant->phoneNumber ?? $tenant->contactNumber ?? null;

            if (!empty($phoneNumber)) {
                app(IprogSMSService::class)->sendSMS(
                    $phoneNumber,
                    'DormDash: ' . $message
                );
            }
        } catch (\Throwable $e) {
            // I-save sa Laravel log kung napakyas ang SMS notification.
            Log::error('Tenant SMS notification failed: ' . $e->getMessage());
        }

        // Ipadala ang email notification kung adunay registered email address.
        try {
            if (!empty($tenant->email)) {
                // Kuhaon ang complete name sa tenant.
                $tenantName = $tenant->firstname . ' ' . $tenant->lastname;

                Mail::to($tenant->email)->send(
                    new AdminTenantEmail($tenantName, $message)
                );
            }
        } catch (\Throwable $e) {
            // I-save sa Laravel log kung napakyas ang email notification.
            Log::error('Tenant email notification failed: ' . $e->getMessage());
        }
    }

    // I-deactivate ang account sa selected tenant.
    public function deactivatetenantAccount($tenant_id)
    {
        // Pangitaon ang tenant gamit ang iyang ID.
        $tenant = tenantModel::where('tenantID', $tenant_id)->first();

        // Kung walay makita nga tenant, ibalik ang 404 error.
        if (!$tenant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tenant account not found.'
            ], 404);
        }

        // I-set ang account status ngadto sa deactivated.
        $tenant->is_deactivated = 1;
        $tenant->save();

        // Mensahe nga ipadala sa tenant mahitungod sa deactivation.
        $messageBody = "Your tenant account has been deactivated. Please contact DormDash support if you believe this is an error.";

        // Tawagon ang shared notification function para sa in-app, SMS, ug email.
        $this->notifyTenant(
            $tenant,
            'Tenant Account Deactivated',
            $messageBody
        );

        // Ibalik ang success response ug updated tenant information.
        return response()->json([
            'status' => 'success',
            'message' => 'Tenant account successfully deactivated',
            'tenant' => $tenant
        ]);
    }

    // I-reactivate ang previously deactivated tenant account.
    public function reactivetenantAccount($tenant_id)
    {
        // Pangitaon ang tenant gamit ang iyang ID.
        $tenant = tenantModel::where('tenantID', $tenant_id)->first();

        // Kung walay makita nga tenant, ibalik ang 404 error.
        if (!$tenant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tenant account not found.'
            ], 404);
        }

        // I-set ang account status balik sa active.
        $tenant->is_deactivated = 0;
        $tenant->save();

        // Mensahe nga ipadala sa tenant human ma-reactivate ang account.
        $messageBody = "Your tenant account has been reactivated successfully. You can now log in to DormDash.";

        // Tawagon ang shared notification function para sa in-app, SMS, ug email.
        $this->notifyTenant(
            $tenant,
            'Tenant Account Reactivated',
            $messageBody
        );

        // Ibalik ang success response ug updated tenant information.
        return response()->json([
            'status' => 'success',
            'message' => 'Tenant account successfully reactivated',
            'tenant' => $tenant
        ]);
    }

    // Magpadala og custom email gikan sa admin ngadto sa tenant.
    public function sendTenantEmail(Request $request)
    {
        // I-validate ang recipient email, subject, ug message.
        $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string',
            'message' => 'required|string',
        ]);

        // Kuhaon ang email details gikan sa request.
        $emailData = $request->only('to', 'subject', 'message');

        // Sulayan pagpadala ang email ngadto sa recipient.
        try {
            // Ipadala ang email gamit ang AdminTenantEmail mail class.
            Mail::to($emailData['to'])->send(
                new AdminTenantEmail(
                    $emailData['subject'],
                    $emailData['message']
                )
            );

        } catch (\Throwable $e) {
            // I-save sa Laravel log ang error kung napakyas ang email sending.
            Log::error('Admin tenant email failed: ' . $e->getMessage());

            // Ibalik ang error response.
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send email.'
            ], 500);
        }

        // Pangitaon ang tenant gamit ang recipient email address.
        $tenant = tenantModel::where('email', $emailData['to'])->first();

        // Kung registered tenant ang recipient, magpadala usab og notifications.
        if ($tenant) {
            // Maghimo og in-app notification ug i-broadcast kini.
            // Dili na ipadala pag-usab ang email kay napadala na kini sa ibabaw.
            try {
                $notifications = notificationModel::create([
                    // Kuhaon ang admin ID gikan sa session.
                    'senderID' => session('admin_id') ?? session('adminID'),
                    'senderType' => 'admin',
                    'receiverID' => $tenant->tenantID,
                    'receiverType' => 'tenant',
                    'title' => $emailData['subject'],
                    'message' => $emailData['message'],
                    'isRead' => false,
                    'readAt' => null,
                ]);

                // I-broadcast ang notification ngadto sa tenant.
                broadcast(new \App\Events\NewNotificationEvent($notifications));

            } catch (\Throwable $e) {
                // I-save sa Laravel log kung napakyas ang in-app notification.
                Log::error('Custom tenant in-app notification failed: ' . $e->getMessage());
            }

            // Ipadala ang SMS nga adunay email subject ug message.
            try {
                // Susihon ang phoneNumber ug contactNumber kung asa adunay value.
                $phoneNumber = $tenant->phoneNumber ?? $tenant->contactNumber ?? null;

                if (!empty($phoneNumber)) {
                    app(IprogSMSService::class)->sendSMS(
                        $phoneNumber,
                        'DormDash: ' . $emailData['subject'] . ' - ' . $emailData['message']
                    );
                }

            } catch (\Throwable $e) {
                // I-save sa Laravel log kung napakyas ang SMS sending.
                Log::error('Custom tenant SMS failed: ' . $e->getMessage());
            }
        }

        // Ibalik ang success response human mapadala ang email.
        return response()->json([
            'status' => 'success',
            'message' => 'Email sent successfully'
        ]);
    }

    // Mag-generate og PDF report nga naglangkob sa tenant records.
    public function downloadTenantReport()
    {
        // Kuhaon ang tanang tenants ug i-sort gikan sa pinakadaan.
        $tenants = tenantModel::orderBy('created_at', 'asc')->get();

        // Kuhaon ang file path sa logo nga gamiton sa PDF report.
        $logoPath = public_path('images/Logo/logo.png');

        // I-load ang tenant report view ug ipasa ang required data.
        $pdf = Pdf::loadView(
            'admin.auth.reports.tenant-report',
            compact('tenants', 'logoPath')
        );

        // I-display ang generated PDF sa browser.
        return $pdf->stream('tenant_report.pdf');
    }

    // Mangita og tenants base sa ilang first name o last name.
    public function searchTenants(Request $request)
    {
        // Kuhaon ang search keyword gikan sa ?q= URL parameter.
        $searchTerm = $request->query('q');

        // Pangitaon ang matching tenants ug i-sort alphabetically.
        $tenants = tenantModel::query()
            ->where('firstname', 'like', "%{$searchTerm}%")
            ->orWhere('lastname', 'like', "%{$searchTerm}%")
            ->orderBy('firstname', 'asc')
            ->paginate(10);

        // Ibalik ang search results isip JSON response.
        return response()->json([
            'status' => 'success',
            'data' => $tenants
        ]);
    }
}
