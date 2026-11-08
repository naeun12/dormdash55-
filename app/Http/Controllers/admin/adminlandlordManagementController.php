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
    // Ipakita ang landlord management page ug ipasa ang admin ID.
    public function landlordManagementIndex($admin_id)
    {
        return view('admin.auth.landlordmanagement', [
            'title' => 'Admin - Landlord Management',
            'admin_id' => $admin_id,
        ]);
    }

    // Kuhaon ang listahan sa landlords nga naka-sort gikan sa pinakabag-o.
    public function getLandlords()
    {
        // 10 ka landlords ang ipakita kada page.
        $landlords = landlordModel::orderBy('created_at', 'desc')->paginate(10);

        // Ibalik ang landlord records isip JSON response.
        return response()->json($landlords);
    }

    // Kuhaon ang profile information sa specific landlord.
    public function viewLandlordProfile($lanlord_id)
    {
        $landlord = landlordModel::where('landlordID', $lanlord_id)->first();

        // Ibalik ang landlord profile isip JSON response.
        return response()->json($landlord);
    }

    // I-deactivate ang account sa selected landlord.
    public function deactivateLandlordAccount($landlord_id)
    {
        // Pangitaon ang landlord gamit ang iyang ID.
        $landlord = landlordModel::where('landlordID', $landlord_id)->first();

        // Kung walay makita nga landlord, ibalik ang 404 error.
        if (!$landlord) {
            return response()->json([
                'status' => 'error',
                'message' => 'Landlord account not found.'
            ], 404);
        }

        // I-set ang account status ngadto sa deactivated.
        $landlord->is_deactivated = 1;
        $landlord->save();

        // Kuhaon ang complete name sa landlord para sa notifications.
        $landlordName = $landlord->firstname . ' ' . $landlord->lastname;

        // Mensahe nga ipadala sa landlord mahitungod sa deactivation.
        $messageBody = "Your Landlord account has been deactivated. Please contact DormDash support if you believe this is an error.";

        // Maghimo og in-app notification para mahibaloan sa landlord ang deactivation.
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

            // I-broadcast ang notification aron makita kini sa recipient.
            broadcast(new \App\Events\NewNotificationEvent($notifications));

        } catch (\Throwable $e) {
            // I-save sa Laravel log kung napakyas ang in-app notification.
            Log::error('Landlord deactivation notification failed: ' . $e->getMessage());
        }

        // Ipadala ang SMS notification kung adunay registered phone number.
        try {
            if (!empty($landlord->phoneNumber)) {
                app(IprogSMSService::class)->sendSMS(
                    $landlord->phoneNumber,
                    "DormDash: " . $messageBody
                );
            }
        } catch (\Throwable $e) {
            // I-save sa Laravel log kung napakyas ang SMS sending.
            Log::error('Landlord deactivation SMS failed: ' . $e->getMessage());
        }

        // Ipadala ang email notification kung adunay registered email address.
        try {
            if (!empty($landlord->email)) {
                Mail::to($landlord->email)->send(
                    new AdminTenantEmail($landlordName, $messageBody)
                );
            }
        } catch (\Throwable $e) {
            // I-save sa Laravel log kung napakyas ang email sending.
            Log::error('Landlord deactivation email failed: ' . $e->getMessage());
        }

        // Ibalik ang success response ug updated landlord information.
        return response()->json([
            'status' => 'success',
            'message' => 'Landlord account successfully deactivated',
            'landlord' => $landlord
        ]);
    }

    // I-reactivate ang previously deactivated landlord account.
    public function reactivelandlordAccount($landlord_id)
    {
        // Pangitaon ang landlord gamit ang iyang ID.
        $landlord = landlordModel::where('landlordID', $landlord_id)->first();

        // Kung walay makita nga landlord, ibalik ang 404 error.
        if (!$landlord) {
            return response()->json([
                'status' => 'error',
                'message' => 'Landlord account not found.'
            ], 404);
        }

        // I-set ang account status balik sa active.
        $landlord->is_deactivated = 0;
        $landlord->save();

        // Kuhaon ang complete name sa landlord.
        $landlordName = $landlord->firstname . ' ' . $landlord->lastname;

        // Mensahe nga ipadala human ma-reactivate ang account.
        $messageBody = "Your landlord account has been reactivated successfully. You can now log in to DormDash.";

        // Maghimo og in-app notification para mahibaloan sa landlord ang reactivation.
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

            // I-broadcast ang notification ngadto sa recipient.
            broadcast(new \App\Events\NewNotificationEvent($notifications));

        } catch (\Throwable $e) {
            // I-save sa Laravel log kung napakyas ang in-app notification.
            Log::error('Landlord reactivation notification failed: ' . $e->getMessage());
        }

        // Ipadala ang SMS notification kung adunay registered phone number.
        try {
            if (!empty($landlord->phoneNumber)) {
                app(IprogSMSService::class)->sendSMS(
                    $landlord->phoneNumber,
                    "DormDash: " . $messageBody
                );
            }
        } catch (\Throwable $e) {
            // I-save sa Laravel log kung napakyas ang SMS sending.
            Log::error('Landlord reactivation SMS failed: ' . $e->getMessage());
        }

        // Ipadala ang email notification kung adunay registered email address.
        try {
            if (!empty($landlord->email)) {
                Mail::to($landlord->email)->send(
                    new AdminTenantEmail($landlordName, $messageBody)
                );
            }
        } catch (\Throwable $e) {
            // I-save sa Laravel log kung napakyas ang email sending.
            Log::error('Landlord reactivation email failed: ' . $e->getMessage());
        }

        // Ibalik ang success response ug updated landlord information.
        return response()->json([
            'status' => 'success',
            'message' => 'Landlord account successfully reactivated',
            'landlord' => $landlord
        ]);
    }

    // Magpadala og custom email gikan sa admin ngadto sa landlord.
    public function sendLandlordEmail(Request $request)
    {
        // I-validate ang recipient email, subject, ug message.
        $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string',
            'message' => 'required|string',
        ]);

        // Kuhaon ang validated email details gikan sa request.
        $emailData = $request->only('to', 'subject', 'message');

        // Pangitaon ang landlord base sa recipient email address.
        $landlord = landlordModel::where('email', $emailData['to'])->first();

        // Sulayan pagpadala ang email gamit ang AdminTenantEmail mail class.
        try {
            Mail::to($emailData['to'])->send(new AdminTenantEmail(
                $emailData['subject'],
                $emailData['message']
            ));
        } catch (\Throwable $e) {
            // I-save ang error details kung napakyas ang email sending.
            Log::error('Admin landlord email failed: ' . $e->getMessage());

            // Ibalik ang error response kung dili successful ang email.
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send email.'
            ], 500);
        }

        // Kung registered landlord ang recipient, magpadala usab og notifications.
        if ($landlord) {

            // Maghimo og in-app notification gamit ang email subject ug message.
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

                // I-broadcast ang notification ngadto sa landlord.
                broadcast(new \App\Events\NewNotificationEvent($notifications));

            } catch (\Throwable $e) {
                // I-save sa Laravel log kung napakyas ang in-app notification.
                Log::error('Admin landlord in-app notification failed: ' . $e->getMessage());
            }

            // Ipadala ang SMS nga adunay email subject ug message.
            try {
                if (!empty($landlord->phoneNumber)) {
                    app(IprogSMSService::class)->sendSMS(
                        $landlord->phoneNumber,
                        "DormDash: {$emailData['subject']} - {$emailData['message']}"
                    );
                }
            } catch (\Throwable $e) {
                // I-save sa Laravel log kung napakyas ang SMS sending.
                Log::error('Admin landlord SMS failed: ' . $e->getMessage());
            }
        }

        // Ibalik ang success response human mapadala ang email.
        return response()->json([
            'status' => 'success',
            'message' => 'Email sent successfully'
        ]);
    }

    // Mag-generate og PDF report nga naglangkob sa landlord records.
    public function downloadLandlordReport()
    {
        // Kuhaon ang tanang landlords ug i-sort gikan sa pinakadaan.
        $landlords = landlordModel::orderBy('created_at', 'asc')->get();

        // Kuhaon ang file path sa logo nga gamiton sa PDF report.
        $logoPath = public_path('images/Logo/logo.png');

        // I-load ang landlord report view ug ipasa ang required data.
        $pdf = Pdf::loadView(
            'admin.auth.reports.landlord-report',
            compact('landlords', 'logoPath')
        );

        // I-display ang generated PDF sa browser.
        return $pdf->stream("landlord_report.pdf");
    }

    // Mangita og landlords base sa ilang first name o last name.
    public function searchLandlords(Request $request)
    {
        // Kuhaon ang search keyword gikan sa ?q= URL parameter.
        $searchTerm = $request->query('q');

        // Pangitaon ang matching landlords ug ipakita ang 10 records kada page.
        $landlords = landlordModel::query()
            ->where('firstname', 'like', "%{$searchTerm}%")
            ->orWhere('lastname', 'like', "%{$searchTerm}%")
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Ibalik ang search results isip JSON response.
        return response()->json([
            'status' => 'success',
            'data' => $landlords
        ]);
    }
}
