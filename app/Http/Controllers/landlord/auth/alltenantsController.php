<?php

namespace App\Http\Controllers\landlord\auth;
use App\Services\IprogSMSService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\landlord\landlordModel;
use App\Models\tenant\approvetenantsModel;
use App\Models\landlord\roomModel;
use App\Models\landlord\dormModel;
use App\Models\notificationModel;
use App\Mail\TenantLandlordReminder;
use Illuminate\Support\Facades\Mail; // <-- add this
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;


use Illuminate\Support\Facades\Validator;

class alltenantsController extends Controller
{
     public function alltenantIndex($landlord_id)
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
    
        return view('landlord.auth.tenantpage',[
        "title" => "Landlord - Tenants List", 
        'headerName' => 'Tenants List',           
        'landlord' => $landlord,
        'landlord_id' => $landlord_id,
        'notifications' => $notifications,
        'unread_count' => $unreadCount,
    ]); 
}
 public function tenantsList(Request $request)
    {
        $landlordId = session('landlord_id');
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }
        $tenant = approvetenantsModel::with(['room.dorm', 'room.landlord'])
        ->where('status','<>', 'pending')
        ->where('isDeleted', false)
        ->whereHas('room', function ($query) use ($landlordId) {
            $query->where('fklandlordID', $landlordId);
        })
        ->orderBy('created_at', 'desc')
        ->get();
    
        return response()->json([
            'status' => 'success',
            'tenant' => $tenant,
            'landlord_id' => $landlordId
        ]);
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

    $tenant = approvetenantsModel::with([
        'room.dorm'
    ])
    ->where('approvedID', $id)
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
public function updateTenantInformation(Request $request, $id)
{
    $landlordId = session('landlord_id');

    if (!$landlordId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized action. Please log in as a landlord.'
        ], 403);
    }

    $tenant = approvetenantsModel::with('room.dorm')->find($id);

    if (!$tenant || !$tenant->room || $tenant->room->fklandlordID !== $landlordId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Tenant not found or unauthorized access.'
        ], 404);
    }

    $validator = Validator::make($request->all(), [
        'firstname'     => ['required', 'string', 'max:255', 'regex:/^[A-Za-z]+$/'],
        'lastname'      => ['required', 'string', 'max:255', 'regex:/^[A-Za-z]+$/'],
        'gender'        => 'required|in:Male,Female,Other',
        'age'           => 'required|integer|min:15|max:120',
        'contactEmail'  => 'required|email|max:255',
        'contactNumber' => 'required|digits_between:10,15',
        'status'        => 'required|string|max:50'
    ], [
        'firstname.regex' => 'The first name may only contain letters.',
        'lastname.regex'  => 'The last name may only contain letters.',
        'age.min' => 'The tenant must be at least 15 years old.',
        'contactNumber.digits_between' => 'The contact number must be between 10 and 15 digits and contain numbers only.'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed.',
            'errors' => $validator->errors()
        ], 422);
    }

    $tenant->firstname = $request->input('firstname');
    $tenant->lastname = $request->input('lastname');
    $tenant->gender = $request->input('gender');
    $tenant->age = $request->input('age');
    $tenant->contactEmail = $request->input('contactEmail');
    $tenant->contactNumber = $request->input('contactNumber');

    $notification = null;

    if ($request->input('status') === 'active') {

        $tenant->status = 'active';

        $notification = notificationModel::create([
            'senderID'     => $landlordId,
            'senderType'   => 'landlord',
            'receiverID'   => $tenant->fktenantID,
            'receiverType' => 'tenant',
            'title'        => 'Status Notification',
            'message'      => 'Hello ' . $tenant->firstname .
                ', your landlord is notifying you about your status update to ' .
                $request->input('status') . ' for room ' .
                $tenant->room->roomNumber . ' at ' .
                $tenant->room->dorm->dormName .
                '. Kindly check and confirm.',
            'isRead'       => false,
        ]);

    } else if ($request->input('status') === 'transferring') {

        $tenant->status = 'transferring';

        $notification = notificationModel::create([
            'senderID'     => $landlordId,
            'senderType'   => 'landlord',
            'receiverID'   => $tenant->fktenantID,
            'receiverType' => 'tenant',
            'title'        => 'Status Notification',
            'message'      => 'Hello ' . $tenant->firstname .
                ', your landlord is notifying you about your status update to ' .
                $request->input('status') . ' for room ' .
                $tenant->room->roomNumber . ' at ' .
                $tenant->room->dorm->dormName .
                '. Kindly check and confirm.',
            'isRead'       => false,
        ]);

    } else if ($request->input('status') === 'pending_moveout') {

        $tenant->status = 'pending_moveout';

        $notification = notificationModel::create([
            'senderID'     => $landlordId,
            'senderType'   => 'landlord',
            'receiverID'   => $tenant->fktenantID,
            'receiverType' => 'tenant',
            'title'        => 'Status Notification',
            'message'      => 'Hello ' . $tenant->firstname .
                ', your landlord is notifying you about your status update to ' .
                $request->input('status') . ' for room ' .
                $tenant->room->roomNumber . ' at ' .
                $tenant->room->dorm->dormName .
                '. Kindly check and confirm.',
            'isRead'       => false,
        ]);

    } else if ($request->input('status') === 'moved_out') {

        $tenant->status = 'moved_out';

        if ($tenant->source_type === 'Reservation') {

            // Count other tenants in the same room
            $otherTenants = approvetenantsModel::where('fkroomID', $tenant->fkroomID)
                ->where('approvedID', '!=', $tenant->approvedID)
                ->whereIn('status', ['active', 'pending'])
                ->count();

            if ($otherTenants === 0) {
                $tenant->room->availability = 'Available';
            } else {
                $tenant->room->availability = 'Occupied';
            }

            $tenant->extension_payment_status = 'pending';
            $tenant->extension_decision = 'not_extending';
            $tenant->notifyRent = 0;
            $tenant->moveOutDate = Carbon::now();
            $tenant->extensionDate = null;

            $tenant->room->save();
        }

        $notification = notificationModel::create([
            'senderID'     => $landlordId,
            'senderType'   => 'landlord',
            'receiverID'   => $tenant->fktenantID,
            'receiverType' => 'tenant',
            'title'        => 'Status Notification',
            'message'      => 'Hello ' . $tenant->firstname .
                ', your landlord is notifying you about your status update to ' .
                $request->input('status') . ' for room ' .
                $tenant->room->roomNumber . ' at ' .
                $tenant->room->dorm->dormName .
                '. Kindly check and confirm.',
            'isRead'       => false,
        ]);
    }

    $tenant->save();

    // SEND IN-APP NOTIFICATION, SMS AND EMAIL
    if ($notification) {

        // Broadcast real-time notification
        broadcast(new \App\Events\NewNotificationEvent($notification));

        $type = 'tenant';
        $message = $notification->message;

        // SEND EMAIL - Existing reminder template
        try {
            if (!empty($tenant->contactEmail)) {
                Mail::to($tenant->contactEmail)->send(
                    new TenantLandlordReminder(
                        $tenant->firstname,
                        $message,
                        $type
                    )
                );
            }
        } catch (\Throwable $mailError) {
            Log::error('Tenant status email failed: ' . $mailError->getMessage());
        }

        // SEND SMS
        try {
            if (!empty($tenant->contactNumber)) {
                app(IprogSMSService::class)->sendSMS(
                    $tenant->contactNumber,
                    'DormDash: ' . $message
                );
            }
        } catch (\Throwable $smsError) {
            Log::error('iProG tenant status SMS failed: ' . $smsError->getMessage());
        }
    }

    return response()->json([
        'status' => 'success',
        'message' => 'Tenant information updated successfully.',
        'tenant' => $tenant
    ]);
}

