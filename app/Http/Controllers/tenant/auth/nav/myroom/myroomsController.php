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
    public function myroomsIndex($tenant_id)
        {
            $sessionTenant_id = session('tenant_id');
       
            if (!$sessionTenant_id) {
                return redirect()->route('tenant-login')->with('error', 'Please log in as a landlord.');
            }
       
            if ($tenant_id !== $sessionTenant_id) {
                return redirect()->route('tenant-login')->with('error', 'Unauthorized access.');
            }
            $notifications = notificationModel::where('receiverID', $sessionTenant_id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
            $unreadCount = notificationModel::where('receiverID', $tenant_id)
            ->where('isRead', false)
            ->count();
       
            $tenant = tenantModel::find($tenant_id);
            if (!$tenant) {
                return redirect()->route('tenant-login')->with('error', 'Landlord not found.');
            }
            $title = 'Tenant My Rooms - DormDash';
            return view('tenant.auth.nav.rooms.myrooms',['title' => 'My Rooms',
            'tenant_id' => $tenant_id,
            'cssPath' => asset('css/tenantpage/auth/roomdetails.css'),
            'notifications' => $notifications,
             'unread_count' => $unreadCount,]);
        }


   public function myroomsList($tenant_id)
{
    $approvedTenants = approvetenantsModel::with([
        'room.dorm',
        'room.dorm.landlord',
        'room.features',
        'payments'
    ])
    ->where('fktenantID', $tenant_id)
    ->orderBy('created_at', 'desc')
    ->get();


    return response()->json([
        'rooms' => $approvedTenants
    ]);
}


public function extendRent(Request $request)
{
    $paymentOption = $request->input('paymentOption');
    $approveID = $request->input('approveID');


    $validator = Validator::make($request->all(), [
        'paymentOption' => 'required|string|in:online,onsite',
        'approveID' => 'required|integer|exists:approved_tenants,approvedID',
        'paymentType' => $paymentOption === 'online' ? 'required|string|max:50' : 'nullable|string|max:50',
        'paymentImage' => $paymentOption === 'online' ? 'required|image|mimes:jpeg,png,jpg|max:2048' : 'nullable',
        'amount' => $paymentOption === 'online' ? 'required|numeric|min:1' : 'nullable|numeric|min:1',
    ]);
    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'errors' => $validator->errors()
        ], 422);
    }
    $approve = approvetenantsModel::with('room.landlord', 'payments')
        ->find($approveID);
    if (!$approve) {
        return response()->json([
            'status' => 'error',
            'message' => 'Approved tenant not found.'
        ], 404);
    }
    $tenant = approvetenantsModel::with('room.landlord', 'room.dorm')
        ->find($approveID);
    $landlord = $approve->room->landlord ?? null;
    if (!$tenant || !$landlord) {
        return response()->json([
            'status' => 'error',
            'message' => 'Tenant or landlord not found.'
        ], 404);
    }
    $payment = $approve->payments()->where('fkapprovedID', $approveID);
    if ($paymentOption === 'online') {
        $mainImageUrl = null;
        if ($request->hasFile('paymentImage')) {
            $image1 = $request->file('paymentImage');
            $image1Name = time() . '_1.' . $image1->getClientOriginalExtension();
            $image1->storeAs('public/uploads/roomImages', $image1Name);
            $mainImageUrl = asset('storage/uploads/roomImages/' . $image1Name);
        }
        approvepaymentModel::create([
            'fkapprovedID' => $approveID,
            'paymentType' => $request->paymentOption,
            'amount' => $request->amount,
            'status' => 'pending',
            'paymentImage' => $mainImageUrl,
        ]);
        $tenant->paymentOption = $paymentOption;
        $payment->update(['status' => 'pending']);
        $tenant->extension_decision = 'pending';
        $tenant->notifyRent = 1;
        $tenant->extension_payment_status = 'pending';
        $tenant->save();
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
        broadcast(new \App\Events\NewNotificationEvent($notifications));
        $message = "DormDash: A tenant has submitted an online payment for the rent extension of Room #{$approve->room->roomNumber}. Please login to review and confirm.";
        try {
            app(IprogSMSService::class)->sendSMS(
                $landlord->phoneNumber,
                $message
            );
        } catch (\Throwable $e) {
            Log::error('Rent extension SMS failed: ' . $e->getMessage());
        }
        try {
            Mail::raw($message, function ($mail) use ($landlord) {
                $mail->to($landlord->email)
                    ->subject('DormDash - Rent Extension Payment');
            });
        } catch (\Throwable $e) {
            Log::error('Rent extension email failed: ' . $e->getMessage());
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Successfully paid extension, wait for landlord approval'
        ]);
    }
    else if ($paymentOption === 'onsite') {
        $tenant->paymentOption = $paymentOption;
        $tenant->extension_decision = 'pending';
        $tenant->notifyRent = 1;
        $tenant->extension_payment_status = 'pending';
        $payment->update(['status' => 'pending']);
        $tenant->save();
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
        broadcast(new \App\Events\NewNotificationEvent($notifications));
        $message = "DormDash: The tenant has chosen onsite payment for the rent extension of Room #{$approve->room->roomNumber}. Please coordinate with the tenant directly.";
        try {
            app(IprogSMSService::class)->sendSMS(
                $landlord->phoneNumber,
                $message
            );
        } catch (\Throwable $e) {
            Log::error('Onsite extension SMS failed: ' . $e->getMessage());
        }


        try {
            Mail::raw($message, function ($mail) use ($landlord) {
                $mail->to($landlord->email)
                    ->subject('DormDash - Onsite Rent Extension');
            });
        } catch (\Throwable $e) {
            Log::error('Onsite extension email failed: ' . $e->getMessage());
        }


        return response()->json([
            'status' => 'success',
            'message' => 'You selected On-site Payment. Please proceed to your landlord to complete the payment.'
        ]);
    }
}
    public function generateReceipt($id)
{
    $tenant = approvetenantsModel::with(['room.dorm', 'room.landlord', 'payments'])->findOrFail($id);


    // Initialize payment variables
    $latestPayment = null;
    $totalPaid = 0;


    // Determine payment based on source type
    if($tenant->source_type === 'Booking') {
        $payments = bookingpaymentModel::where('fkbookingID', $tenant->source_id)->get();
    } elseif($tenant->source_type === 'Reservation') {
        $payments = reservationpaymentModel::where('reservationID', $tenant->source_id)->get();
    } else {
        $payments = $tenant->payments; // fallback to approvepayments
    }


    // Compute total and latest payment
    $totalPaid = $payments->sum('amount');
    $latestPayment = $payments->sortByDesc('created_at')->first();


    // Prepare data for the PDF
    $data = [
        'tenant' => $tenant,
        'totalPaid' => $totalPaid,
        'latestPayment' => $latestPayment,
        'paymentType' => $latestPayment->paymentType ?? null
    ];


    $pdf = PDF::loadView('tenant.receipt.receipt', $data);
    return $pdf->stream('receipt.pdf');
}


  public function ReviewAndRating(Request $request)
{
    $request->validate([
        'roomID' => 'required',
        'approvedID' => 'required',
        'rating' => 'required|integer|min:1|max:5',
        'review' => 'nullable|string|max:2000',
    ]);


    $room = \App\Models\landlord\roomModel::with('landlord')
        ->find($request->roomID);


    if (!$room) {
        return response()->json([
            'success' => false,
            'message' => 'Room not found.'
        ], 404);
    }


    $dormID = $room->fkdormID;
    $approvedID = $request->approvedID;


    $existing = reviewandratingModel::where('fkdormID', $dormID)
        ->where('fkapprovedID', $approvedID)
        ->first();


    if ($existing) {
        return response()->json([
            'success' => false,
            'message' => 'You have already reviewed this dorm.'
        ], 409);
    }


    // Save new review
    reviewandratingModel::create([
        'fkdormID' => $dormID,
        'fkapprovedID' => $approvedID,
        'rating' => $request->rating,
        'review' => $request->review,
    ]);
    $tenant = \App\Models\tenant\approvetenantsModel::find($approvedID);
    if ($tenant) {
        $tenant->has_rated = true;
        $tenant->save();
    }
    $landlord = $room->landlord;
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
        broadcast(new \App\Events\NewNotificationEvent($notifications));
        $message = "DormDash: A tenant has submitted a {$request->rating}-star rating for your dorm. Please login to view the review.";
        try {
            app(IprogSMSService::class)->sendSMS(
                $landlord->phoneNumber,
                $message
            );
        } catch (\Throwable $e) {
            Log::error('Review notification SMS failed: ' . $e->getMessage());
        }
        try {
            Mail::raw($message, function ($mail) use ($landlord) {
                $mail->to($landlord->email)
                    ->subject('DormDash - New Dorm Review');
            });
        } catch (\Throwable $e) {
            Log::error('Review notification email failed: ' . $e->getMessage());
        }
    }
    $reviews = reviewandratingModel::where('fkdormID', $dormID)->get();
    $average = $reviews->avg('rating');
    $percentage = ($average / 5) * 100;


    return response()->json([
        'success' => true,
        'message' => 'Review submitted successfully.',
        'reviews' => $reviews,
        'average' => round($average, 2),
        'percentage' => $percentage
    ]);
}
 public function sendIssue(Request $request)
{
    $request->validate([
        'message' => 'required|string|max:2000',
        'senderID' => 'required|string',
        'senderRole' => 'required|string|in:tenant,landlord',
        'receiverID' => 'required|string',
    ]);


    DB::beginTransaction();


    try {
        $topic = "tenant_{$request->senderID}_landlord_{$request->receiverID}";
        $conversation = conversationModel::where('topic', $topic)->first();


        // Check if conversation already exists
        if (!$conversation) {
            $conversation = conversationModel::create([
                'initiatorID' => $request->senderID,
                'initiatorRole' => $request->senderRole,
                'topic' => $topic,
            ]);
        }


        // Create the message
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


        broadcast(new MessageSent($message))->toOthers();


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


        broadcast(new \App\Events\NewNotificationEvent($notifications));


        DB::commit();


        // Get landlord details
        $landlord = \App\Models\landlord\landlordModel::where(
            'landlordID',
            $request->receiverID
        )->first();


        // SMS and Email Notification
        if ($landlord) {
            $notificationMessage = "DormDash: A tenant has sent you a new issue report. Please login to your DormDash account to review and respond.";


            // SMS
            try {
                app(IprogSMSService::class)->sendSMS(
                    $landlord->phoneNumber,
                    $notificationMessage
                );
            } catch (\Throwable $e) {
                Log::error('Issue report SMS failed: ' . $e->getMessage());
            }


            // Email
            try {
                Mail::raw($notificationMessage, function ($mail) use ($landlord) {
                    $mail->to($landlord->email)
                        ->subject('DormDash - New Tenant Issue Report');
                });
            } catch (\Throwable $e) {
                Log::error('Issue report email failed: ' . $e->getMessage());
            }
        }


        return response()->json([
            'success' => true,
            'message' => 'Issue sent successfully!',
            'conversationID' => $conversation->id,
            'messageID' => $message->id,
        ]);


    } catch (\Exception $e) {
        DB::rollBack();


        return response()->json([
            'success' => false,
            'message' => 'Failed to send issue: ' . $e->getMessage(),
        ], 500);
    }
}
public function notifyrentUpdate(Request $request)
{
    $request->validate([
        'approveID' => 'required|integer',
        'decision' => 'required|string',
    ]);


    $tenant = approvetenantsModel::with(
        'room.landlord',
        'room.dorm',
        'payments'
    )->find($request->approveID);


    if (!$tenant) {
        return response()->json([
            'status' => 'error',
            'message' => 'Tenant not found'
        ], 404);
    }


    $landlord = $tenant->room->landlord ?? null;


    if (!$landlord) {
        return response()->json([
            'status' => 'error',
            'message' => 'Landlord not found'
        ], 404);
    }


    // Update status
    $tenant->notifyRent = false;
    $tenant->extension_decision = $request->decision;
    $tenant->save();


    // Send notification to landlord
    $notificationMessage = 'Tenant ' . $tenant->firstname . ' ' . $tenant->lastname .
        ' has ' . $request->decision . ' the rent extension request for room ' .
        $tenant->room->roomNumber . ' at ' . $tenant->room->dorm->dormName . '.';


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


    // Broadcast notification
    broadcast(new \App\Events\NewNotificationEvent($notification));


    // SMS and Email Notification
    $message = "DormDash: " . $notificationMessage . " Please login to your DormDash account to view the update.";


    // SMS
    try {
        app(IprogSMSService::class)->sendSMS(
            $landlord->phoneNumber,
            $message
        );
    } catch (\Throwable $e) {
        Log::error('Rent extension decision SMS failed: ' . $e->getMessage());
    }


    // Email
    try {
        Mail::raw($message, function ($mail) use ($landlord) {
            $mail->to($landlord->email)
                ->subject('DormDash - Rent Extension Decision');
        });
    } catch (\Throwable $e) {
        Log::error('Rent extension decision email failed: ' . $e->getMessage());
    }


    return response()->json([
        'status' => 'success',
        'message' => 'Rent status updated and landlord notified'
    ]);
}


}



