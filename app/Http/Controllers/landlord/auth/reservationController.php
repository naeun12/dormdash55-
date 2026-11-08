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
    public function reservationIndex($landlord_id)
    {
        $sessionLandlordId = session('landlord_id');
        $notifications = notificationModel::where('receiverID', $sessionLandlordId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
            $unreadCount = notificationModel::where('receiverID', $landlord_id)
            ->where('isRead', false)
            ->count();
        if (!$sessionLandlordId) {
            return redirect()->route('loginLandlord')->with('error', 'Please log in as a landlord.');
        }
   
        if ($landlord_id !== $sessionLandlordId) {
            return redirect()->route('loginLandlord')->with('error', 'Unauthorized access.');
        }
   
        $landlord = landlordModel::find($landlord_id);
        if (!$landlord) {
            return redirect()->route('loginLandlord')->with('error', 'Landlord not found.');
        }
   
        return view('landlord.auth.reservationpage',[
        "title" => "Landlord - Reservation Approval",
        'headerName' => 'Tenants Reservation',          
        'landlord' => $landlord,
        'landlord_id' => $landlord_id,
        'notifications' => $notifications,
        'unread_count' => $unreadCount,
    ]);  
    }
        public function searchReservation(Request $request)
{
    $landlordId = session('landlord_id');


    if (!$landlordId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized action. Please log in as a landlord.'
        ], 403);
    }


    $searchTerm = $request->input('search');


    $query = reservationModel::with(['room.dorm', 'room.landlord', 'tenant'])
        ->whereHas('room', function ($q) use ($landlordId) {
            $q->where('fklandlordID', $landlordId);
        })->where('isDeleted', false);


    if ($searchTerm) {
        $query->where(function ($q) use ($searchTerm) {
            $q->where('firstname', 'like', '%' . $searchTerm . '%')
              ->orWhere('lastname', 'like', '%' . $searchTerm . '%')
              ->orWhere('contactEmail', 'like', '%' . $searchTerm . '%');
        });
    }
    $reservations = $query->orderBy('created_at', 'asc')->paginate(6);


    return response()->json([
        'status' => 'success',
        'reservation' => $reservations
    ]);
}
    public function reservationList(Request $request)
    {
        $landlordId = session('landlord_id');
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }
        $reservation = reservationModel::with(['room.dorm', 'room.landlord','tenant'])
        ->whereHas('room', function ($query) use ($landlordId) {
            $query->where('fklandlordID', $landlordId);
        })->where('isDeleted', false)
        ->orderBy('updated_at', 'desc')
        ->paginate(6);
   
        return response()->json([
            'status' => 'success',
            'reservation' => $reservation,
            'landlord_id' => $landlordId
        ]);
    }
    public function viewReservation($id)
    {
        $landlordId = session('landlord_id');
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }
   
        $reservation = reservationModel::with([
        'room.dorm',
        'tenant',
        'payment',
        'room.currentTenant' => function ($query) {
            $query->where('status', 'active');
        },
    ])
    ->where('reservationID', $id)
    ->whereHas('room', function ($query) use ($landlordId) {
        $query->where('fklandlordID', $landlordId);
    })
    ->first();




   
        if (!$reservation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tenant screening record not found.'
            ], 404);
        }


        return response()->json([
            'status' => 'success',
            'reservation' => $reservation
        ]);
    }
    public function reservationDelete($id)
{
    try {
        $reservation = reservationModel::where('reservationID', $id)->first();


        if (!$reservation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tenant reservation record not found.'
            ], 404);
        }
        $reservation->isDeleted = true;
        $reservation->save();


        return response()->json([
            'status' => 'success',
            'message' => 'Tenant reservation record deleted successfully.'
        ]);


    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'An error occurred while deleting the tenant reservation record.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
