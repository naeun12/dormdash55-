<?php

namespace App\Http\Controllers\tenant\auth\nav\myroom;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\landlord\roomModel;
use App\Models\tenant\tenantModel;
use App\Models\tenant\approvetenantsModel;
use App\Models\tenant\approvepaymentModel;
use App\Models\tenant\bookingpaymentModel;
use App\Models\tenant\reservationpaymentModel;
use Illuminate\Support\Facades\Validator;
use App\Models\notificationModel;
use App\Models\reviewandratingModel;
use App\Models\conversationModel;
use App\Models\messageModel;
use App\Events\MessageSent;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\IprogSMSService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class myroomsController extends Controller
{
    // Ipakita ang My Rooms page sa naka-login nga tenant.
    public function myroomsIndex($tenant_id)
    {
        // Kuhaa ang tenant ID nga naka-store sa current session.
        $sessionTenant_id = session('tenant_id');

        // Susiha kung adunay naka-login nga tenant.
        // Kung walay session, i-redirect siya ngadto sa login page.
        if (!$sessionTenant_id) {
            return redirect()->route('tenant-login')->with('error', 'Please log in as a landlord.');
        }

        // Siguraduhon nga ang tenant ID sa URL parehas sa naka-login nga tenant.
        // Kung dili parehas, dili tugotan ang pag-access sa My Rooms page.
        if ($tenant_id !== $sessionTenant_id) {
            return redirect()->route('tenant-login')->with('error', 'Unauthorized access.');
        }

        // Kuhaa ang pinakabag-o nga lima ka notifications sa tenant.
        $notifications = notificationModel::where('receiverID', $sessionTenant_id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Ihap ang wala pa mabasang notifications sa tenant.
        $unreadCount = notificationModel::where('receiverID', $tenant_id)
            ->where('isRead', false)
            ->count();

        // Pangitaa ang tenant information base sa iyang ID.
        $tenant = tenantModel::find($tenant_id);

        // I-redirect ang user kung walay makita nga tenant record.
        if (!$tenant) {
            return redirect()->route('tenant-login')->with('error', 'Landlord not found.');
        }

        // Title nga gamiton sa My Rooms page.
        $title = 'Tenant My Rooms - DormDash';

        // I-load ang My Rooms page uban sa tenant ug notification information.
        return view('tenant.auth.nav.rooms.myrooms', [
            'title' => 'My Rooms',
            'tenant_id' => $tenant_id,
            'cssPath' => asset('css/tenantpage/auth/roomdetails.css'),
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    // Kuhaa ang tanang approved rooms sa tenant.
    public function myroomsList($tenant_id)
    {
        // Kuhaa ang approved tenant records uban sa ilang room, dorm,
        // landlord, room features, ug payment information.
        $approvedTenants = approvetenantsModel::with([
            'room.dorm',
            'room.dorm.landlord',
            'room.features',
            'payments'
        ])
            ->where('fktenantID', $tenant_id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Ibalik ang listahan sa approved rooms isip JSON response.
        return response()->json([
            'rooms' => $approvedTenants
        ]);
    }

    // Proseso sa pag-request sa tenant og rent extension.
    public function extendRent(Request $request)
    {
        // Kuhaa ang gipiling payment option ug approved tenant ID.
        $paymentOption = $request->input('paymentOption');
        $approveID = $request->input('approveID');

        // I-validate ang payment option ug uban pang payment information.
        // Lahi ang required fields depende kung online o onsite ang payment.
        $validator = Validator::make($request->all(), [
            'paymentOption' => 'required|string|in:online,onsite',
            'approveID' => 'required|integer|exists:approved_tenants,approvedID',
            'paymentType' => $paymentOption === 'online' ? 'required|string|max:50' : 'nullable|string|max:50',
            'paymentImage' => $paymentOption === 'online' ? 'required|image|mimes:jpeg,png,jpg|max:2048' : 'nullable',
            'amount' => $paymentOption === 'online' ? 'required|numeric|min:1' : 'nullable|numeric|min:1',
        ]);

        // Ibalik ang validation errors kung naay invalid o kulang nga input.
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Pangitaa ang approved tenant uban sa room, landlord, ug payment information.
        $approve = approvetenantsModel::with('room.landlord', 'payments')
            ->find($approveID);

        // Hunongon ang proseso kung wala makita ang approved tenant.
        if (!$approve) {
            return response()->json([
                'status' => 'error',
                'message' => 'Approved tenant not found.'
            ], 404);
        }

        // Kuhaa ang tenant information uban sa landlord ug dorm details.
        $tenant = approvetenantsModel::with('room.landlord', 'room.dorm')
            ->find($approveID);

        // Kuhaa ang landlord nga tag-iya sa room.
        $landlord = $approve->room->landlord ?? null;

        // Siguraduhon nga naa ang tenant ug landlord information.
        if (!$tenant || !$landlord) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tenant or landlord not found.'
            ], 404);
        }

        // Kuhaa ang payment records nga konektado sa approved tenant.
        $payment = $approve->payments()->where('fkapprovedID', $approveID);

        // Proseso kung online payment ang gipili sa tenant.
        if ($paymentOption === 'online') {

            // Default value sa payment image URL kung wala pa ma-upload.
            $mainImageUrl = null;

            // Susiha kung adunay payment image nga gi-upload.
            if ($request->hasFile('paymentImage')) {
                // Kuhaa ang uploaded payment image.
                $image1 = $request->file('paymentImage');

                // Himoa ang filename gamit ang current timestamp ug file extension.
                $image1Name = time() . '_1.' . $image1->getClientOriginalExtension();

                // I-save ang payment image sulod sa public storage directory.
                $image1->storeAs('public/uploads/roomImages', $image1Name);

                // Himoa ang public URL sa uploaded payment image.
                $mainImageUrl = asset('storage/uploads/roomImages/' . $image1Name);
            }

            // I-save ang online extension payment isip pending.
            // Kinahanglan pa kini og review ug confirmation sa landlord.
            approvepaymentModel::create([
                'fkapprovedID' => $approveID,
                'paymentType' => $request->paymentOption,
                'amount' => $request->amount,
                'status' => 'pending',
                'paymentImage' => $mainImageUrl,
            ]);

            // I-update ang payment option ug extension status sa tenant.
            $tenant->paymentOption = $paymentOption;

            // I-update ang existing payment status ngadto sa pending.
            $payment->update(['status' => 'pending']);

            // I-set ang extension decision ug payment status ngadto sa pending.
            $tenant->extension_decision = 'pending';
            $tenant->notifyRent = 1;
            $tenant->extension_payment_status = 'pending';

            // I-save ang updated tenant information.
            $tenant->save();

            // Paghimo og notification para mahibaw-an sa landlord ang online extension payment.
            $notifications = notificationModel::create([
                'senderID' => $approve->fktenantID,
                'senderType' => 'tenant',
                'receiverID' => $landlord->landlordID,
                'receiverType' => 'landlord',
                'title' => 'Checked Tenant Extension Payment',
                'message' => "A tenant has submitted payment for the rent extension of Room #{$approve->room->roomNumber}. Please review and confirm.",
                'isRead' => false,
                'readAt' => null,
            ]);

            // I-broadcast ang notification aron ma-notify dayon ang landlord.
            broadcast(new \App\Events\NewNotificationEvent($notifications));

            // Himoa ang mensahe para sa SMS ug email notification.
            $message = "DormDash: A tenant has submitted an online payment for the rent extension of Room #{$approve->room->roomNumber}. Please login to review and confirm.";

            // Sulayi pagpadala og SMS gamit ang iProG SMS service.
            try {
                app(IprogSMSService::class)->sendSMS(
                    $landlord->phoneNumber,
                    $message
                );
            } catch (\Throwable $e) {
                // I-record ang error kung mapakyas ang SMS sending.
                Log::error('Rent extension SMS failed: ' . $e->getMessage());
            }

            // Sulayi pagpadala og email notification ngadto sa landlord.
            try {
                Mail::raw($message, function ($mail) use ($landlord) {
                    $mail->to($landlord->email)
                        ->subject('DormDash - Rent Extension Payment');
                });
            } catch (\Throwable $e) {
                // I-record ang error kung mapakyas ang email sending.
                Log::error('Rent extension email failed: ' . $e->getMessage());
            }

            // Ibalik ang success response human ma-submit ang online extension payment.
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully paid extension, wait for landlord approval'
            ]);
        }

        // Proseso kung onsite payment ang gipili sa tenant.
        else if ($paymentOption === 'onsite') {

            // I-update ang payment option ug extension status sa tenant.
            $tenant->paymentOption = $paymentOption;
            $tenant->extension_decision = 'pending';
            $tenant->notifyRent = 1;
            $tenant->extension_payment_status = 'pending';

            // I-update ang existing payment status ngadto sa pending.
            $payment->update(['status' => 'pending']);

            // I-save ang updated tenant information.
            $tenant->save();

            // Paghimo og notification aron mahibaw-an sa landlord nga onsite payment ang gipili.
            $notifications = notificationModel::create([
                'senderID' => $approve->fktenantID,
                'senderType' => 'tenant',
                'receiverID' => $landlord->landlordID,
                'receiverType' => 'landlord',
                'title' => 'Onsite Payment Chosen',
                'message' => "The tenant has chosen onsite payment for the rent extension of Room #{$approve->room->roomNumber}. Please coordinate with the tenant directly for the payment.",
                'isRead' => false,
                'readAt' => null,
            ]);

            // I-broadcast ang notification para mahibaw-an dayon sa landlord.
            broadcast(new \App\Events\NewNotificationEvent($notifications));

            // Himoa ang mensahe para ipahibalo ang gipiling onsite payment.
            $message = "DormDash: The tenant has chosen onsite payment for the rent extension of Room #{$approve->room->roomNumber}. Please coordinate with the tenant directly.";

            // Sulayi pagpadala og SMS notification ngadto sa landlord.
            try {
                app(IprogSMSService::class)->sendSMS(
                    $landlord->phoneNumber,
                    $message
                );
            } catch (\Throwable $e) {
                // I-record ang error kung mapakyas ang SMS sending.
                Log::error('Onsite extension SMS failed: ' . $e->getMessage());
            }

            // Sulayi pagpadala og email notification ngadto sa landlord.
            try {
                Mail::raw($message, function ($mail) use ($landlord) {
                    $mail->to($landlord->email)
                        ->subject('DormDash - Onsite Rent Extension');
                });
            } catch (\Throwable $e) {
                // I-record ang error kung mapakyas ang email sending.
                Log::error('Onsite extension email failed: ' . $e->getMessage());
            }

            // Ibalik ang success response ug pahimangno nga moduol sa landlord para sa bayad.
            return response()->json([
                'status' => 'success',
                'message' => 'You selected On-site Payment. Please proceed to your landlord to complete the payment.'
            ]);
        }
    }

    // Paghimo og PDF receipt base sa payment records sa tenant.
    public function generateReceipt($id)
    {
        // Kuhaa ang approved tenant uban sa room, dorm, landlord, ug payment information.
        $tenant = approvetenantsModel::with(['room.dorm', 'room.landlord', 'payments'])->findOrFail($id);

        // I-initialize ang payment variables.
        $latestPayment = null;
        $totalPaid = 0;

        // Pilia kung asa kuhaon ang payment records base sa source type.
        if ($tenant->source_type === 'Booking') {
            // Kuhaa ang payments gikan sa booking payment records.
            $payments = bookingpaymentModel::where('fkbookingID', $tenant->source_id)->get();
        } elseif ($tenant->source_type === 'Reservation') {
            // Kuhaa ang payments gikan sa reservation payment records.
            $payments = reservationpaymentModel::where('reservationID', $tenant->source_id)->get();
        } else {
            // Gamiton ang approved tenant payments kung walay laing source type nga motakdo.
            $payments = $tenant->payments; // fallback to approvepayments
        }

        // Kwentaha ang total nga nabayran ug kuhaa ang pinakabag-o nga payment.
        $totalPaid = $payments->sum('amount');
        $latestPayment = $payments->sortByDesc('created_at')->first();

        // Andama ang data nga ipasa ngadto sa PDF receipt view.
        $data = [
            'tenant' => $tenant,
            'totalPaid' => $totalPaid,
            'latestPayment' => $latestPayment,
            'paymentType' => $latestPayment->paymentType ?? null
        ];

        // Himoa ang PDF gamit ang receipt view ug ipakita kini sa browser.
        $pdf = PDF::loadView('tenant.receipt.receipt', $data);

        return $pdf->stream('receipt.pdf');
    }

    // Proseso sa pag-submit sa tenant og review ug rating sa dorm.
    public function ReviewAndRating(Request $request)
    {
        // I-validate ang room, approved tenant, rating, ug review information.
        $request->validate([
            'roomID' => 'required',
            'approvedID' => 'required',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:2000',
        ]);

        // Kuhaa ang room uban sa landlord information.
        $room = \App\Models\landlord\roomModel::with('landlord')
            ->find($request->roomID);

        // Hunongon ang proseso kung wala makita ang room.
        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Room not found.'
            ], 404);
        }

        // Kuhaa ang dormitory ID ug approved tenant ID.
        $dormID = $room->fkdormID;
        $approvedID = $request->approvedID;

        // Susiha kung adunay existing review gikan sa maong approved tenant para sa dorm.
        $existing = reviewandratingModel::where('fkdormID', $dormID)
            ->where('fkapprovedID', $approvedID)
            ->first();

        // Dili tugotan ang tenant nga mosubmit og review pag-usab kung nakareview na siya.
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reviewed this dorm.'
            ], 409);
        }

        // I-save ang bag-ong review ug rating sa database.
        reviewandratingModel::create([
            'fkdormID' => $dormID,
            'fkapprovedID' => $approvedID,
            'rating' => $request->rating,
            'review' => $request->review,
        ]);

        // Pangitaa ang approved tenant nga nagsubmit sa review.
        $tenant = \App\Models\tenant\approvetenantsModel::find($approvedID);

        // I-update ang has_rated flag aron mahibaw-an nga nakahatag na siya og rating.
        if ($tenant) {
            $tenant->has_rated = true;
            $tenant->save();
        }

        // Kuhaa ang landlord nga tag-iya sa room.
        $landlord = $room->landlord;

        // Paghimo og notification kung adunay landlord information.
        if ($landlord) {
            $notifications = notificationModel::create([
                'senderID' => $tenant->fktenantID ?? $approvedID,
                'senderType' => 'tenant',
                'receiverID' => $landlord->landlordID,
                'receiverType' => 'landlord',
                'title' => 'New Dorm Review',
                'message' => "A tenant submitted a {$request->rating}-star rating for your dorm.",
                'isRead' => false,
                'readAt' => null,
            ]);

            // I-broadcast ang notification aron mahibaw-an dayon sa landlord ang bag-ong review.
            broadcast(new \App\Events\NewNotificationEvent($notifications));

            // Himoa ang mensahe para sa SMS ug email notification.
            $message = "DormDash: A tenant has submitted a {$request->rating}-star rating for your dorm. Please login to view the review.";

            // Sulayi pagpadala og SMS notification ngadto sa landlord.
            try {
                app(IprogSMSService::class)->sendSMS(
                    $landlord->phoneNumber,
                    $message
                );
            } catch (\Throwable $e) {
                // I-record ang error kung mapakyas ang SMS sending.
                Log::error('Review notification SMS failed: ' . $e->getMessage());
            }

            // Sulayi pagpadala og email notification ngadto sa landlord.
            try {
                Mail::raw($message, function ($mail) use ($landlord) {
                    $mail->to($landlord->email)
                        ->subject('DormDash - New Dorm Review');
                });
            } catch (\Throwable $e) {
                // I-record ang error kung mapakyas ang email sending.
                Log::error('Review notification email failed: ' . $e->getMessage());
            }
        }

        // Kuhaa ang tanang reviews sa maong dormitory.
        $reviews = reviewandratingModel::where('fkdormID', $dormID)->get();

        // Kwentaha ang average rating ug iyang equivalent percentage.
        $average = $reviews->avg('rating');
        $percentage = ($average / 5) * 100;

        // Ibalik ang success response uban sa reviews ug rating statistics.
        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully.',
            'reviews' => $reviews,
            'average' => round($average, 2),
            'percentage' => $percentage
        ]);
    }

    // Proseso sa pagpadala sa tenant og issue report ngadto sa landlord.
    public function sendIssue(Request $request)
    {
        // I-validate ang report message ug sender/receiver information.
        $request->validate([
            'message' => 'required|string|max:2000',
            'senderID' => 'required|string',
            'senderRole' => 'required|string|in:tenant,landlord',
            'receiverID' => 'required|string',
        ]);

        // Sugdan ang database transaction aron magkahiusa ang mga database operations.
        DB::beginTransaction();

        try {
            // Himoa ang unique conversation topic base sa tenant ug landlord IDs.
            $topic = "tenant_{$request->senderID}_landlord_{$request->receiverID}";

            // Susiha kung adunay existing conversation para sa maong topic.
            $conversation = conversationModel::where('topic', $topic)->first();

            // Paghimo og bag-ong conversation kung wala pa kini sa database.
            if (!$conversation) {
                $conversation = conversationModel::create([
                    'initiatorID' => $request->senderID,
                    'initiatorRole' => $request->senderRole,
                    'topic' => $topic,
                ]);
            }

            // I-save ang issue report isip bag-ong message sulod sa conversation.
            $message = messageModel::create([
                'conversationID' => $conversation->id,
                'senderID' => $request->senderID,
                'senderRole' => $request->senderRole,
                'receiverID' => $request->receiverID,
                'receiverRole' => 'landlord',
                'message' => $request->message,
                'replyToId' => null,
                'isRead' => false,
                'sentAt' => now(),
            ]);

            // I-broadcast ang message aron ma-update ang real-time conversation sa laing user.
            broadcast(new MessageSent($message))->toOthers();

            // Paghimo og notification para mahibaw-an sa landlord nga adunay tenant nga nagpadala og report.
            $notifications = notificationModel::create([
                'senderID' => $message->senderID,
                'senderType' => 'tenant',
                'receiverID' => $message->receiverID,
                'receiverType' => 'landlord',
                'title' => 'Tenant sent report',
                'message' => 'A tenant has sent you a report.',
                'isRead' => false,
                'readAt' => null,
            ]);

            // I-broadcast ang notification aron ma-notify dayon ang landlord.
            broadcast(new \App\Events\NewNotificationEvent($notifications));

            // I-commit ang database transaction kung malampuson ang mga database operations.
            DB::commit();

            // Kuhaa ang landlord information base sa receiver ID.
            $landlord = \App\Models\landlord\landlordModel::where(
                'landlordID',
                $request->receiverID
            )->first();

            // Magpadala og SMS ug email notification kung adunay landlord record.
            if ($landlord) {
                // Himoa ang mensahe nga nagpahibalo bahin sa bag-ong issue report.
                $notificationMessage = "DormDash: A tenant has sent you a new issue report. Please login to your DormDash account to review and respond.";

                // Sulayi pagpadala og SMS notification.
                try {
                    app(IprogSMSService::class)->sendSMS(
                        $landlord->phoneNumber,
                        $notificationMessage
                    );
                } catch (\Throwable $e) {
                    // I-record ang error kung mapakyas ang SMS sending.
                    Log::error('Issue report SMS failed: ' . $e->getMessage());
                }

                // Sulayi pagpadala og email notification.
                try {
                    Mail::raw($notificationMessage, function ($mail) use ($landlord) {
                        $mail->to($landlord->email)
                            ->subject('DormDash - New Tenant Issue Report');
                    });
                } catch (\Throwable $e) {
                    // I-record ang error kung mapakyas ang email sending.
                    Log::error('Issue report email failed: ' . $e->getMessage());
                }
            }

            // Ibalik ang success response uban sa conversation ug message IDs.
            return response()->json([
                'success' => true,
                'message' => 'Issue sent successfully!',
                'conversationID' => $conversation->id,
                'messageID' => $message->id,
            ]);

        } catch (\Exception $e) {

            // I-rollback ang database changes kung adunay error sa proseso.
            DB::rollBack();

            // Ibalik ang error response kung napakyas ang pagpadala sa issue report.
            return response()->json([
                'success' => false,
                'message' => 'Failed to send issue: ' . $e->getMessage(),
            ], 500);
        }
    }

    // I-update ang desisyon sa tenant bahin sa iyang rent extension.
    public function notifyrentUpdate(Request $request)
    {
        // I-validate ang approved tenant ID ug extension decision.
        $request->validate([
            'approveID' => 'required|integer',
            'decision' => 'required|string',
        ]);

        // Kuhaa ang approved tenant uban sa room, landlord, dorm, ug payment information.
        $tenant = approvetenantsModel::with(
            'room.landlord',
            'room.dorm',
            'payments'
        )->find($request->approveID);

        // Ibalik ang error response kung wala makita ang tenant.
        if (!$tenant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tenant not found'
            ], 404);
        }

        // Kuhaa ang landlord nga konektado sa tenant's room.
        $landlord = $tenant->room->landlord ?? null;

        // Ibalik ang error response kung wala makita ang landlord.
        if (!$landlord) {
            return response()->json([
                'status' => 'error',
                'message' => 'Landlord not found'
            ], 404);
        }

        // I-update ang rent notification flag ug extension decision sa tenant.
        $tenant->notifyRent = false;
        $tenant->extension_decision = $request->decision;

        // I-save ang updated tenant information.
        $tenant->save();

        // Himoa ang notification message nga naglakip sa tenant name,
        // extension decision, room number, ug dormitory name.
        $notificationMessage = 'Tenant ' . $tenant->firstname . ' ' . $tenant->lastname .
            ' has ' . $request->decision . ' the rent extension request for room ' .
            $tenant->room->roomNumber . ' at ' . $tenant->room->dorm->dormName . '.';

        // Paghimo og notification para mahibaw-an sa landlord ang extension decision.
        $notification = notificationModel::create([
            'senderID' => $tenant->fktenantID,
            'senderType' => 'tenant',
            'receiverID' => $landlord->landlordID,
            'receiverType' => 'landlord',
            'title' => 'Extension Decision',
            'message' => $notificationMessage,
            'isRead' => false,
            'readAt' => null,
        ]);

        // I-broadcast ang notification aron ma-update dayon ang landlord.
        broadcast(new \App\Events\NewNotificationEvent($notification));

        // Himoa ang mensahe para sa SMS ug email notification.
        $message = "DormDash: " . $notificationMessage . " Please login to your DormDash account to view the update.";

        // Sulayi pagpadala og SMS notification ngadto sa landlord.
        try {
            app(IprogSMSService::class)->sendSMS(
                $landlord->phoneNumber,
                $message
            );
        } catch (\Throwable $e) {
            // I-record ang error kung mapakyas ang SMS sending.
            Log::error('Rent extension decision SMS failed: ' . $e->getMessage());
        }

        // Sulayi pagpadala og email notification ngadto sa landlord.
        try {
            Mail::raw($message, function ($mail) use ($landlord) {
                $mail->to($landlord->email)
                    ->subject('DormDash - Rent Extension Decision');
            });
        } catch (\Throwable $e) {
            // I-record ang error kung mapakyas ang email sending.
            Log::error('Rent extension decision email failed: ' . $e->getMessage());
        }

        // Ibalik ang success response human ma-update ang rent status ug ma-notify ang landlord.
        return response()->json([
            'status' => 'success',
            'message' => 'Rent status updated and landlord notified'
        ]);
    }
}

