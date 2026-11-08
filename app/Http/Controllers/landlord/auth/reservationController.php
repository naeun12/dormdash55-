<?php

namespace App\Http\Controllers\landlord\auth;

use App\Mail\TenantLandlordReminder;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;
use App\Services\IprogSMSService;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\landlord\landlordModel;
use App\Models\tenant\reservationModel;
use App\Models\landlord\roomModel;
use App\Models\landlord\dormModel;
use App\Models\notificationModel;
use App\Models\tenant\approvetenantsModel;
use Carbon\Carbon;

class reservationController extends Controller
{
    // I-display ang reservation approval page sa landlord.
    public function reservationIndex($landlord_id)
    {
        // Kuhaa ang landlord ID gikan sa current session.
        $sessionLandlordId = session('landlord_id');

        // Kuhaa ang pinakabag-ong lima ka notifications sa landlord.
        $notifications = notificationModel::where('receiverID', $sessionLandlordId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // I-count ang tanang unread notifications sa landlord.
        $unreadCount = notificationModel::where('receiverID', $landlord_id)
            ->where('isRead', false)
            ->count();

        // Siguraduhon nga naka-login ang landlord.
        if (!$sessionLandlordId) {
            return redirect()->route('loginLandlord')->with('error', 'Please log in as a landlord.');
        }

        // I-check kung ang gi-access nga landlord ID parehas sa session ID.
        if ($landlord_id !== $sessionLandlordId) {
            return redirect()->route('loginLandlord')->with('error', 'Unauthorized access.');
        }

        // Pangitaa ang landlord record sa database.
        $landlord = landlordModel::find($landlord_id);

        // Kung walay makita nga landlord, ibalik sa login page.
        if (!$landlord) {
            return redirect()->route('loginLandlord')->with('error', 'Landlord not found.');
        }

        // I-load ang reservation page uban sa landlord ug notification information.
        return view('landlord.auth.reservationpage', [
            "title" => "Landlord - Reservation Approval",
            'headerName' => 'Tenants Reservation',
            'landlord' => $landlord,
            'landlord_id' => $landlord_id,
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    // Mangita og tenant reservations gamit ang search input.
    public function searchReservation(Request $request)
    {
        // Kuhaa ang landlord ID gikan sa session.
        $landlordId = session('landlord_id');

        // Kung walay naka-login nga landlord, ibalik ang unauthorized response.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }

        // Kuhaa ang search keyword gikan sa request.
        $searchTerm = $request->input('search');

        // Kuhaa ang reservations nga iya sa landlord ug i-load ang related records.
        $query = reservationModel::with(['room.dorm', 'room.landlord', 'tenant'])
            ->whereHas('room', function ($q) use ($landlordId) {
                $q->where('fklandlordID', $landlordId);
            })->where('isDeleted', false);

        // Kung naay search keyword, pangitaa ang tenant pinaagi sa first name, last name, o email.
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('firstname', 'like', '%' . $searchTerm . '%')
                    ->orWhere('lastname', 'like', '%' . $searchTerm . '%')
                    ->orWhere('contactEmail', 'like', '%' . $searchTerm . '%');
            });
        }

        // I-sort ang reservations gikan sa pinakadaan ug ipakita ang unom kada page.
        $reservations = $query->orderBy('created_at', 'asc')->paginate(6);

        // Ibalik ang search results isip JSON response.
        return response()->json([
            'status' => 'success',
            'reservation' => $reservations
        ]);
    }

    // Kuhaa ang reservation list nga sakop sa naka-login nga landlord.
    public function reservationList(Request $request)
    {
        // Kuhaa ang landlord ID gikan sa session.
        $landlordId = session('landlord_id');

        // I-check kung naka-login ang landlord.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }

        // Kuhaa ang reservations sa landlord uban sa related room, dorm, ug tenant information.
        $reservation = reservationModel::with(['room.dorm', 'room.landlord', 'tenant'])
            ->whereHas('room', function ($query) use ($landlordId) {
                $query->where('fklandlordID', $landlordId);
            })->where('isDeleted', false)
            ->orderBy('updated_at', 'desc')
            ->paginate(6);

        // Ibalik ang reservation list ug landlord ID isip JSON.
        return response()->json([
            'status' => 'success',
            'reservation' => $reservation,
            'landlord_id' => $landlordId
        ]);
    }

    // Ipakita ang kompleto nga detalye sa usa ka reservation.
    public function viewReservation($id)
    {
        // Kuhaa ang landlord ID gikan sa session.
        $landlordId = session('landlord_id');

        // Kung walay landlord session, dili tugotan ang request.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }

        // Pangitaa ang reservation ug i-load ang iyang related information.
        $reservation = reservationModel::with([
            'room.dorm',
            'tenant',
            'payment',

            // Kuhaa lamang ang current tenant nga active ang status.
            'room.currentTenant' => function ($query) {
                $query->where('status', 'active');
            },
        ])
            ->where('reservationID', $id)

            // Siguraduhon nga ang reservation iya sa room sa naka-login nga landlord.
            ->whereHas('room', function ($query) use ($landlordId) {
                $query->where('fklandlordID', $landlordId);
            })
            ->first();

        // Kung walay reservation nga nakit-an, ibalik ang 404 response.
        if (!$reservation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tenant screening record not found.'
            ], 404);
        }