public function getDormReservations()
{
    $dorms = dormModel::all();


    return response()->json($dorms);
}
public function getTenantsByDorm($dormId)
{
    $tenants = reservationModel::whereHas('room.dorm', function ($query) use ($dormId) {
        $query->where('dormID', $dormId);
    })->with(['room.dorm'])
     ->orderBy('created_at', 'desc')->where('isDeleted', false)
      ->paginate(6);


    return response()->json($tenants);
}
public function getRoomsNumber(Request $request)
{
    $landlordId = session('landlord_id');
    $searchTerm = $request->input('roomsNumber'); // match 'roomsNumber'


    $query = reservationModel::with(['room.dorm', 'room.landlord', 'tenant'])
        ->whereHas('room', function ($q) use ($landlordId) {
            $q->where('fklandlordID', $landlordId);
        })->where('isDeleted', false);


        if ($searchTerm) {
            $query->whereHas('room', function ($q) use ($searchTerm) {
                $q->where('roomNumber', '=', $searchTerm); // use exact match
            });
        }
       


    return response()->json($query->orderBy('created_at', 'desc')->paginate(6));
}


public function getApplications(Request $request)
{
    $landlordId = session('landlord_id');
    $searchStatus = $request->input('selectedapplicationStatus');


    $query = reservationModel::with(['room.dorm', 'room.landlord', 'tenant'])
        ->whereHas('room', function ($q) use ($landlordId) {
            $q->where('fklandlordID', $landlordId);
        })->where('isDeleted', false);


    if ($searchStatus) {
        $query->where('status', '=', $searchStatus);
    }


    return response()->json($query->orderBy('created_at', 'desc')->paginate(6));
}
 public function getAllReservations()
    {
        $reservations = reservationModel::with('room.dorm')->get();


        return response()->json($reservations);
    }
public function acceptReservation(Request $request)
{
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


    $reservation = reservationModel::findOrFail($request->reservationID);


    try {
        $tenantName = $request->firstname . ' ' . $request->lastname;
        $message = null;
        $type = 'tenant';


        switch ($request->status) {


            case 'pending':
                $message = "Hi {$tenantName}, Your reservation for Room {$request->roomNumber} at {$request->dorm} is pending. Please wait for the landlord's confirmation.";


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


                $reservation->status = $request->status;
                $reservation->save();


                broadcast(new \App\Events\NewNotificationEvent($notifications));
                break;


            case 'confirmed':
                $message = "Hi {$tenantName}, Your reservation for Room {$request->roomNumber} at {$request->dorm} has been confirmed. Please proceed with the payment to secure your slot. Thank you!";


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


                $reservation->status = $request->status;
                $reservation->save();


                broadcast(new \App\Events\NewNotificationEvent($notifications));
                break;


            case 'approved':
                $moveIn = Carbon::parse($reservation->moveInDate);
                $moveOut = $moveIn->copy()->addMonth()->subDay();


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


                $message = "Hi {$tenantName}, Your reservation for Room {$request->roomNumber} at {$request->dorm} has been fully approved. Your move-in date is confirmed: {$moveIn->format('M d, Y')} until {$moveOut->format('M d, Y')}. Please prepare for your stay!";


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


                $reservation->status = $request->status;
                $reservation->save();


                broadcast(new \App\Events\NewNotificationEvent($notifications));
                break;


            case 'rejected':
                $message = "Hi {$tenantName}, We regret to inform you that your reservation for Room {$request->roomNumber} at {$request->dorm} has been declined by the landlord. You may apply for other available rooms. Thank you!";


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


                $reservation->status = $request->status;
                $reservation->save();


                broadcast(new \App\Events\NewNotificationEvent($notifications));
                break;
        }


        // Email Notification
        if ($message) {
            try {
                Mail::to($request->email)->send(
                    new TenantLandlordReminder($tenantName, $message, $type)
                );
            } catch (\Throwable $e) {
                Log::error('Reservation email notification failed: ' . $e->getMessage());
            }


            // SMS Notification
            try {
                if (!empty($reservation->contactNumber)) {
                    app(IprogSMSService::class)->sendSMS(
                        $reservation->contactNumber,
                        $message
                    );
                }
            } catch (\Throwable $e) {
                Log::error('Reservation SMS notification failed: ' . $e->getMessage());
            }
        }


        return response()->json([
            'status'  => 'success',
            'message' => "Reservation status updated to {$request->status}.",
        ]);


    } catch (\Throwable $e) {
        Log::error('Failed to update reservation: ' . $e->getMessage());


        return response()->json([
            'status'  => 'error',
            'message' => 'Failed to update reservation.',
            'error'   => $e->getMessage()
        ], 500);
    }
}






}