public function getDorms()
{
    $landlordId = session('landlord_id');

    if (!$landlordId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized action. Please log in as a landlord.'
        ], 403);
    }
    $dorms = dormModel::with('rooms')->where('fklandlordID', $landlordId)->get();
    if ($dorms->isEmpty()) {
        return response()->json([
            'status' => 'error',
            'message' => 'No dorms found for this landlord.'
        ], 404);
    }

  
    return response()->json([
        'status' => 'success',
        'dorms' => $dorms
    ]);
}
public function getRoomTypes(Request $request)
{
    $roomNumber = $request->input('roomNumber');
    $dormId = $request->input('dormID'); // optional

    $query = roomModel::query()
        ->where('roomNumber', $roomNumber);

    if ($dormId) {
        $query->where('fkdormID', $dormId);
    }
    $roomTypes = $query->pluck('roomType');


    return response()->json([
        'status' => 'success',
        'roomTypes' => $roomTypes
    ]);
}
public function getRooms(Request $request)
{
    $dormId = $request->input('dormID');

$rooms = roomModel::where('fkdormID', $dormId)
    ->get();

    return response()->json([
        'status' => 'success',
        'data' => $rooms
    ]);
}
public function getRoomsDetails(Request $request)
{
    $roomID = $request->input('roomID');

    $rooms = roomModel::where('roomID', $roomID)->get();

    return response()->json([
        'status' => 'success',
        'data' => $rooms
    ]);
}
public function roomUpdate(Request $request,$roomID)
{
    $request->validate([
        'current_room_id' => 'required|integer|exists:rooms,roomID',
        'tenant_id' => 'required|integer|exists:approved_tenants,approvedID'
    ]);
     $currentRoom = roomModel::findOrFail($request->current_room_id);
    $currentRoom->availability = 'Available';
    $currentRoom->save();

    $newRoom = roomModel::findOrFail($roomID);
    $newRoom->availability = 'Occupied';
    $newRoom->save();
    if ($newRoom->availability === 'Occupied') {
        return response()->json([
            'status'  => 'error',
            'message' => 'The selected room is already occupied.'
        ]);
    }

    $updateTenant = approvetenantsModel::findOrFail($request->tenant_id);
    $updateTenant->fkroomID = $roomID;
    $updateTenant->save();

    return response()->json([
        'status' => 'success',
        'message' => 'Room updated successfully',
        'current_room' => $currentRoom,
        'new_room' => $newRoom
    ]);
}
public function searchTenants(Request $request)
{
    $landlordId = session('landlord_id');
    if (!$landlordId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized action.'
        ], 403);
    }

    $searchTerm = trim($request->input('query', ''));

    $tenants = approvetenantsModel::with('room.dorm')
        ->whereHas('room', function ($query) use ($landlordId) {
            $query->where('fklandlordID', $landlordId);
        })
        ->when($searchTerm !== '', function ($query) use ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('firstname', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('lastname', 'LIKE', "%{$searchTerm}%");
            });
        })
        ->where('status','<>','pending')
        ->orderBy('updated_at', 'desc')
        ->get();

    return response()->json([
        'status' => 'success',
        'tenants' => $tenants
    ]);
}

