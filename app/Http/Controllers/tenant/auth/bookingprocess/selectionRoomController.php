<?php
namespace App\Http\Controllers\tenant\auth\bookingprocess;
use App\Http\Controllers\Controller;
use App\Models\landlord\roomModel;
use App\Models\landlord\dormModel;
use App\Models\tenant\reservationModel;
use App\Models\notificationModel;
use App\Mail\TenantLandlordReminder;
use App\Events\NewNotificationEvent;

use Illuminate\Support\Facades\Mail;

use Illuminate\Http\Request;

class selectionRoomController extends Controller
{
    // Ipakita ang room selection page ug kuhaa ang notifications sa tenant.
    public function SelectionRoom($dormitoryID, $tenantID)
    {
        // Kuhaa ang tenant ID nga naka-store sa current session.
        $sessionTenant_id = session('tenant_id');

        // Kuhaa ang pinakabag-o nga lima ka notifications sa tenant.
        $notifications = notificationModel::where('receiverID', $sessionTenant_id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Ihap ang wala pa mabasang notifications sa tenant.
        $unreadCount = notificationModel::where('receiverID', $tenantID)
            ->where('isRead', false)
            ->count();

        // I-load ang room selection page uban sa dormitory ug tenant information.
        return view('tenant.auth.bookingProcess.roomSelection', [
            'title' => 'Room Selection',
            'cssPath' => asset('css/tenantpage/auth/roomselect.css'),
            'dormitory_id' => $dormitoryID,
            'tenant_id' => $tenantID,
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    // Kuhaa ang available rooms sulod sa gipiling dormitory.
    public function availableRooms($dormitoryID)
    {
        // Pangitaa ang mga room nga available ug kuhaa ang resulta.
        $rooms = roomModel::where('fkdormID', $dormitoryID)
            ->where('availability', 'Available')
            ->orWhere('created_at', 'asc')
            ->get();

        // Ibalik ang listahan sa rooms isip JSON response.
        return response()->json(['rooms' => $rooms]);
    }

    // Kuhaa ang occupied rooms nga pwede pa ma-reserve.
    public function occupiedRooms($dormitoryID)
    {
        // Kuhaa ang rooms uban sa pinakabag-o nga approved tenant information.
        $rooms = roomModel::with('latestApprovedTenant')
            ->where('fkdormID', $dormitoryID)
            ->where('availability', 'Occupied')
            ->where('isReservable', 1)
            ->get()

            // I-filter ang rooms base sa reservation status sa ilang tenant.
            ->filter(function ($room) {

                // Ayaw ipakita ang room kung ang latest approved tenant adunay pending status.
                if ($room->latestApprovedTenant && $room->latestApprovedTenant->status === 'pending') {
                    return false;
                }

                // Ipakita ang occupied rooms nga wala ma-block tungod sa pending reservation.
                return true; // show only rooms occupied but not reserved
            })

            // I-reset ang array indexes human sa filtering.
            ->values();

        // Ibalik ang filtered list sa occupied rooms.
        return response()->json(['rooms' => $rooms]);
    }

    // I-filter ang rooms base sa minimum ug maximum nga presyo.
    public function selectedPriceRange(Request $request, $dormitoryID)
    {
        // Kuhaa ang minimum ug maximum price gikan sa query parameters.
        $min = $request->query('min');
        $max = $request->query('max');

        // Kuhaa ang availability status nga gipili sa tenant.
        $chooseStatus = $request->query('chooseStatus');

        // Pangitaa ang rooms nga sakop sa dormitory, status, ug price range.
        $rooms = roomModel::where('fkdormID', $dormitoryID)
            ->where('availability', $chooseStatus)
            ->whereBetween('price', [$min, $max])
            ->get();

        // Ibalik ang mga rooms nga nakapasar sa price filter.
        return response()->json(['rooms' => $rooms]);
    }

    // I-filter ang rooms base sa gender preference.
    public function filterGender(Request $request, $dormitoryID)
    {
        // Kuhaa ang gender ug availability status nga gipili sa tenant.
        $gender = $request->query('gender');
        $chooseStatus = $request->query('chooseStatus');

        // Pangitaa ang rooms nga motakdo sa dormitory, status, ug gender preference.
        $rooms = roomModel::where('fkdormID', $dormitoryID)
            ->where('availability', $chooseStatus)
            ->where('genderPreference', $gender)
            ->get();

        // Ibalik ang listahan sa rooms nga nakapasar sa gender filter.
        return response()->json(['rooms' => $rooms]);
    }

    // Kuhaa ang kompleto nga detalye sa usa ka room.
    public function viewRoomDetails($id)
    {
        // Kuhaa ang room uban sa latest approved tenant ug dorm information.
        $room = roomModel::with(['latestApprovedTenant', 'dorm'])
            ->where('roomID', $id)
            ->first();

        // Ibalik ang room details isip JSON response.
        return response()->json([
            'status' => 'success',
            'room' => $room,
        ]);
    }

    // Proseso sa pag-reserve sa room sa tenant.
    public function reservation(Request $request)
    {
        try {

            // I-validate ang impormasyon nga gisubmit sa reservation form.
            // Gisiguro nga ang dormitory, room, ug tenant ID naa sa database.
            $validatedData = $request->validate([
                'dormitory_id' => 'required|integer|exists:dorms,dormID',
                'room_id' => 'required|integer|exists:rooms,roomID',
                'tenant_id' => 'required|string|exists:tenants,tenantID',
                'firstname' => 'required|string|max:255',
                'lastname' => 'required|string|max:255',
                'contact_number' => 'required|string|max:20',
                'email' => 'required|email|max:255',
                'age' => 'required|integer|min:15|max:60',
                'gender' => 'required|in:Male,Female',
                'studentpicture_id' => 'required|string',
            ]);

            // 1. Kuhaa ang room uban sa landlord ug current tenant information.
            $room = roomModel::with(['landlord', 'currentTenant'])->find($request->room_id);

            // Hunongon ang proseso kung wala makita ang gipiling room.
            if (!$room) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Room not found.'
                ], 404);
            }

            // Kuhaa ang landlord nga tag-iya sa gipiling room.
            $landlord = $room->landlord;

            // 2. Susiha ang extension decision sa tanang active tenants sa room.
            $tenants = $room->currentTenant()->where('status', 'active')->get();

            // I-check matag tenant kung adunay extension request nga makapugong sa reservation.
            foreach ($tenants as $tenant) {

                // Dili tugotan ang reservation kung nakadesisyon ang tenant nga mag-extend.
                if ($tenant->extension_decision === 'extend') {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'One of the tenants has decided to extend their stay. Reservation is not allowed at the moment.',
                    ]);
                }

                // Dili usab tugotan ang reservation kung pending pa ang extension request.
                if ($tenant->extension_decision === 'pending') {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'A tenant’s extension request is still pending. Reservation is not allowed at the moment. Please message the landlord for request.'
                    ]);
                }
            }

            // 3. Kung walay tenant nga makapugong sa reservation, i-save ang reservation.
            $reservation = reservationModel::create([
                'fkdormitoryID' => $request->dormitory_id,
                'fkroomID' => $request->room_id,
                'fktenantID' => $request->tenant_id,
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'contactNumber' => $request->contact_number,
                'contactEmail' => $request->email,
                'age' => $request->age,
                'gender' => $request->gender,
                'pictureID' => $request->studentpicture_id,
                'status' => 'pending',
            ]);

            // 4. Magpadala og email ngadto sa tenant isip kumpirmasyon nga na-submit ang reservation.
            try {
                // Himoa ang tenant name ug reservation confirmation message.
                $tenantName = $request->firstname . ' ' . $request->lastname;
                $tenantMessage = "Hi $tenantName, your room reservation has been submitted successfully and is currently pending confirmation.";

                // Ipadala ang email gamit ang TenantLandlordReminder mail class.
                Mail::to($request->email)->send(new TenantLandlordReminder($tenantName, $tenantMessage, 'tenant'));

            } catch (\Exception $mailException) {
                // I-record ang email error kung mapakyas ang pagpadala.
                // Magpadayon gihapon ang reservation process.
            }

            // 5. Paghimo og notification para mahibaw-an sa landlord ang bag-ong reservation.
            $notifications = notificationModel::create([
                'senderID' => $request->tenant_id,
                'senderType' => 'tenant',
                'receiverID' => $landlord->landlordID ?? null,
                'receiverType' => 'landlord',
                'title' => 'New Reservation Request',
                'message' => "A tenant has reserved Room #{$room->roomNumber}. Please review the request.",
                'isRead' => false,
                'readAt' => null,
            ]);

            // I-broadcast ang notification aron ma-notify dayon ang landlord pinaagi sa real-time event.
            broadcast(new \App\Events\NewNotificationEvent($notifications));

            // Ibalik ang success response ug reservation details ngadto sa frontend.
            return response()->json([
                'status' => 'success',
                'message' => 'Room reserved successfully. Waiting for landlord confirmation.',
                'data' => $reservation
            ]);

        } catch (\Exception $e) {

            // I-record ang error details kung mapakyas ang reservation process.

            // Ibalik ang error response ngadto sa frontend.
            return response()->json([
                'status' => 'error',
                'message' => 'Reservation failed.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