        // Ibalik ang reservation details isip JSON response.
        return response()->json([
            'status' => 'success',
            'reservation' => $reservation
        ]);
    }

    // I-delete ang reservation pinaagi sa pag-update sa isDeleted field.
    public function reservationDelete($id)
    {
        try {
            // Pangitaa ang reservation gamit ang reservation ID.
            $reservation = reservationModel::where('reservationID', $id)->first();

            // Kung walay reservation nga nakit-an, ibalik ang 404 response.
            if (!$reservation) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tenant reservation record not found.'
                ], 404);
            }

            // I-mark ang reservation isip deleted.
            $reservation->isDeleted = true;
            $reservation->save();

            // Ibalik ang success response human ma-update ang record.
            return response()->json([
                'status' => 'success',
                'message' => 'Tenant reservation record deleted successfully.'
            ]);

        } catch (\Exception $e) {
            // Kung naay error sa pag-delete, ibalik ang error details.
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the tenant reservation record.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Kuhaa ang tanang dorm records gikan sa database.
    public function getDormReservations()
    {
        $dorms = dormModel::all();

        // Ibalik ang dorm list isip JSON response.
        return response()->json($dorms);
    }

    // Kuhaa ang reservations sa mga tenant nga naka-apply sa specific dorm.
    public function getTenantsByDorm($dormId)
    {
        // Pangitaa ang reservations base sa dorm ID ug i-load ang room ug dorm information.
        $tenants = reservationModel::whereHas('room.dorm', function ($query) use ($dormId) {
            $query->where('dormID', $dormId);
        })->with(['room.dorm'])

            // Ipakita una ang pinakabag-ong reservation ug dili apilon ang deleted records.
            ->orderBy('created_at', 'desc')
            ->where('isDeleted', false)
            ->paginate(6);

        // Ibalik ang tenant reservations isip JSON response.
        return response()->json($tenants);
    }

    // Mangita og reservations base sa room number.
    public function getRoomsNumber(Request $request)
    {
        // Kuhaa ang landlord ID ug room number nga gipangita.
        $landlordId = session('landlord_id');
        $searchTerm = $request->input('roomsNumber'); // match 'roomsNumber'

        // Kuhaa ang reservations nga iya sa landlord ug i-load ang related records.
        $query = reservationModel::with(['room.dorm', 'room.landlord', 'tenant'])
            ->whereHas('room', function ($q) use ($landlordId) {
                $q->where('fklandlordID', $landlordId);
            })->where('isDeleted', false);

        // Kung naay room number nga gipangita, gamiton ang exact match.
        if ($searchTerm) {
            $query->whereHas('room', function ($q) use ($searchTerm) {
                $q->where('roomNumber', '=', $searchTerm); // use exact match
            });
        }

        // Ibalik ang reservations nga na-filter pinaagi sa room number.
        return response()->json($query->orderBy('created_at', 'desc')->paginate(6));
    }

    // I-filter ang reservations base sa application status.
    public function getApplications(Request $request)
    {
        // Kuhaa ang landlord ID ug application status gikan sa request.
        $landlordId = session('landlord_id');
        $searchStatus = $request->input('selectedapplicationStatus');

        // Kuhaa ang reservations nga iya sa landlord ug i-load ang related records.
        $query = reservationModel::with(['room.dorm', 'room.landlord', 'tenant'])
            ->whereHas('room', function ($q) use ($landlordId) {
                $q->where('fklandlordID', $landlordId);
            })->where('isDeleted', false);

        // Kung naay gipiling status, i-filter ang reservations base niini.
        if ($searchStatus) {
            $query->where('status', '=', $searchStatus);
        }

        // Ibalik ang filtered reservations nga pinakabag-o ang naa sa taas.
        return response()->json($query->orderBy('created_at', 'desc')->paginate(6));
    }

    // Kuhaa ang tanang reservations uban sa ilang dorm information.
    public function getAllReservations()
    {
        $reservations = reservationModel::with('room.dorm')->get();

        // Ibalik ang tanang reservation records isip JSON.
        return response()->json($reservations);
    }

    // I-update ang reservation status ug magpadala og notification sa tenant.
    public function acceptReservation(Request $request)
    {
        // I-validate ang impormasyon nga gikinahanglan sa pag-update sa reservation.
        $request->validate([
            'reservationID' => 'required|integer|exists:reservation,reservationID',
            'email'         => 'required|email',
            'roomNumber'    => 'required|string',
            'landlordID'    => 'required|string',
            'dorm'          => 'required|string',
            'firstname'     => 'required|string',
            'lastname'      => 'required|string',
            'status'        => 'required|in:pending,confirmed,approved,rejected',
        ]);

        // Pangitaa ang reservation record gamit ang reservation ID.
        $reservation = reservationModel::findOrFail($request->reservationID);

        try {
            // Paghugpong sa first name ug last name aron makuha ang kompleto nga tenant name.
            $tenantName = $request->firstname . ' ' . $request->lastname;

            // Variable para sa message nga ipadala sa tenant.
            $message = null;

            // I-set ang notification type isip tenant.
            $type = 'tenant';

            // I-check kung unsa ang bag-ong reservation status.
            switch ($request->status) {

                // Kung pending ang status, pahibal-on ang tenant nga naghulat pa sa confirmation.
                case 'pending':
                    $message = "Hi {$tenantName}, Your reservation for Room {$request->roomNumber} at {$request->dorm} is pending. Please wait for the landlord's confirmation.";

                    // Maghimo og in-app notification para sa tenant.
                    $notifications = notificationModel::create([
                        'senderID'     => $request->landlordID,
                        'senderType'   => 'landlord',
                        'receiverID'   => $reservation->fktenantID,
                        'receiverType' => 'tenant',
                        'title'        => 'Reservation Update: Pending',
                        'message'      => "Your reservation for Room #{$reservation->room->roomNumber} at {$request->dorm} is currently pending landlord confirmation. Please wait for further updates.",
                        'isRead'       => false,
                        'readAt'       => null,
                    ]);

                    // I-update ang reservation status ngadto sa pending.
                    $reservation->status = $request->status;
                    $reservation->save();

                    // I-broadcast ang bag-ong notification ngadto sa connected clients.
                    broadcast(new \App\Events\NewNotificationEvent($notifications));
                    break;

                // Kung confirmed ang status, pahibal-on ang tenant nga kinahanglan na siyang mobayad.
                case 'confirmed':
                    $message = "Hi {$tenantName}, Your reservation for Room {$request->roomNumber} at {$request->dorm} has been confirmed. Please proceed with the payment to secure your slot. Thank you!";

                    // Maghimo og in-app notification nga confirmed na ang reservation.
                    $notifications = notificationModel::create([
                        'senderID'     => $request->landlordID,
                        'senderType'   => 'landlord',
                        'receiverID'   => $reservation->fktenantID,
                        'receiverType' => 'tenant',
                        'title'        => 'Reservation Update: Confirmed',
                        'message'      => "Your reservation for Room #{$reservation->room->roomNumber} at {$request->dorm} has been confirmed. Please complete your payment to finalize your reservation.",
                        'isRead'       => false,
                        'readAt'       => null,
                    ]);

                    // I-save ang bag-ong reservation status.
                    $reservation->status = $request->status;
                    $reservation->save();

                    // I-broadcast ang confirmation notification.
                    broadcast(new \App\Events\NewNotificationEvent($notifications));
                    break;

                // Kung approved ang status, i-register ang tenant isip approved tenant.
                case 'approved':
                    // Kuhaa ang move-in date ug kwentaha ang move-out date nga usa ka bulan minus usa ka adlaw.
                    $moveIn = Carbon::parse($reservation->moveInDate);
                    $moveOut = $moveIn->copy()->addMonth()->subDay();

                    // I-save ang tenant information sa approved tenants table.
                    approvetenantsModel::create([
                        'fktenantID'    => $reservation->fktenantID,
                        'fkroomID'      => $reservation->fkroomID,
                        'firstname'     => $request->firstname,
                        'lastname'      => $request->lastname,
                        'contactNumber' => $reservation->contactNumber,
                        'contactEmail'  => $reservation->contactEmail,
                        'age'           => $reservation->age,
                        'gender'        => $reservation->gender,
                        'pictureID'     => $reservation->pictureID,
                        'source_type'   => 'Reservation',
                        'source_id'     => $reservation->reservationID,
                        'moveInDate'    => $moveIn,
                        'moveOutDate'   => $moveOut,
                        'status'        => 'pending'
                    ]);

                    // Maghimo og message nga nagpakita sa confirmed move-in ug move-out dates.
                    $message = "Hi {$tenantName}, Your reservation for Room {$request->roomNumber} at {$request->dorm} has been fully approved. Your move-in date is confirmed: {$moveIn->format('M d, Y')} until {$moveOut->format('M d, Y')}. Please prepare for your stay!";

                    // Maghimo og in-app notification nga approved na ang reservation.
                    $notifications = notificationModel::create([
                        'senderID'     => $request->landlordID,
                        'senderType'   => 'landlord',
                        'receiverID'   => $reservation->fktenantID,
                        'receiverType' => 'tenant',
                        'title'        => 'Reservation Update: Approved',
                        'message'      => "Congratulations! Your reservation for Room #{$reservation->room->roomNumber} at {$request->dorm} has been approved. Your stay is confirmed from {$moveIn->format('M d, Y')} to {$moveOut->format('M d, Y')}.",
                        'isRead'       => false,
                        'readAt'       => null,
                    ]);

                    // I-update ang reservation status ngadto sa approved.
                    $reservation->status = $request->status;
                    $reservation->save();

                    // I-broadcast ang approval notification ngadto sa tenant.
                    broadcast(new \App\Events\NewNotificationEvent($notifications));
                    break;

                // Kung rejected ang status, pahibal-on ang tenant nga gi-decline ang reservation.
                case 'rejected':
                    $message = "Hi {$tenantName}, We regret to inform you that your reservation for Room {$request->roomNumber} at {$request->dorm} has been declined by the landlord. You may apply for other available rooms. Thank you!";

                    // Maghimo og in-app notification bahin sa declined reservation.
                    $notifications = notificationModel::create([
                        'senderID'     => $request->landlordID,
                        'senderType'   => 'landlord',
                        'receiverID'   => $reservation->fktenantID,
                        'receiverType' => 'tenant',
                        'title'        => 'Reservation Update: Declined',
                        'message'      => "Your reservation for Room #{$reservation->room->roomNumber} at {$request->dorm} has been declined by the landlord.",
                        'isRead'       => false,
                        'readAt'       => null,
                    ]);

                    // I-save ang rejected status sa reservation.
                    $reservation->status = $request->status;
                    $reservation->save();

                    // I-broadcast ang rejection notification.
                    broadcast(new \App\Events\NewNotificationEvent($notifications));
                    break;
            }

            // Kung adunay message, ipadala ang email ug SMS notification sa tenant.
            if ($message) {
                // Email Notification
                try {
                    // Ipadala ang reservation update pinaagi sa email.
                    Mail::to($request->email)->send(
                        new TenantLandlordReminder($tenantName, $message, $type)
                    );
                } catch (\Throwable $e) {
                    // I-record sa Laravel log kung napakyas ang email notification.
                    Log::error('Reservation email notification failed: ' . $e->getMessage());
                }

                // SMS Notification
                try {
                    // I-check kung naa bay contact number ang tenant.
                    if (!empty($reservation->contactNumber)) {
                        // Ipadala ang reservation update pinaagi sa iProg SMS service.
                        app(IprogSMSService::class)->sendSMS(
                            $reservation->contactNumber,
                            $message
                        );
                    }
                } catch (\Throwable $e) {
                    // I-record sa Laravel log kung napakyas ang SMS notification.
                    Log::error('Reservation SMS notification failed: ' . $e->getMessage());
                }
            }

            // Ibalik ang success response human ma-update ang reservation status.
            return response()->json([
                'status'  => 'success',
                'message' => "Reservation status updated to {$request->status}.",
            ]);

        } catch (\Throwable $e) {
            // I-record ang error kung napakyas ang reservation update process.
            Log::error('Failed to update reservation: ' . $e->getMessage());

            // Ibalik ang error response uban sa error details.
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update reservation.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