public function filterByDorm(Request $request)
{
    $landlordId = session('landlord_id');
    $dormId = $request->input('dorm_id');

    if (!$landlordId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized action.'
        ], 403);
    }

    $query = approvetenantsModel::with('room.dorm')->whereHas('room', function ($q) use ($landlordId, $dormId) {
        $q->where('fklandlordID', $landlordId);
        if ($dormId) {
            $q->where('fkdormID', $dormId);
        }
        
    })    ->where('approved_tenants.status', '<>', 'pending'); // ✅ correct table
;

    $tenants = $query->get();

    return response()->json([
        'status' => 'success',
        'tenants' => $tenants
    ]);
}

public function notifyTenant(Request $request)
{
    try {
        // 1. Validate input
        $request->validate([
            'landlordID' => 'required|string',
            'approveID'  => 'required|integer',
        ]);

        // 2. Find tenant with room + dorm
        $tenant = approvetenantsModel::with('room.dorm')
            ->findOrFail($request->approveID);

        // 3. Update notify flag
        $tenant->notifyRent = 1;
        $tenant->extension_payment_status = 'pending';
        $tenant->save();

        // 4. Create notification
        $notification = notificationModel::create([
            'senderID'     => $request->landlordID,
            'senderType'   => 'landlord',
            'receiverID'   => $tenant->fktenantID,
            'receiverType' => 'tenant',
            'title'        => 'Extension Notification',
            'message'      => 'Hello ' . $tenant->firstname .
                ', your landlord is notifying you about your rental extension for room ' .
                $tenant->room->roomNumber . ' at ' . $tenant->room->dorm->dormName .
                '. Kindly check and confirm.',
            'isRead'       => false,
        ]);

        // 5. Fire event for broadcasting (real-time notif)
        broadcast(new \App\Events\NewNotificationEvent($notification));

        $type = 'tenant';

        $message = "Hi {$tenant->firstname} {$tenant->lastname}, 👋 This is a notification from your landlord regarding your stay at Room {$tenant->room->roomNumber} in {$tenant->room->dorm->dormName}. 📝 Your current booking has an extension request.";

        $message .= "\n\nExtension Details:";
        $message .= "\n- Move-out Date: {$tenant->moveOutDate}";
        $message .= "\n\nPlease contact your landlord if you have any questions or concerns. Thank you!";

        // 6. SEND EMAIL - Existing email template preserved
        try {
            if (!empty($tenant->contactEmail)) {
                Mail::to($tenant->contactEmail)->send(
                    new TenantLandlordReminder(
                        $tenant->firstname,
                        $message,
                        $type
                    )
                );
            }
        } catch (\Throwable $mailError) {
            Log::error('Tenant extension email failed: ' . $mailError->getMessage());
        }

        // 7. SEND SMS
        try {
            if (!empty($tenant->contactNumber)) {
                app(IprogSMSService::class)->sendSMS(
                    $tenant->contactNumber,
                    'DormDash: ' . $message
                );
            }
        } catch (\Throwable $smsError) {
            Log::error('iProG extension SMS failed: ' . $smsError->getMessage());
        }

        // 8. Return success response
        return response()->json([
            'status'  => 'success',
            'message' => 'Notification sent to tenant successfully',
            'data'    => $notification
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {

        // Validation errors
        return response()->json([
            'status'  => 'error',
            'message' => 'Validation failed',
            'errors'  => $e->errors()
        ], 422);

    } catch (\Exception $e) {

        // Any other errors
        return response()->json([
            'status'  => 'error',
            'message' => 'Something went wrong while sending notification',
            'error'   => $e->getMessage()
        ], 500);
    }
}

public function addMoveInTenant()
{
    $landlordId = session('landlord_id');

    if (!$landlordId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized action. Please log in as a landlord.'
        ], 403);
    }

    $tenant = approvetenantsModel::with(['room.dorm', 'room.landlord'])
        ->where('status', 'pending')
        ->whereHas('room', function ($query) use ($landlordId) {
            $query->where('fklandlordID', $landlordId);
        })
        ->orderBy('created_at', 'desc')
        ->paginate(1); // 👈 per page items (e.g., 5 per page)

    return response()->json([
        'status' => 'success',
        'moveInTenant' => $tenant,
        'landlord_id' => $landlordId
    ]);
}
public function searchMoveInTenant(Request $request)
{
    $landlordId = session('landlord_id');
    $searchTerm = $request->input('searchMoveIn');

    if (!$landlordId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized action.'
        ], 403);
    }

 $tenants = approvetenantsModel::with('room.dorm')
    ->whereHas('room', function ($q) use ($landlordId) {
        $q->where('fklandlordID', $landlordId);
    })
    ->where('status', 'pending') // ✅ Only pending tenants
    ->when($searchTerm, function ($q) use ($searchTerm) {
        $q->where(function ($sub) use ($searchTerm) {
            $sub->where('firstname', 'like', "%{$searchTerm}%")
                ->orWhere('lastname', 'like', "%{$searchTerm}%")
                ->orWhere('contactEmail', 'like', "%{$searchTerm}%");
        });
    })
    ->paginate(1);


    return response()->json([
        'status' => 'success',
        'tenants' => $tenants
    ]);
}

