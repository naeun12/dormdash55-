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
    // Ipakita ang booking details page sa tenant.
    public function viewBookingDetails($tenant_id, $booking_id)
    {
        // Kuhaa ang tenant ID nga naka-store sa current session.
        $sessionTenant_id = session('tenant_id');

        // Kuhaa ang pinakabag-o nga lima ka notifications sa tenant.
        $notifications = notificationModel::where('receiverID', $sessionTenant_id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Ihap ang wala pa mabasang notifications sa tenant.
        $unreadCount = notificationModel::where('receiverID', $tenant_id)
            ->where('isRead', false)
            ->count();

        // Susiha kung adunay naka-login nga tenant.
        // Kung walay session, i-redirect siya ngadto sa login page.
        if (!$sessionTenant_id) {
            return redirect()->route('tenant-login')->with('error', 'Please log in as a landlord.');
        }

        // Siguraduhon nga ang tenant ID sa URL parehas sa naka-login nga tenant.
        // Kung dili parehas, dili tugotan ang pag-access.
        if ($tenant_id !== $sessionTenant_id) {
            return redirect()->route('tenant-login')->with('error', 'Unauthorized access.');
        }

        // Pangitaa ang tenant information base sa iyang ID.
        $tenant = tenantModel::find($tenant_id);

        // I-redirect ang user kung walay makita nga tenant record.
        if (!$tenant) {
            return redirect()->route('tenant-login')->with('error', 'Landlord not found.');
        }

        // Title nga gamiton sa booking details page.
        $title = 'Tenant room Details - DormDash';

        // I-load ang booking details page uban sa tenant, booking, ug notification information.
        return view('tenant.auth.nav.bookings.mybookingdetails', [
            'title' => 'Tenant - Booking Details',
            'tenant_id' => $tenant_id,
            'booking_id' => $booking_id,
            'cssPath' => asset('css/tenantpage/auth/roomdetails.css'),
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    // Kuhaa ang detalye sa booking base sa booking ID.
    public function bookingDetails($booking_id)
    {
        // Kuhaa ang booking uban sa konektadong tenant, room, ug dorm information.
        $bookings = bookingModel::with(['tenant', 'room.dorm'])
            ->where('bookingID', $booking_id)
            ->get();

        // Ibalik ang booking details isip JSON response.
        return response()->json($bookings);
    }

    // Proseso sa pag-submit sa tenant sa iyang room payment.
    public function payRoom(Request $request)
    {
        try {
            // Step 1: I-validate ang payment information nga gisubmit sa tenant.
            $validated = $request->validate([
                'fkbookingID' => 'required|exists:bookings,bookingID',
                'paymentType' => 'required|string',
                'paymentImage' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'amount' => 'required|numeric|min:0'
            ], [
                // Custom error messages kung naay invalid o kulang nga payment information.
                'fkbookingID.required' => 'Booking ID is required.',
                'fkbookingID.exists' => 'Booking ID does not exist.',
                'paymentType.required' => 'Please select a payment type.',
                'paymentType.string' => 'Payment type must be a valid string.',
                'paymentImage.image' => 'The file must be an image.',
                'paymentImage.mimes' => 'The image must be a jpeg, png, or jpg.',
                'paymentImage.max' => 'The image must not be larger than 2MB.',
            ]);

            // Default value sa payment image URL kung walay gi-upload nga image.
            $mainImageUrl = null;

            // Susiha kung adunay payment image nga gi-upload sa tenant.
            if ($request->hasFile('paymentImage')) {
                // Kuhaa ang uploaded payment image.
                $image1 = $request->file('paymentImage');

                // Himoa ang unique filename gamit ang current timestamp ug file extension.
                $image1Name = time() . '_1.' . $image1->getClientOriginalExtension();

                // I-save ang payment image sulod sa public storage directory.
                $image1->storeAs('public/uploads/roomImages', $image1Name);

                // Himoa ang public URL nga gamiton sa pag-display sa payment image.
                $mainImageUrl = asset('storage/uploads/roomImages/' . $image1Name);
            }

            // I-save ang payment information sa booking payment table.
            $payment = bookingpaymentModel::create([
                'fkbookingID' => $request->fkbookingID,
                'paymentType' => $request->paymentType,
                'paymentImage' => $mainImageUrl,
                'amount' => $request->amount
            ]);

            // I-update ang booking status ngadto sa paid human ma-submit ang payment.
            bookingModel::with('tenant', 'room.dorm')
                ->where('bookingID', $request->fkbookingID)
                ->update([
                    'status' => 'paid'
                ]);

            // Kuhaa pag-usab ang booking information uban sa tenant, room, ug dorm details.
            $booking = bookingModel::with(['tenant', 'room.dorm'])
                ->where('bookingID', $request->fkbookingID)
                ->first();

            // Kuhaa ang booking uban sa landlord information pinaagi sa room relationship.
            $landlordBooking = bookingModel::with('room.landlord')
                ->find($request->fkbookingID);

            // Kuhaa ang landlord information ug iyang ID gikan sa booking.
            $landlord = optional($landlordBooking->room)->landlord;
            $landlordID = optional($landlord)->landlordID;

            // Paghimo og in-app notification aron mahibaw-an sa landlord nga naay payment submission.
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

            // I-broadcast ang notification aron ma-notify dayon ang landlord pinaagi sa real-time event.
            broadcast(new \App\Events\NewNotificationEvent($notifications));

            // Magpadala og SMS ug email notification kung adunay landlord information.
            if ($landlord) {
                // Himoa ang notification message nga naglakip sa room number ug payment amount.
                $message = "DormDash: A tenant has submitted a payment for Room #{$booking->room->roomNumber}. Amount: PHP " . number_format($payment->amount, 2) . ". Please log in to DormDash to review the payment.";

                // Sulayi pagpadala og SMS notification gamit ang iProG SMS service.
                try {
                    // Ipadala ang SMS kung adunay phone number ang landlord.
                    if (!empty($landlord->phoneNumber)) {
                        app(IprogSMSService::class)->sendSMS(
                            $landlord->phoneNumber,
                            $message
                        );
                    }
                } catch (\Exception $smsError) {
                    // I-record ang error kung mapakyas ang SMS sending.
                    Log::error('Landlord payment SMS failed: ' . $smsError->getMessage());
                }

                // Sulayi pagpadala og email notification ngadto sa landlord.
                try {
                    // Ipadala ang email kung adunay email address ang landlord.
                    if (!empty($landlord->email)) {
                        Mail::raw($message, function ($mail) use ($landlord) {
                            $mail->to($landlord->email)
                                ->subject('DormDash - Tenant Payment Submitted');
                        });
                    }
                } catch (\Exception $mailError) {
                    // I-record ang error kung mapakyas ang email sending.
                    Log::error('Landlord payment email failed: ' . $mailError->getMessage());
                }
            }

            // Ibalik ang success response ug payment information human ma-submit.
            return response()->json([
                'message' => 'Payment submitted successfully!',
                'payment' => $payment
            ], 201);

        } catch (ValidationException $e) {

            // Ibalik ang validation errors kung naay invalid o kulang nga payment input.
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {

            // I-record ang error kung mapakyas ang payment submission.
            Log::error('Room payment submission failed: ' . $e->getMessage());

            // Ibalik ang error response ngadto sa frontend.
            return response()->json([
                'message' => 'Something went wrong while submitting payment.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Proseso sa pagkansela sa booking sa tenant.
    public function cancelbooking($bookingID)
    {
        // Pangitaa ang booking record base sa booking ID.
        $booking = bookingModel::where('bookingID', $bookingID)->first();

        // Ipadayon ang cancellation kung nakit-an ang booking.
        if ($booking) {

            // Usba ang booking status ngadto sa cancelled.
            $booking->status = 'cancelled';

            // I-save ang updated booking status sa database.
            $booking->save();

            // Kuhaa pag-usab ang booking uban sa tenant, dorm, room, ug landlord information.
            $booking = bookingModel::with(['tenant', 'room.dorm', 'room.landlord'])
                ->where('bookingID', $bookingID)
                ->first();

            // Kuhaa ang landlord nga tag-iya sa room nga gi-book.
            $landlord = $booking->room->landlord;

            // Paghimo og notification aron mahibaw-an sa landlord nga gikansela ang booking.
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

            // I-broadcast ang cancellation notification para ma-update dayon ang landlord.
            broadcast(new \App\Events\NewNotificationEvent($notifications));

            // Magpadala og SMS ug email kung adunay landlord information.
            if ($landlord) {

                // Himoa ang mensahe nga pahibalo bahin sa pagkansela sa booking.
                $message = "DormDash: A tenant has cancelled their booking for Room #{$booking->room->roomNumber}. Please log in to your DormDash account to review the booking.";

                // Sulayi pagpadala og SMS notification ngadto sa landlord.
                try {
                    // Ipadala ang SMS kung adunay registered phone number ang landlord.
                    if (!empty($landlord->phoneNumber)) {
                        app(IprogSMSService::class)->sendSMS(
                            $landlord->phoneNumber,
                            $message
                        );
                    }
                } catch (\Exception $smsError) {
                    // I-record ang error kung mapakyas ang SMS sending.
                    Log::error('Booking cancellation SMS failed: ' . $smsError->getMessage());
                }

                // Sulayi pagpadala og email notification ngadto sa landlord.
                try {
                    // Ipadala ang email kung adunay email address ang landlord.
                    if (!empty($landlord->email)) {
                        Mail::raw($message, function ($mail) use ($landlord) {
                            $mail->to($landlord->email)
                                ->subject('DormDash - Booking Cancellation');
                        });
                    }
                } catch (\Exception $mailError) {
                    // I-record ang error kung mapakyas ang email sending.
                    Log::error('Booking cancellation email failed: ' . $mailError->getMessage());
                }
            }

            // Ibalik ang success response ug updated booking information.
            return response()->json([
                'message' => 'Booking cancelled successfully',
                'booking' => $booking
            ]);
        }

        // Ibalik ang 404 response kung walay booking nga makita sa database.
        return response()->json([
            'message' => 'Booking not found'
        ], 404);
    }
}

