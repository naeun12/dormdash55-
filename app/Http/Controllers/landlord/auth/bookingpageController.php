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
use App\Services\PhilSMSService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
class bookingpageController extends Controller
{
    public function bookingpageIndex($landlord_id)
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
    
        return view('landlord.auth.bookingpage',[
        "title" => "Landlord - Booking Approval", 
        'headerName' => 'Tenants Booking',           
        'landlord' => $landlord,
        'landlord_id' => $landlord_id,
        'notifications' => $notifications,
        'unread_count' => $unreadCount,
    ]);   
    }
    public function searchBooking(Request $request)
{
    $landlordId = session('landlord_id');

    if (!$landlordId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized action. Please log in as a landlord.'
        ], 403);
    }

    $searchTerm = $request->input('search');

    $query = bookingModel::with(['room.dorm', 'room.landlord', 'tenant'])
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

    $bookings = $query->orderBy('updated_at', 'asc')->paginate(5);

    return response()->json([
        'status' => 'success',
        'booking' => $bookings
    ]);
}

    public function bookingList()
    {
        $landlordId = session('landlord_id');
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }
        $booking = bookingModel::with(['room.dorm', 'room.landlord', 'tenant','payment'])
        ->whereHas('room', function ($query) use ($landlordId) {
            $query->where('fklandlordID', $landlordId);
        })
        ->orderBy('updated_at', 'desc')
        ->where('isDeleted', false)
        ->paginate(6);
    
        return response()->json([
            'status' => 'success',
            'booking' => $booking,
            'landlord_id' => $landlordId
        ]);
    }
    public function getDormName()
{
    $dorms = dormModel::all();

    return response()->json($dorms); 
}
public function getTenantsByDorm($dormId)
{
    $tenants = bookingModel::whereHas('room.dorm', function ($query) use ($dormId) {
        $query->where('dormID', $dormId);
    })->with(['room.dorm']);
    return response()->json($tenants->paginate(6));
}
public function getRooms(Request $request)
{
    $landlordId = session('landlord_id');
    $searchTerm = $request->input('roomsNumber');

    $query = bookingModel::with(['room.dorm', 'room.landlord', 'tenant','payment'])
        ->whereHas('room', function ($q) use ($landlordId) {
            $q->where('fklandlordID', $landlordId);
        })
        ->where('isDeleted', false)
        ->orderBy('updated_at', 'desc');

        if ($searchTerm) {
            $query->whereHas('room', function ($q) use ($searchTerm) {
                $q->where('roomNumber', '=', $searchTerm); // use exact match
            });
        }
        

    return response()->json($query->paginate(6));
}


public function getApplications(Request $request)
{
    $landlordId = session('landlord_id');
    $searchStatus = $request->input('selectedapplicationStatus');

    $query = bookingModel::with(['room.dorm', 'room.landlord', 'tenant',
    'payment'])
        ->whereHas('room', function ($q) use ($landlordId) {
            $q->where('fklandlordID', $landlordId);
        })
         ->where('isDeleted', false)
         ->orderBy('updated_at', 'desc');

    if ($searchStatus && strtolower($searchStatus) !== 'all') {
    $query->where('status', '=', strtolower($searchStatus));
}


    return response()->json($query->paginate(6));
}
    public function ViewTenant($id)
{
    $landlordId = session('landlord_id');
    if (!$landlordId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized action. Please log in as a landlord.'
        ], 403);
    }

    $tenant = bookingModel::with([
        'room.dorm','payment'
    ])
    ->where('bookingID', $id)
    ->whereHas('room', function ($query) use ($landlordId) {
        $query->where('fklandlordID', $landlordId);
    })
    ->first();

    if (!$tenant) {
        return response()->json([
            'status' => 'error',
            'message' => 'Tenant screening record not found.'
        ], 404);
    }

    return response()->json([
        'status' => 'success',
        'tenant' => $tenant
    ]);
}