public function moveInTenant(Request $request)
{
    $landlordId = session('landlord_id');

    $request->validate([
        'approvedID' => 'required|integer',
    ]);

    try {
        $approvedID = $request->input('approvedID');

        // Find the tenant record
        $tenant = approvetenantsModel::with('room.dorm', 'room.landlord')
            ->findOrFail($approvedID);

        // Check if the room already has an active tenant
        $hasActiveTenant = approvetenantsModel::where('fkroomID', $tenant->fkroomID)
            ->where('status', 'active')
            ->exists();

        if ($hasActiveTenant) {
            return response()->json([
                'status'  => 'error',
                'message' => 'This room already has an active tenant. Cannot move in another tenant.',
            ], 200);
        }

        // Activate tenant since no other active tenant exists
        $tenant->status = 'active';

        if ($tenant->room) {
            $tenant->room->availability = 'Occupied';
            $tenant->room->save();
        }

        $tenant->save();

        // Create notification
        $notification = notificationModel::create([
            'senderID'     => $landlordId,
            'senderType'   => 'landlord',
            'receiverID'   => $tenant->fktenantID,
            'receiverType' => 'tenant',
            'title'        => 'Move IN Notification',
            'message'      => 'Hello ' . $tenant->firstname .
                ', your landlord is notifying you about your move-in for room ' .
                $tenant->room->roomNumber . ' at ' . $tenant->room->dorm->dormName .
                '. Kindly check and confirm.',
            'isRead'       => false,
        ]);

        $type = 'tenant';

        $message = "Hi {$tenant->firstname} {$tenant->lastname}, 👋 Your booking for Room {$tenant->room->roomNumber} at {$tenant->room->dorm->dormName} is now active ✅. You can move in starting from {$tenant->moveInDate}. Enjoy your stay!";

        // SEND EMAIL - Existing email template preserved
        try {
            if (!empty($tenant->contactEmail)) {
                Mail::to($tenant->contactEmail)->send(
                    new TenantLandlordReminder(
                        $tenant->firstname,
                        $message,
                        $type
                    )
                );
            }
        } catch (\Throwable $mailError) {
            Log::error('Tenant move-in email failed: ' . $mailError->getMessage());
        }

        // SEND SMS
        try {
            if (!empty($tenant->contactNumber)) {
                app(IprogSMSService::class)->sendSMS(
                    $tenant->contactNumber,
                    'DormDash: ' . $message
                );
            }
        } catch (\Throwable $smsError) {
            Log::error('iProG move-in SMS failed: ' . $smsError->getMessage());
        }

        // Fire event for broadcasting
        broadcast(new \App\Events\NewNotificationEvent($notification));

        return response()->json([
            'status'  => 'success',
            'message' => 'Tenant moved in successfully',
            'data'    => $tenant
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {

        return response()->json([
            'status'  => 'error',
            'message' => 'Validation failed',
            'errors'  => $e->errors()
        ], 422);

    } catch (\Exception $e) {

        return response()->json([
            'status'  => 'error',
            'message' => 'Something went wrong while moving in tenant',
            'error'   => $e->getMessage()
        ], 500);
    }
}


public function viewTenantPayment($id)
{
    $landlordId = session('landlord_id');
    if (!$landlordId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized action. Please log in as a landlord.'
        ], 403);
    }

    $tenant = approvetenantsModel::with([
        'room.dorm',
        'payments' => function ($query) {
            $query->latest('created_at')->take(1); // latest payment only
        }
    ])
    ->where('approvedID', $id)
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

public function updateTenantExtension(Request $request, $id)
{
    $landlordId = session('landlord_id');

    if (!$landlordId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized action. Please log in as a landlord.'
        ], 403);
    }

    $request->validate([
        'extensionDate' => 'required|date|after:today',
    ]);

    try {
        $extensionDate = $request->input('extensionDate');

        // Find the tenant
        $tenant = approvetenantsModel::with('room.dorm', 'room.landlord', 'payments')
            ->findOrFail($id);

        // Check if the tenant belongs to the logged-in landlord
        if ($tenant->room->fklandlordID !== $landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. This tenant does not belong to you.'
            ], 403);
        }

        $extensionDate = Carbon::parse($request->extensionDate);

        // Update extension decision
        $tenant->extension_decision = 'pending';
        $tenant->notifyRent = 0;
        $tenant->extensionDate = $extensionDate;
        $tenant->moveOutDate = $extensionDate->copy()->addMonth();
        $tenant->paymentOption = 'onsite';
        $tenant->extension_payment_status = 'done';
        $tenant->save();

        $tenant->payments()->update([
            'fkapprovedID' => $id,
            'status' => 'approved',
            'paymentType' => 'onsite',
            'amount' => $tenant->room->price
        ]);

        // Create notification
        $notification = notificationModel::create([
            'senderID'     => $landlordId,
            'senderType'   => 'landlord',
            'receiverID'   => $tenant->fktenantID,
            'receiverType' => 'tenant',
            'title'        => 'Rent Extension Approved',
            'message'      => 'Hello ' . $tenant->firstname .
                ', your landlord has approved your payment for room ' .
                $tenant->room->roomNumber . ' at ' . $tenant->room->dorm->dormName .
                '. Thank you for your prompt payment.',
            'isRead'       => false,
        ]);

        // Fire event for broadcasting (real-time notif)
        broadcast(new \App\Events\NewNotificationEvent($notification));

        // SEND SMS
        try {
            if (!empty($tenant->contactNumber)) {
                app(IprogSMSService::class)->sendSMS(
                    $tenant->contactNumber,
                    'DormDash: ' . $notification->message
                );
            }
        } catch (\Throwable $smsError) {
            Log::error('iProG SMS failed: ' . $smsError->getMessage());
        }

        // SEND EMAIL
        try {
            if (!empty($tenant->email)) {
                Mail::raw($notification->message, function ($message) use ($tenant) {
                    $message->to($tenant->email)
                        ->subject('Rent Extension Approved');
                });
            }
        } catch (\Throwable $mailError) {
            Log::error('Tenant email failed: ' . $mailError->getMessage());
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Tenant payment approved successfully',
            'data'    => $tenant
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {

        // Validation errors
        return response()->json([
            'status'  => 'error',
            'message' => 'Validation failed',
            'errors'  => $e->errors()
        ], 422);

    } catch (\Exception $e) {

        // Any other errors
        return response()->json([
            'status'  => 'error',
            'message' => 'Something went wrong while approving tenant payment',
            'error'   => $e->getMessage()
        ], 500);
    }
}


