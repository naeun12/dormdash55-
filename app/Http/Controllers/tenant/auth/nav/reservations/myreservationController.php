<?php
namespace App\Http\Controllers\tenant\auth\nav\reservations;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\tenant\reservationModel;
use App\Models\tenant\reservationpaymentModel;
use App\Models\tenant\tenantModel;
use App\Models\notificationModel;
use Carbon\Carbon;
use App\Services\IprogSMSService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
class myreservationController extends Controller
{
    public function viewReservation($tenant_id)
        {
            $sessionTenant_id = session('tenant_id');
         $notifications = notificationModel::where('receiverID', $sessionTenant_id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
            $unreadCount = notificationModel::where('receiverID', $tenant_id)
            ->where('isRead', false)
            ->count();
            if (!$sessionTenant_id) {
                return redirect()->route('tenant-login')->with('error', 'Please log in as a landlord.');
            }
       
            if ($tenant_id !== $sessionTenant_id) {
                return redirect()->route('tenant-login')->with('error', 'Unauthorized access.');
            }
       
            $tenant = tenantModel::find($tenant_id);
            if (!$tenant) {
                return redirect()->route('tenant-login')->with('error', 'Landlord not found.');
            }
            $title = 'Tenant room Details - DormDash';
            return view('tenant.auth.nav.reservations.reservation',['title' => 'Reservations',
            'tenant_id' => $tenant_id,
            'cssPath' => asset('css/tenantpage/auth/roomdetails.css'),
            'notifications' => $notifications,
             'unread_count' => $unreadCount,]);
        }
        public function myReservationList($tenant_id)
        {
            $reservations = reservationModel::with(['tenant','room.dorm', ])
                ->where('fktenantID', $tenant_id)
                ->orderBy('updated_at', 'desc')
                ->get();
                foreach($reservations as $reservation)
                {
                    if(Carbon::parse($reservation->created_at)->diffInDays(now()) > 3)
                    {
                        if($reservation->status === 'pending' || $reservation->status === 'confirmed')
                        {
                            $reservation->status = 'expired';
                            $reservation->save();
                        }  
                    }
                }


            return response()->json($reservations);
        }
       public function reservationDetails($reservationID)


{
    $reservation = reservationModel::with([
        'tenant',
        'room.dorm',
        'room.landlord',
        'room.currentTenant' => function ($query) {
    $query->where('status', 'active');
}


    ])->find($reservationID);


    return response()->json([
        'status' => 'success',
        'data'   => $reservation
    ]);
}




      public function cancelReservation($reservationID)
{
    $reservation = reservationModel::with([
        'tenant',
        'room.dorm',
        'room.landlord'
    ])->where('reservationID', $reservationID)->first();


    if (!$reservation) {
        return response()->json([
            'message' => 'Reservation not found'
        ], 404);
    }


    $landlord = $reservation->room->landlord ?? null;
    $tenant = $reservation->tenant;


    // Update reservation status
    $reservation->status = 'cancelled';
    $reservation->save();


    if ($landlord && $tenant) {


        $notificationMessage = "A tenant has cancelled their reservation for Room #{$reservation->room->roomNumber}.";


        // In-app notification
        $notifications = notificationModel::create([
            'senderID' => $tenant->tenantID,
            'senderType' => 'tenant',
            'receiverID' => $landlord->landlordID,
            'receiverType' => 'landlord',
            'title' => 'Tenant Cancelled Reservation',
            'message' => $notificationMessage,
            'isRead' => false,
            'readAt' => null,
        ]);


        broadcast(new \App\Events\NewNotificationEvent($notifications));


        $message = "DormDash: {$notificationMessage} Please login to your account to view the update.";


        // SMS Notification
        try {
            app(IprogSMSService::class)->sendSMS(
                $landlord->phoneNumber,
                $message
            );
        } catch (\Throwable $e) {
            Log::error('Reservation cancellation SMS failed: ' . $e->getMessage());
        }


        // Email Notification
        try {
            Mail::raw($message, function ($mail) use ($landlord) {
                $mail->to($landlord->email)
                    ->subject('DormDash - Reservation Cancelled');
            });
        } catch (\Throwable $e) {
            Log::error('Reservation cancellation email failed: ' . $e->getMessage());
        }
    }


    return response()->json([
        'message' => 'Reservation cancelled successfully',
        'reservation' => $reservation
    ]);
}
   public function paymentReservation(Request $request, $reservationID)
{
    try {


        $validatedData = $request->validate([
            'moveInDate' => 'required|date',
            'paymentType' => 'required|string|max:255',
            'paymentImage' => 'required|image|mimes:jpeg,png,jpg|max:1024',
            'tenant_id' => 'required|exists:tenants,tenantID',
            'amount' => 'required|numeric|min:0',
        ], [
            'paymentType.required' => 'Please select a payment method.',
            'paymentImage.required' => 'Please upload a payment screenshot.',
            'paymentImage.mimes' => 'Payment image must be in JPEG, PNG, or JPG format.',
            'paymentImage.max' => 'Payment image must not be larger than 1MB.',
        ]);


        $reservation = reservationModel::findOrFail($reservationID);


        $mainImageUrl = null;


        if ($request->hasFile('paymentImage')) {
            $image1 = $request->file('paymentImage');
            $image1Name = time() . '_1.' . $image1->getClientOriginalExtension();


            $image1->storeAs('public/uploads/roomImages', $image1Name);


            $mainImageUrl = asset('storage/uploads/roomImages/' . $image1Name);
        }


        $reservation->update([
            'moveInDate' => $request->moveInDate
        ]);


        $reservationPayment = reservationpaymentModel::create([
            'reservationID' => $reservationID,
            'paymentType' => $request->paymentType,
            'paymentImage' => $mainImageUrl,
            'amount' => $request->amount,
            'status' => 'pending'
        ]);


        $reservation = reservationModel::with([
            'tenant',
            'room.dorm',
            'room.landlord'
        ])->where('reservationID', $reservationID)->first();


        if (!$reservation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Reservation not found.'
            ], 404);
        }


        $landlord = $reservation->room->landlord ?? null;


        if ($landlord) {


            // In-app notification
            $notificationMessage = "A tenant has paid for Room #{$reservation->room->roomNumber}. Please review the payment.";


            $notifications = notificationModel::create([
                'senderID' => $request->tenant_id,
                'senderType' => 'tenant',
                'receiverID' => $landlord->landlordID,
                'receiverType' => 'landlord',
                'title' => 'Checked Tenant Payment',
                'message' => $notificationMessage,
                'isRead' => false,
                'readAt' => null,
            ]);
            broadcast(new \App\Events\NewNotificationEvent($notifications));
            $message = "DormDash: A tenant has submitted payment for Reservation Room #{$reservation->room->roomNumber}. Amount: PHP " . number_format($request->amount, 2) . ". Please login to review and confirm.";
            try {
                app(IprogSMSService::class)->sendSMS(
                    $landlord->phoneNumber,
                    $message
                );
            } catch (\Throwable $e) {
                Log::error('Reservation payment SMS failed: ' . $e->getMessage());
            }
            try {
                Mail::raw($message, function ($mail) use ($landlord) {
                    $mail->to($landlord->email)
                        ->subject('DormDash - Reservation Payment Submitted');
                });
            } catch (\Throwable $e) {
                Log::error('Reservation payment email failed: ' . $e->getMessage());
            }
        }


        if ($reservation) {
            $reservation->status = 'paid';
            $reservation->save();
        }


        return response()->json([
            'status' => 'success',
            'message' => 'Payment successfully. Waiting for landlord confirmation.',
            'data' => $reservationPayment
        ]);


    } catch (\Illuminate\Validation\ValidationException $e) {


        return response()->json([
            'status' => 'validation_error',
            'errors' => $e->errors(),
        ], 422);


    } catch (\Exception $e) {


        return response()->json([
            'status' => 'error',
            'message' => 'Reservation failed.',
            'error' => $e->getMessage()
        ], 500);
    }
}    
    }











