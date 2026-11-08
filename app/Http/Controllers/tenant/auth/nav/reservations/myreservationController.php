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
    // Ipakita ang reservation page sa tenant.
    public function viewReservation($tenant_id)
    {
        // Kuhaon ang tenant ID nga naka-login sa session.
        $sessionTenant_id = session('tenant_id');

        // Kuhaon ang pinakabag-ong lima ka notifications sa tenant.
        $notifications = notificationModel::where('receiverID', $sessionTenant_id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Ihap ang wala pa mabasa nga notifications.
        $unreadCount = notificationModel::where('receiverID', $tenant_id)
            ->where('isRead', false)
            ->count();

        // Ibalik sa login page kung walay naka-login nga tenant.
        if (!$sessionTenant_id) {
            return redirect()->route('tenant-login')->with('error', 'Please log in as a landlord.');
        }

        // Siguraduhon nga ang tenant ID sa URL parehas sa naka-login nga tenant.
        if ($tenant_id !== $sessionTenant_id) {
            return redirect()->route('tenant-login')->with('error', 'Unauthorized access.');
        }

        // Pangitaon ang tenant record sa database.
        $tenant = tenantModel::find($tenant_id);

        // Ibalik sa login page kung walay nakit-an nga tenant.
        if (!$tenant) {
            return redirect()->route('tenant-login')->with('error', 'Landlord not found.');
        }

        // Title sa reservation page.
        $title = 'Tenant room Details - DormDash';

        // I-load ang reservation view uban sa tenant ug notification data.
        return view('tenant.auth.nav.reservations.reservation', [
            'title' => 'Reservations',
            'tenant_id' => $tenant_id,
            'cssPath' => asset('css/tenantpage/auth/roomdetails.css'),
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    // Kuhaon ang tanang reservation sa tenant.
    public function myReservationList($tenant_id)
    {
        // Kuhaon ang reservation records uban sa related tenant, room, ug dorm information.
        $reservations = reservationModel::with(['tenant', 'room.dorm'])
            ->where('fktenantID', $tenant_id)
            ->orderBy('updated_at', 'desc')
            ->get();

        // Susihon ang matag reservation kung lapas na ba sa expiration period.
        foreach ($reservations as $reservation) {

            // Kung molapas na og tulo ka adlaw sukad gihimo ang reservation.
            if (Carbon::parse($reservation->created_at)->diffInDays(now()) > 3) {

                // Kung pending o confirmed pa ang status, himoon na kining expired.
                if ($reservation->status === 'pending' || $reservation->status === 'confirmed') {
                    $reservation->status = 'expired';
                    $reservation->save();
                }
            }
        }

        // Ibalik ang reservation list isip JSON response.
        return response()->json($reservations);
    }

    // Kuhaon ang kompleto nga detalye sa usa ka reservation.
    public function reservationDetails($reservationID)
    {
        // Pangitaon ang reservation gamit ang ID ug i-load ang related records.
        $reservation = reservationModel::with([
            'tenant',
            'room.dorm',
            'room.landlord',

            // Kuhaon ang current tenant sa room nga active ang status.
            'room.currentTenant' => function ($query) {
                $query->where('status', 'active');
            }

        ])->find($reservationID);

        // Ibalik ang reservation details isip JSON response.
        return response()->json([
            'status' => 'success',
            'data' => $reservation
        ]);
    }

    // I-cancel ang reservation sa tenant.
    public function cancelReservation($reservationID)
    {
        // Pangitaon ang reservation uban sa tenant, dorm, ug landlord information.
        $reservation = reservationModel::with([
            'tenant',
            'room.dorm',
            'room.landlord'
        ])->where('reservationID', $reservationID)->first();

        // Ibalik ang 404 response kung walay nakit-an nga reservation.
        if (!$reservation) {
            return response()->json([
                'message' => 'Reservation not found'
            ], 404);
        }

        // Kuhaon ang landlord ug tenant nga konektado sa reservation.
        $landlord = $reservation->room->landlord ?? null;
        $tenant = $reservation->tenant;

        // Usbon ang reservation status ngadto sa cancelled.
        $reservation->status = 'cancelled';
        $reservation->save();

        // Padayon sa notification process kung naa ang landlord ug tenant.
        if ($landlord && $tenant) {

            // Mensahe nga ipahibalo sa landlord bahin sa cancellation.
            $notificationMessage = "A tenant has cancelled their reservation for Room #{$reservation->room->roomNumber}.";

            // Maghimo og in-app notification para sa landlord.
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

            // I-broadcast ang notification aron makita dayon sa landlord.
            broadcast(new \App\Events\NewNotificationEvent($notifications));

            // Mensahe nga gamiton para sa SMS ug email notification.
            $message = "DormDash: {$notificationMessage} Please login to your account to view the update.";

            // Ipadala ang SMS notification ngadto sa landlord.
            try {
                app(IprogSMSService::class)->sendSMS(
                    $landlord->phoneNumber,
                    $message
                );
            } catch (\Throwable $e) {
                // I-save sa log ang error kung mapakyas ang SMS sending.
                Log::error('Reservation cancellation SMS failed: ' . $e->getMessage());
            }

            // Ipadala ang email notification ngadto sa landlord.
            try {
                Mail::raw($message, function ($mail) use ($landlord) {
                    $mail->to($landlord->email)
                        ->subject('DormDash - Reservation Cancelled');
                });
            } catch (\Throwable $e) {
                // I-save sa log ang error kung mapakyas ang email sending.
                Log::error('Reservation cancellation email failed: ' . $e->getMessage());
            }
        }

        // Ibalik ang success message ug ang updated reservation information.
        return response()->json([
            'message' => 'Reservation cancelled successfully',
            'reservation' => $reservation
        ]);
    }

    // I-process ang payment sa tenant para sa iyang reservation.
    public function paymentReservation(Request $request, $reservationID)
    {
        try {

            // I-validate ang mga impormasyon nga gipadala sa payment form.
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

            // Pangitaon ang reservation record gamit ang reservation ID.
            $reservation = reservationModel::findOrFail($reservationID);

            // Default value sa payment image URL.
            $mainImageUrl = null;

            // Susihon kung adunay gi-upload nga payment screenshot.
            if ($request->hasFile('paymentImage')) {

                // Kuhaon ang uploaded payment image.
                $image1 = $request->file('paymentImage');

                // Maghimo og unique filename gamit ang current timestamp.
                $image1Name = time() . '_1.' . $image1->getClientOriginalExtension();

                // I-save ang payment image sulod sa public storage folder.
                $image1->storeAs('public/uploads/roomImages', $image1Name);

                // Kuhaon ang accessible URL sa gi-upload nga payment image.
                $mainImageUrl = asset('storage/uploads/roomImages/' . $image1Name);
            }

            // I-update ang move-in date sa reservation.
            $reservation->update([
                'moveInDate' => $request->moveInDate
            ]);

            // Maghimo og bag-ong payment record para sa reservation.
            $reservationPayment = reservationpaymentModel::create([
                'reservationID' => $reservationID,
                'paymentType' => $request->paymentType,
                'paymentImage' => $mainImageUrl,
                'amount' => $request->amount,
                'status' => 'pending'
            ]);

            // I-load pag-usab ang reservation uban sa related tenant, dorm, ug landlord.
            $reservation = reservationModel::with([
                'tenant',
                'room.dorm',
                'room.landlord'
            ])->where('reservationID', $reservationID)->first();

            // Ibalik ang error response kung wala nay nakit-an nga reservation.
            if (!$reservation) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Reservation not found.'
                ], 404);
            }

            // Kuhaon ang landlord nga tag-iya sa room.
            $landlord = $reservation->room->landlord ?? null;

            // Magpadala og notification kung naa ang landlord.
            if ($landlord) {

                // Mensahe nga ipahibalo sa landlord nga adunay tenant nga nagbayad.
                $notificationMessage = "A tenant has paid for Room #{$reservation->room->roomNumber}. Please review the payment.";

                // Maghimo og in-app notification para sa landlord.
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

                // I-broadcast ang notification aron makita dayon sa landlord.
                broadcast(new \App\Events\NewNotificationEvent($notifications));

                // Mensahe para sa SMS ug email nga naglakip sa room number ug payment amount.
                $message = "DormDash: A tenant has submitted payment for Reservation Room #{$reservation->room->roomNumber}. Amount: PHP " . number_format($request->amount, 2) . ". Please login to review and confirm.";

                // Ipadala ang SMS notification ngadto sa landlord.
                try {
                    app(IprogSMSService::class)->sendSMS(
                        $landlord->phoneNumber,
                        $message
                    );
                } catch (\Throwable $e) {
                    // I-save sa log ang error kung mapakyas ang SMS sending.
                    Log::error('Reservation payment SMS failed: ' . $e->getMessage());
                }

                // Ipadala ang email notification ngadto sa landlord.
                try {
                    Mail::raw($message, function ($mail) use ($landlord) {
                        $mail->to($landlord->email)
                            ->subject('DormDash - Reservation Payment Submitted');
                    });
                } catch (\Throwable $e) {
                    // I-save sa log ang error kung mapakyas ang email sending.
                    Log::error('Reservation payment email failed: ' . $e->getMessage());
                }
            }

            // Usbon ang reservation status ngadto sa paid.
            if ($reservation) {
                $reservation->status = 'paid';
                $reservation->save();
            }

            // Ibalik ang success response ug payment information.
            return response()->json([
                'status' => 'success',
                'message' => 'Payment successfully. Waiting for landlord confirmation.',
                'data' => $reservationPayment
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {

            // Ibalik ang validation errors kung naay invalid o kulang nga input.
            return response()->json([
                'status' => 'validation_error',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {

            // Ibalik ang error response kung adunay problema sa reservation payment process.
            return response()->json([
                'status' => 'error',
                'message' => 'Reservation failed.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