public function updateTenantPaymentExtension(Request $request, $id)
{
    $landlordId = session('landlord_id');

    if (!$landlordId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized action. Please log in as a landlord.'
        ], 403);
    }

    $request->validate([
        'status' => 'required|in:approved,rejected',
    ]);

    try {
        $status = $request->input('status');
        $paymentID = $request->input('paymentID');
        $extensionDate = $request->input('extensionDate');

        $tenant = approvetenantsModel::with('room.dorm', 'room.landlord', 'payments')
            ->findOrFail($id);

        if ($tenant->room->fklandlordID !== $landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. This tenant does not belong to you.'
            ], 403);
        }

        if ($status === 'approved') {

            $tenant->extension_decision = 'pending';
            $tenant->notifyRent = 0;
            $tenant->extension_payment_status = 'done';
            $tenant->extensionDate = Carbon::parse($extensionDate);
            $tenant->moveOutDate = Carbon::parse($extensionDate)->addMonth();
            $tenant->save();

            $tenant->payments()
                ->where('paymentID', $paymentID)
                ->update(['status' => $status]);

            $notification = notificationModel::create([
                'senderID'     => $landlordId,
                'senderType'   => 'landlord',
                'receiverID'   => $tenant->fktenantID,
                'receiverType' => 'tenant',
                'title'        => 'Rent Extension Approved',
                'message'      => 'Hello ' . $tenant->firstname .
                    ', your landlord has approved your payment for room ' .
                    $tenant->room->roomNumber . ' at ' . $tenant->room->dorm->dormName .
                    '. Thank you for your prompt payment.',
                'isRead'       => false,
            ]);

            broadcast(new \App\Events\NewNotificationEvent($notification));

            $emailSubject = 'Rent Extension Payment Approved';
            $emailMessage = $notification->message;

        } else if ($status === 'rejected') {

            $tenant->notifyRent = 0;
            $tenant->extension_payment_status = 'pending';
            $tenant->save();

            $tenant->payments()
                ->where('paymentID', $paymentID)
                ->update(['status' => 'rejected']);

            $notification = notificationModel::create([
                'senderID'     => $landlordId,
                'senderType'   => 'landlord',
                'receiverID'   => $tenant->fktenantID,
                'receiverType' => 'tenant',
                'title'        => 'Rent Extension Rejected',
                'message'      => 'Hello ' . $tenant->firstname .
                    ', your landlord has rejected your payment for room ' .
                    $tenant->room->roomNumber . ' at ' . $tenant->room->dorm->dormName .
                    '. Please contact your landlord for more details.',
                'isRead'       => false,
            ]);

            // Fire event for broadcasting (real-time notif)
            broadcast(new \App\Events\NewNotificationEvent($notification));

            $emailSubject = 'Rent Extension Payment Rejected';
            $emailMessage = $notification->message;
        }

        // SEND SMS
        try {
            if (!empty($tenant->contactNumber)) {
                app(IprogSMSService::class)->sendSMS(
                    $tenant->contactNumber,
                    'DormDash: ' . $notification->message
                );
            }
        } catch (\Throwable $smsError) {
            Log::error('iProG SMS failed: ' . $smsError->getMessage());
        }

        // SEND EMAIL
        try {
            if (!empty($tenant->email)) {
                Mail::raw($emailMessage, function ($message) use ($tenant, $emailSubject) {
                    $message->to($tenant->email)
                        ->subject($emailSubject);
                });
            }
        } catch (\Throwable $mailError) {
            Log::error('Tenant email failed: ' . $mailError->getMessage());
        }

        return response()->json([
            'status'  => 'success',
            'message' => $status === 'approved'
                ? 'Tenant payment approved successfully'
                : 'Tenant payment rejected successfully',
            'data'    => $tenant
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {

        return response()->json([
            'status'  => 'error',
            'message' => 'Validation failed',
            'errors'  => $e->errors()
        ], 422);

    } catch (\Exception $e) {

        return response()->json([
            'status'  => 'error',
            'message' => 'Something went wrong while approving tenant payment',
            'error'   => $e->getMessage()
        ], 500);
    }
}

public function softDelete(Request $request, $id)
{
    $landlordId = session('landlord_id');
    if (!$landlordId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized action. Please log in as a landlord.'
        ], 403);
    }
    try {

        // Find the tenant record
        $tenant = approvetenantsModel::findOrFail($id);
        $tenant->isDeleted = true;
        $tenant->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Tenant record temporarily deleted successfully',
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Validation failed',
            'errors'  => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Something went wrong while deleting tenant record',
            'error'   => $e->getMessage()
        ], 500);
}
}
public function generateActiveTenantReport(Request $request)
{
    $landlordId = session('landlord_id');
    if (!$landlordId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized action. Please log in as a landlord.'
        ], 403);
    }

    $logoPath = public_path('images/Logo/logo.png');
    $date = $request->query('date'); // optional date filter YYYY-MM-DD

   $tenants = approvetenantsModel::with('room.dorm')
    ->whereHas('room', function ($query) use ($landlordId) {
        $query->where('fklandlordID', $landlordId);
    })
    ->where('status', 'active')
    ->where('isDeleted', false)
    ->when(!empty($date), function ($query) use ($date) {
        $query->whereYear('moveInDate', date('Y', strtotime($date)))
              ->whereMonth('moveInDate', date('m', strtotime($date)));
    })
    ->orderBy('moveInDate', 'desc')
    ->get();


    $totalTenants = $tenants->count();

    $pdf = Pdf::loadView('landlord.reports.active-tenant-report', [
        'tenants' => $tenants,
        'logoPath' => $logoPath,
        'totalTenants' => $totalTenants,
        'selectedDate' => $date
    ]);

    return $pdf->stream("landlord.reports.active-tenant-report-{$landlordId}.pdf");
}
public function generateExtensionPaymentReport(Request $request)
{
    $landlordId = session('landlord_id');
    if (!$landlordId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized action. Please log in as a landlord.'
        ], 403);
    }

    $logoPath = public_path('images/Logo/logo.png');

    $date = $request->query('date'); // optional date

    $tenantsWithExtensions = approvetenantsModel::with(['room.dorm', 'payments'])
        ->whereHas('room', fn($q) => $q->where('fklandlordID', $landlordId))
        ->where('extension_payment_status', 'done')
        ->where('isDeleted', false)
        ->when($date, function ($query) use ($date) {
            // Filter by month and year
            $query->whereHas('payments', fn($q) => $q->whereYear('created_at', date('Y', strtotime($date)))
                                                        ->whereMonth('created_at', date('m', strtotime($date))));
        })
        ->orderBy('created_at', 'desc')
        ->get();

    // Calculate total extension payments
    $totalIncome = $tenantsWithExtensions->sum(function($tenant) {
        return $tenant->payments->sum('amount');
    });

    $pdf = Pdf::loadView('landlord.reports.extension-payment-report', [
        'tenants' => $tenantsWithExtensions,
        'logoPath'=> $logoPath,
        'totalIncome' => $totalIncome
    ]);

    return $pdf->stream("landlord-extension-payment-report-{$landlordId}.pdf");
}


}
