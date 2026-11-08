<?php

namespace App\Http\Controllers\landlord\auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\landlord\landlordModel;
use App\Models\landlord\bookingModel;
use App\Models\landlord\roomModel;
use App\Models\landlord\dormModel;
use App\Models\tenant\bookingpaymentModel;
use App\Models\tenant\approvetenantsModel;
use App\Models\tenant\approvepaymentModel;
use App\Models\notificationModel;
use App\Mail\TenantLandlordReminder;
use App\Services\IprogSMSService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class bookingpageController extends Controller
{
    // Ipakita ang booking approval page sa landlord.
    public function bookingpageIndex($landlord_id)
    {
        // Kuhaon ang landlord ID gikan sa active session.
        $sessionLandlordId = session('landlord_id');

        // Siguraduhon nga naka-login ang landlord.
        if (!$sessionLandlordId) {
            return redirect()->route('loginLandlord')
                ->with('error', 'Please log in as a landlord.');
        }

        // Kuhaon ang pinakabag-ong lima ka notifications sa landlord.
        $notifications = notificationModel::where('receiverID', $sessionLandlordId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Ihap-on ang wala pa mabasang notifications.
        $unreadCount = notificationModel::where('receiverID', $landlord_id)
            ->where('isRead', false)
            ->count();

        // Siguraduhon nga ang gi-access nga landlord ID iya sa naka-login nga landlord.
        if ($landlord_id !== $sessionLandlordId) {
            return redirect()->route('loginLandlord')
                ->with('error', 'Unauthorized access.');
        }

        // Pangitaon ang landlord record sa database.
        $landlord = landlordModel::find($landlord_id);

        // Ibalik sa login page kung walay nakit-an nga landlord.
        if (!$landlord) {
            return redirect()->route('loginLandlord')
                ->with('error', 'Landlord not found.');
        }

        // I-load ang booking page uban sa landlord ug notification data.
        return view('landlord.auth.bookingpage', [
            "title" => "Landlord - Booking Approval",
            'headerName' => 'Tenants Booking',
            'landlord' => $landlord,
            'landlord_id' => $landlord_id,
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    // Pangitaon ang tenant bookings base sa search input.
    public function searchBooking(Request $request)
    {
        $landlordId = session('landlord_id');

        // Dili tugotan ang search kung walay naka-login nga landlord.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }

        // Kuhaon ang search keyword gikan sa request.
        $searchTerm = $request->input('search');

        // Kuhaon ang bookings nga konektado lamang sa rooms sa naka-login nga landlord.
        // I-load usab ang related room, dorm, landlord ug tenant information.
        $query = bookingModel::with(['room.dorm', 'room.landlord', 'tenant'])
            ->whereHas('room', function ($q) use ($landlordId) {
                $q->where('fklandlordID', $landlordId);
            })
            ->where('isDeleted', false);

        // Kung naay search keyword, pangitaon ang tenant pinaagi sa pangalan o email.
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('firstname', 'like', '%' . $searchTerm . '%')
                    ->orWhere('lastname', 'like', '%' . $searchTerm . '%')
                    ->orWhere('contactEmail', 'like', '%' . $searchTerm . '%');
            });
        }

        // I-sort ang resulta ug ipakita ang lima ka records matag page.
        $bookings = $query->orderBy('updated_at', 'asc')->paginate(5);

        // Ibalik ang booking search results isip JSON response.
        return response()->json([
            'status' => 'success',
            'booking' => $bookings
        ]);
    }

    // Kuhaon ang listahan sa tanang active bookings sa landlord.
    public function bookingList()
    {
        $landlordId = session('landlord_id');

        // Siguraduhon nga naka-login ang landlord.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }

        // Kuhaon ang bookings ug apil ang related room, dorm, tenant ug payment details.
        $booking = bookingModel::with([
            'room.dorm',
            'room.landlord',
            'tenant',
            'payment'
        ])
            ->whereHas('room', function ($query) use ($landlordId) {
                // I-filter ang bookings base sa landlord nga tag-iya sa room.
                $query->where('fklandlordID', $landlordId);
            })
            ->orderBy('updated_at', 'desc')
            ->where('isDeleted', false)
            ->paginate(6);

        // Ibalik ang booking list ug landlord ID ngadto sa frontend.
        return response()->json([
            'status' => 'success',
            'booking' => $booking,
            'landlord_id' => $landlordId
        ]);
    }

    // Kuhaon ang listahan sa tanang dorm names.
    public function getDormName()
    {
        $dorms = dormModel::all();

        // Ibalik ang dorm records isip JSON.
        return response()->json($dorms);
    }

    // Kuhaon ang mga tenant bookings nga sakop sa gipiling dorm.
    public function getTenantsByDorm($dormId)
    {
        // I-filter ang bookings base sa dorm ID pinaagi sa room relationship.
        $tenants = bookingModel::whereHas('room.dorm', function ($query) use ($dormId) {
            $query->where('dormID', $dormId);
        })->with(['room.dorm']);

        // Ibalik ang resulta nga adunay unom ka records matag page.
        return response()->json($tenants->paginate(6));
    }

    // Kuhaon ug i-filter ang bookings base sa room number.
    public function getRooms(Request $request)
    {
        $landlordId = session('landlord_id');

        // Kuhaon ang room number nga gipangita sa user.
        $searchTerm = $request->input('roomsNumber');

        // Kuhaon ang bookings nga sakop sa rooms sa naka-login nga landlord.
        $query = bookingModel::with([
            'room.dorm',
            'room.landlord',
            'tenant',
            'payment'
        ])
            ->whereHas('room', function ($q) use ($landlordId) {
                $q->where('fklandlordID', $landlordId);
            })
            ->where('isDeleted', false)
            ->orderBy('updated_at', 'desc');

        // Kung naay room number nga gipangita, i-filter ang booking list.
        if ($searchTerm) {
            $query->whereHas('room', function ($q) use ($searchTerm) {
                $q->where('roomNumber', '=', $searchTerm);
            });
        }

        // Ibalik ang filtered bookings nga adunay pagination.
        return response()->json($query->paginate(6));
    }

    // Kuhaon ang booking applications base sa gipiling application status.
    public function getApplications(Request $request)
    {
        $landlordId = session('landlord_id');

        // Kuhaon ang status filter gikan sa frontend.
        $searchStatus = $request->input('selectedapplicationStatus');

        // Kuhaon ang bookings sa landlord uban sa ilang related information.
        $query = bookingModel::with([
            'room.dorm',
            'room.landlord',
            'tenant',
            'payment'
        ])
            ->whereHas('room', function ($q) use ($landlordId) {
                $q->where('fklandlordID', $landlordId);
            })
            ->where('isDeleted', false)
            ->orderBy('updated_at', 'desc');

        // Kung dili "All" ang gipiling status, i-filter ang bookings base niini.
        if ($searchStatus && strtolower($searchStatus) !== 'all') {
            $query->where('status', '=', strtolower($searchStatus));
        }

        // Ibalik ang application results nga adunay unom ka records matag page.
        return response()->json($query->paginate(6));
    }

    // Kuhaon ang kompletong impormasyon sa usa ka tenant booking.
    public function ViewTenant($id)
    {
        $landlordId = session('landlord_id');

        // Siguraduhon nga naka-login ang landlord.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }

        // Pangitaon ang booking gamit ang booking ID.
        // Siguraduhon usab nga ang room iya sa naka-login nga landlord.
        $tenant = bookingModel::with([
            'room.dorm',
            'payment'
        ])
            ->where('bookingID', $id)
            ->whereHas('room', function ($query) use ($landlordId) {
                $query->where('fklandlordID', $landlordId);
            })
            ->first();

        // Ibalik ang error kung walay booking record nga nakit-an.
        if (!$tenant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tenant screening record not found.'
            ], 404);
        }

        // Ibalik ang tenant booking details ngadto sa frontend.
        return response()->json([
            'status' => 'success',
            'tenant' => $tenant
        ]);
    }

    // Pagdumala sa booking status sa tenant sama sa approve, reject ug confirm.
    public function handletenantBooking(Request $request)
    {
        // I-validate ang booking ID, landlord ID ug allowed booking status.
        $request->validate([
            'bookingID' => 'required|integer|exists:bookings,bookingID',
            'landlordID' => 'required|string',
            'status' => 'required|string|in:approved,rejected,confirmed,paid,pending,cancelled'
        ]);

        $landlordId = session('landlord_id');

        // Siguraduhon nga ang request landlord ID parehas sa naka-login nga landlord.
        if (!$landlordId || $request->landlordID != $landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action.'
            ], 403);
        }

        // Pangitaon ang booking record base sa gipasa nga booking ID.
        $booking = bookingModel::findOrFail($request->bookingID);

        // Kuhaon ang room details ug siguruhon nga iya kini sa landlord.
        $room = roomModel::with(['dorm', 'landlord'])
            ->where('roomID', $booking->fkroomID)
            ->where('fklandlordID', $landlordId)
            ->first();

        // Ibalik ang error kung walay room nga nakit-an o dili kini sakop sa landlord.
        if (!$room) {
            return response()->json([
                'status' => 'error',
                'message' => 'Booking room not found or unauthorized.'
            ], 404);
        }

        // Kuhaon ang payment record nga konektado sa booking.
        $payment = bookingpaymentModel::where(
            'fkbookingID',
            $request->bookingID
        )->first();

        try {
            // I-combine ang first name ug last name sa tenant.
            $tenantName = trim(
                $booking->firstname . ' ' . $booking->lastname
            );

            // Kuhaon ang dorm name ug room number.
            // Gamiton ang default value kung walay available nga data.
            $dormName = $room->dorm->dormName ?? 'Unknown Dorm';
            $roomNumber = $room->roomNumber ?? 'N/A';

            // Mga variable para sa notification ug message sa tenant.
            $message = null;
            $notifications = null;
            $type = 'tenant';

            // I-check kung unsa nga booking status ang gipili sa landlord.
            switch ($request->status) {

                case 'pending':

                    // Mensahe kung pending pa ang booking ug naghulat sa confirmation.
                    $message = "Hi {$tenantName}, your booking for Room {$roomNumber} at {$dormName} is pending landlord confirmation. Please wait.";

                    // Maghimo og notification para sa tenant.
                    $notifications = notificationModel::create([
                        'senderID' => $landlordId,
                        'senderType' => 'landlord',
                        'receiverID' => $booking->fktenantID,
                        'receiverType' => 'tenant',
                        'title' => 'Booking Update: Pending',
                        'message' => $message,
                        'isRead' => false,
                    ]);

                    break;

                case 'confirmed':

                    // Mensahe kung gi-confirm na sa landlord ang booking.
                    $message = "Hi {$tenantName}, your booking for Room {$roomNumber} at {$dormName} has been confirmed. Please proceed with payment.";

                    // Maghimo og notification nga confirmed na ang booking.
                    $notifications = notificationModel::create([
                        'senderID' => $landlordId,
                        'senderType' => 'landlord',
                        'receiverID' => $booking->fktenantID,
                        'receiverType' => 'tenant',
                        'title' => 'Booking Update: Confirmed',
                        'message' => $message,
                        'isRead' => false,
                    ]);

                    break;

                case 'approved':

                    // Siguraduhon nga dili pa occupied ang room sa dili pa mo-approve.
                    if ($room->availability === 'Occupied') {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'This room is already occupied. You cannot approve another tenant.',
                        ], 403);
                    }

                    // I-save ang tenant information sa approved tenants table.
                    // Pending pa ang status niini alang sa sunod nga proseso.
                    approvetenantsModel::create([
                        'fktenantID' => $booking->fktenantID,
                        'fkroomID' => $booking->fkroomID,
                        'source_type' => 'Booking',
                        'source_id' => $booking->bookingID,
                        'firstname' => $booking->firstname,
                        'lastname' => $booking->lastname,
                        'contactNumber' => $booking->contactNumber,
                        'moveInDate' => $booking->moveInDate,
                        'moveOutDate' => $booking->moveOutDate,
                        'contactEmail' => $booking->contactEmail,
                        'age' => $booking->age,
                        'gender' => $booking->gender,
                        'status' => 'pending',
                        'pictureID' => $booking->pictureID,
                    ]);

                    // Mensahe nga nagpasabot nga gi-approve ang booking.
                    $message = "Hi {$tenantName}, your booking for Room {$roomNumber} at {$dormName} has been approved.";

                    // Maghimo og approval notification para sa tenant.
                    $notifications = notificationModel::create([
                        'senderID' => $landlordId,
                        'senderType' => 'landlord',
                        'receiverID' => $booking->fktenantID,
                        'receiverType' => 'tenant',
                        'title' => 'Booking Update: Approved',
                        'message' => $message,
                        'isRead' => false,
                    ]);

                    // Usbon ang room availability ngadto sa Reserved.
                    $room->availability = 'Reserved';
                    $room->updated_at = now();
                    $room->save();

                    break;

                case 'rejected':

                    // Mensahe kung gi-decline sa landlord ang booking.
                    $message = "Hi {$tenantName}, your booking for Room {$roomNumber} at {$dormName} has been declined.";

                    // Maghimo og rejection notification para sa tenant.
                    $notifications = notificationModel::create([
                        'senderID' => $landlordId,
                        'senderType' => 'landlord',
                        'receiverID' => $booking->fktenantID,
                        'receiverType' => 'tenant',
                        'title' => 'Booking Update: Rejected',
                        'message' => $message,
                        'isRead' => false,
                    ]);

                    break;
            }

            // I-update ang booking status ug oras sa katapusang pag-usab.
            $booking->status = $request->status;
            $booking->updated_at = now();
            $booking->save();

            // I-broadcast ang notification aron ma-update ang notification display sa frontend.
            if ($notifications) {
                broadcast(
                    new \App\Events\NewNotificationEvent($notifications)
                );
            }

            // Ipadala ang email notification ngadto sa tenant.
            // Kung mapakyas ang email, i-record ang error sa Laravel log.
            if ($message) {
                try {
                    Mail::to($booking->contactEmail)->send(
                        new TenantLandlordReminder(
                            $tenantName,
                            $message,
                            $type
                        )
                    );
                } catch (\Throwable $e) {
                    Log::error('Booking email failed: ' . $e->getMessage());
                }
            }

            // Ipadala ang SMS notification gamit ang iProG SMS service.
            try {
                // Maghimo og SMS message depende sa booking status.
                $smsMessage = match ($request->status) {

                    'approved' =>
                        "DormDash: Hi {$tenantName}! Your booking for Room {$roomNumber} at {$dormName} has been approved.",

                    'rejected' =>
                        "DormDash: Hi {$tenantName}! Your booking for Room {$roomNumber} at {$dormName} has been rejected.",

                    'confirmed' =>
                        "DormDash: Hi {$tenantName}! Your booking for Room {$roomNumber} at {$dormName} is confirmed. Please proceed with payment.",

                    default => null,
                };

                // Ipadala lamang ang SMS kung adunay message ug contact number ang tenant.
                if ($smsMessage && !empty($booking->contactNumber)) {

                    $smsSent = app(IprogSMSService::class)->sendSMS(
                        $booking->contactNumber,
                        $smsMessage
                    );

                    // I-record sa log kung napakyas ang pagpadala sa SMS.
                    if (!$smsSent) {
                        Log::warning('iProG SMS was not sent.', [
                            'bookingID' => $booking->bookingID
                        ]);
                    }
                }

            } catch (\Throwable $e) {
                // I-record ang SMS error aron dali ma-troubleshoot.
                Log::error('Tenant iProG SMS failed: ' . $e->getMessage(), [
                    'bookingID' => $booking->bookingID
                ]);
            }

            // Ibalik ang success response kung nahuman ang booking update.
            return response()->json([
                'status' => 'success',
                'message' => "Booking status updated to {$request->status}.",
            ]);

        } catch (\Throwable $e) {

            // I-record ang error kung napakyas ang booking update process.
            Log::error('Booking update failed: ' . $e->getMessage());

            // Ibalik ang error response ngadto sa frontend.
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update booking.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Temporary nga pag-delete sa tenant booking record.
    public function deleteBooking($id)
    {
        try {
            // Pangitaon ang booking record base sa booking ID.
            $booking = bookingModel::where('bookingID', $id)->first();

            // Ibalik ang error kung walay booking record nga nakit-an.
            if (!$booking) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tenant Booking record not found.'
                ], 404);
            }

            // I-set ang isDeleted ngadto sa true imbes nga permanenteng tangtangon ang record.
            $booking->isDeleted = true;
            $booking->save();

            // Ibalik ang success response human ma-temporary delete ang booking.
            return response()->json([
                'status' => 'success',
                'message' => 'Tenant Booking record temporary deleted successfully.'
            ]);

        } catch (\Exception $e) {

            // Ibalik ang error details kung naay problema sa pag-delete.
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the tenant screening record.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
