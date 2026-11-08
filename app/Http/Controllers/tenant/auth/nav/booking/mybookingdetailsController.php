<?php
namespace App\Http\Controllers\tenant\auth\nav\booking;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\landlord\bookingModel;
use App\Models\landlord\roomModel;
use App\Models\tenant\bookingpaymentModel;
use App\Models\tenant\tenantModel;
use App\Models\notificationModel;
use Carbon\Carbon;
use App\Services\IprogSMSService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;


class mybookingdetailsController extends Controller
{
    public function viewBookingDetails($tenant_id,$booking_id)
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
            return view('tenant.auth.nav.bookings.mybookingdetails',['title' => 'Tenant - Booking Details',
            'tenant_id' => $tenant_id,
            'booking_id' => $booking_id,
            'cssPath' => asset('css/tenantpage/auth/roomdetails.css'),
             'notifications' => $notifications,
             'unread_count' => $unreadCount,
        ]);
        }
        public function bookingDetails($booking_id)
        {
            $bookings = bookingModel::with(['tenant','room.dorm'])
                ->where('bookingID', $booking_id)
                ->get();


            return response()->json($bookings);
        }
       


public function payRoom(Request $request)
{
    try {
        // Step 1: Validation
        $validated = $request->validate([
            'fkbookingID' => 'required|exists:bookings,bookingID',
            'paymentType' => 'required|string',
            'paymentImage' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'amount' => 'required|numeric|min:0'
        ], [
            'fkbookingID.required' => 'Booking ID is required.',
            'fkbookingID.exists' => 'Booking ID does not exist.',
            'paymentType.required' => 'Please select a payment type.',
            'paymentType.string' => 'Payment type must be a valid string.',
            'paymentImage.image' => 'The file must be an image.',
            'paymentImage.mimes' => 'The image must be a jpeg, png, or jpg.',
            'paymentImage.max' => 'The image must not be larger than 2MB.',
        ]);


        $mainImageUrl = null;


        if ($request->hasFile('paymentImage')) {
            $image1 = $request->file('paymentImage');
            $image1Name = time() . '_1.' . $image1->getClientOriginalExtension();
            $image1->storeAs('public/uploads/roomImages', $image1Name);
            $mainImageUrl = asset('storage/uploads/roomImages/' . $image1Name);
        }


        $payment = bookingpaymentModel::create([
            'fkbookingID' => $request->fkbookingID,
            'paymentType' => $request->paymentType,
            'paymentImage' => $mainImageUrl,
            'amount' => $request->amount
        ]);


        bookingModel::with('tenant', 'room.dorm')
            ->where('bookingID', $request->fkbookingID)
            ->update([
                'status' => 'paid'
            ]);


        $booking = bookingModel::with(['tenant', 'room.dorm'])
            ->where('bookingID', $request->fkbookingID)
            ->first();


        $landlordBooking = bookingModel::with('room.landlord')
            ->find($request->fkbookingID);


        $landlord = optional($landlordBooking->room)->landlord;
        $landlordID = optional($landlord)->landlordID;


        // Create in-app notification
        $notifications = notificationModel::create([
            'senderID' => $booking->fktenantID,
            'senderType' => 'tenant',
            'receiverID' => $landlordID,
            'receiverType' => 'landlord',
            'title' => 'Checked Tenant Payment',
            'message' => "The tenant has completed the payment for Room #{$booking->room->roomNumber}. Please review it.",
            'isRead' => false,
            'readAt' => null,
        ]);


        broadcast(new \App\Events\NewNotificationEvent($notifications));


        // Send SMS and email to landlord
        if ($landlord) {
            $message = "DormDash: A tenant has submitted a payment for Room #{$booking->room->roomNumber}. Amount: PHP " . number_format($payment->amount, 2) . ". Please log in to DormDash to review the payment.";


            // Send SMS using iProG SMS
            try {
                if (!empty($landlord->phoneNumber)) {
                    app(IprogSMSService::class)->sendSMS(
                        $landlord->phoneNumber,
                        $message
                    );
                }
            } catch (\Exception $smsError) {
                Log::error('Landlord payment SMS failed: ' . $smsError->getMessage());
            }


            // Send email notification
            try {
                if (!empty($landlord->email)) {
                    Mail::raw($message, function ($mail) use ($landlord) {
                        $mail->to($landlord->email)
                            ->subject('DormDash - Tenant Payment Submitted');
                    });
                }
            } catch (\Exception $mailError) {
                Log::error('Landlord payment email failed: ' . $mailError->getMessage());
            }
        }


        return response()->json([
            'message' => 'Payment submitted successfully!',
            'payment' => $payment
        ], 201);


    } catch (ValidationException $e) {
        return response()->json([
            'message' => 'Validation failed.',
            'errors' => $e->errors()
        ], 422);


    } catch (\Exception $e) {
        Log::error('Room payment submission failed: ' . $e->getMessage());


        return response()->json([
            'message' => 'Something went wrong while submitting payment.',
            'error' => $e->getMessage()
        ], 500);
    }
}


 
public function cancelbooking($bookingID)
{
    $booking = bookingModel::where('bookingID', $bookingID)->first();


    if ($booking) {
        $booking->status = 'cancelled';
        $booking->save();


        $booking = bookingModel::with(['tenant', 'room.dorm', 'room.landlord'])
            ->where('bookingID', $bookingID)
            ->first();
        $landlord = $booking->room->landlord;
        $notifications = notificationModel::create([
            'senderID' => $booking->fktenantID,
            'senderType' => 'tenant',
            'receiverID' => $landlord->landlordID,
            'receiverType' => 'landlord',
            'title' => 'Booking Cancelled',
            'message' => "A tenant has cancelled their booking for Room #{$booking->room->roomNumber}.",
            'isRead' => false,
            'readAt' => null,
        ]);
        broadcast(new \App\Events\NewNotificationEvent($notifications));
        if ($landlord) {
            $message = "DormDash: A tenant has cancelled their booking for Room #{$booking->room->roomNumber}. Please log in to your DormDash account to review the booking.";
            try {
                if (!empty($landlord->phoneNumber)) {
                    app(IprogSMSService::class)->sendSMS(
                        $landlord->phoneNumber,
                        $message
                    );
                }
            } catch (\Exception $smsError) {
                Log::error('Booking cancellation SMS failed: ' . $smsError->getMessage());
            }
            try {
                if (!empty($landlord->email)) {
                    Mail::raw($message, function ($mail) use ($landlord) {
                        $mail->to($landlord->email)
                            ->subject('DormDash - Booking Cancellation');
                    });
                }
            } catch (\Exception $mailError) {
                Log::error('Booking cancellation email failed: ' . $mailError->getMessage());
            }
        }


        return response()->json([
            'message' => 'Booking cancelled successfully',
            'booking' => $booking
        ]);
    }


    return response()->json([
        'message' => 'Booking not found'
    ], 404);
}




}