public function handletenantBooking(Request $request)
{
    $request->validate([
        'bookingID'  => 'required|integer|exists:bookings,bookingID',
        'landlordID' => 'required|string',
        'status'     => 'required|string|in:approved,rejected,confirmed,paid,pending,cancelled'
    ]);

    $landlordId = session('landlord_id');

    if (!$landlordId || $request->landlordID != $landlordId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized action.'
        ], 403);
    }

    $booking = bookingModel::findOrFail($request->bookingID);

    // Verify that this booking belongs to the logged-in landlord.
    $room = roomModel::with(['dorm', 'landlord'])
        ->where('roomID', $booking->fkroomID)
        ->where('fklandlordID', $landlordId)
        ->first();

    if (!$room) {
        return response()->json([
            'status' => 'error',
            'message' => 'Booking room not found or unauthorized.'
        ], 404);
    }

    $payment = bookingpaymentModel::where(
        'fkbookingID',
        $request->bookingID
    )->first();

    try {

        $tenantName = trim(
            $booking->firstname . ' ' . $booking->lastname
        );

        $dormName = $room->dorm->dormName ?? 'Unknown Dorm';
        $roomNumber = $room->roomNumber ?? 'N/A';

        $message = null;
        $notifications = null;
        $type = 'tenant';

        switch ($request->status) {

            case 'pending':

                $message = "Hi {$tenantName}, your booking for Room {$roomNumber} at {$dormName} is pending landlord confirmation. Please wait.";

                $notifications = notificationModel::create([
                    'senderID'     => $landlordId,
                    'senderType'   => 'landlord',
                    'receiverID'   => $booking->fktenantID,
                    'receiverType' => 'tenant',
                    'title'        => 'Booking Update: Pending',
                    'message'      => $message,
                    'isRead'       => false,
                ]);

                break;


            case 'confirmed':

                $message = "Hi {$tenantName}, your booking for Room {$roomNumber} at {$dormName} has been confirmed. Please proceed with payment.";

                $notifications = notificationModel::create([
                    'senderID'     => $landlordId,
                    'senderType'   => 'landlord',
                    'receiverID'   => $booking->fktenantID,
                    'receiverType' => 'tenant',
                    'title'        => 'Booking Update: Confirmed',
                    'message'      => $message,
                    'isRead'       => false,
                ]);

                break;


            case 'approved':

                if ($room->availability === 'Occupied') {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'This room is already occupied. You cannot approve another tenant.',
                    ], 403);
                }

                approvetenantsModel::create([
                    'fktenantID'   => $booking->fktenantID,
                    'fkroomID'     => $booking->fkroomID,
                    'source_type'  => 'Booking',
                    'source_id'    => $booking->bookingID,
                    'firstname'    => $booking->firstname,
                    'lastname'     => $booking->lastname,
                    'contactNumber'=> $booking->contactNumber,
                    'moveInDate'   => $booking->moveInDate,
                    'moveOutDate'  => $booking->moveOutDate,
                    'contactEmail' => $booking->contactEmail,
                    'age'          => $booking->age,
                    'gender'       => $booking->gender,
                    'status'       => 'pending',
                    'pictureID'    => $booking->pictureID,
                ]);

                $message = "Hi {$tenantName}, your booking for Room {$roomNumber} at {$dormName} has been approved.";

                $notifications = notificationModel::create([
                    'senderID'     => $landlordId,
                    'senderType'   => 'landlord',
                    'receiverID'   => $booking->fktenantID,
                    'receiverType' => 'tenant',
                    'title'        => 'Booking Update: Approved',
                    'message'      => $message,
                    'isRead'       => false,
                ]);

                $room->availability = 'Reserved';
                $room->updated_at = now();
                $room->save();

                break;


            case 'rejected':

                $message = "Hi {$tenantName}, your booking for Room {$roomNumber} at {$dormName} has been declined.";

                $notifications = notificationModel::create([
                    'senderID'     => $landlordId,
                    'senderType'   => 'landlord',
                    'receiverID'   => $booking->fktenantID,
                    'receiverType' => 'tenant',
                    'title'        => 'Booking Update: Rejected',
                    'message'      => $message,
                    'isRead'       => false,
                ]);

                break;
        }


        // Update booking status
        $booking->status = $request->status;
        $booking->updated_at = now();
        $booking->save();
        if ($notifications) {
            broadcast(
                new \App\Events\NewNotificationEvent($notifications)
            );
        }
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
        try {

            $smsMessage = match ($request->status) {

                'approved' =>
                    "DormDash: Hi {$tenantName}! Your booking for Room {$roomNumber} at {$dormName} has been approved.",

                'rejected' =>
                    "DormDash: Hi {$tenantName}! Your booking for Room {$roomNumber} at {$dormName} has been rejected.",

                'confirmed' =>
                    "DormDash: Hi {$tenantName}! Your booking for Room {$roomNumber} at {$dormName} is confirmed. Please proceed with payment.",

                default => null,
            };

            if ($smsMessage && !empty($booking->contactNumber)) {

                app(PhilSMSService::class)->sendSMS(
                    $booking->contactNumber,
                    $smsMessage
                );

            }

        } catch (\Throwable $e) {

            Log::error('Tenant booking SMS failed: ' . $e->getMessage());

        }


        return response()->json([
            'status'  => 'success',
            'message' => "Booking status updated to {$request->status}.",
        ]);

    } catch (\Throwable $e) {

        Log::error('Booking update failed: ' . $e->getMessage());

        return response()->json([
            'status'  => 'error',
            'message' => 'Failed to update booking.',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

public function deleteBooking($id)
{
    try {
        $booking = bookingModel::where('bookingID', $id)->first();

        if (!$booking) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tenant Booking record not found.'
            ], 404);
        }
        $booking->isDeleted = true;
        $booking->save();
        return response()->json([
            'status' => 'success',
            'message' => 'Tenant Booking record temporary deleted successfully.'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'An error occurred while deleting the tenant screening record.',
            'error' => $e->getMessage(),
        ], 500);
    }
}


}
