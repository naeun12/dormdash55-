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
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Validator;

class alltenantsController extends Controller
{
    // Ipakita ang tenants list page sa landlord.
    public function alltenantIndex($landlord_id)
    {
        // Kuhaon ang landlord ID gikan sa current session.
        $sessionLandlordId = session('landlord_id');

        // Kuhaon ang pinakabag-o nga 5 notifications sa landlord.
        $notifications = notificationModel::where('receiverID', $sessionLandlordId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Ihap-on ang unread notifications sa landlord.
        $unreadCount = notificationModel::where('receiverID', $landlord_id)
            ->where('isRead', false)
            ->count();

        // Susihon kung naka-login ba ang landlord.
        if (!$sessionLandlordId) {
            return redirect()->route('loginLandlord')->with('error', 'Please log in as a landlord.');
        }

        // Siguraduhon nga ang URL landlord ID parehas sa session landlord ID.
        if ($landlord_id !== $sessionLandlordId) {
            return redirect()->route('loginLandlord')->with('error', 'Unauthorized access.');
        }

        // Pangitaon ang landlord record gamit ang ID.
        $landlord = landlordModel::find($landlord_id);

        // Kung walay makita nga landlord, ibalik sa login page.
        if (!$landlord) {
            return redirect()->route('loginLandlord')->with('error', 'Landlord not found.');
        }

        // I-load ang tenants list page ug ipasa ang required data.
        return view('landlord.auth.tenantpage', [
            "title" => "Landlord - Tenants List",
            'headerName' => 'Tenants List',
            'landlord' => $landlord,
            'landlord_id' => $landlord_id,
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    // Kuhaon ang listahan sa tenants nga sakop sa logged-in landlord.
    public function tenantsList(Request $request)
    {
        // Kuhaon ang landlord ID gikan sa session.
        $landlordId = session('landlord_id');

        // Ibalik ang unauthorized response kung walay naka-login nga landlord.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }

        // Kuhaon ang tenant records uban sa ilang room, dorm, ug landlord relationships.
        $tenant = approvetenantsModel::with(['room.dorm', 'room.landlord'])
            ->where('status', '<>', 'pending')
            ->where('isDeleted', false)
            ->whereHas('room', function ($query) use ($landlordId) {
                // Siguraduhon nga ang room iya sa logged-in landlord.
                $query->where('fklandlordID', $landlordId);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Ibalik ang tenant list ug landlord ID isip JSON response.
        return response()->json([
            'status' => 'success',
            'tenant' => $tenant,
            'landlord_id' => $landlordId
        ]);
    }

    // Kuhaon ang complete information sa selected tenant.
    public function ViewTenant($id)
    {
        // Kuhaon ang landlord ID gikan sa current session.
        $landlordId = session('landlord_id');

        // Susihon kung naka-login ang landlord.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }

        // Pangitaon ang tenant record ug apilon ang room ug dorm information.
        $tenant = approvetenantsModel::with([
            'room.dorm'
        ])
            ->where('approvedID', $id)
            ->whereHas('room', function ($query) use ($landlordId) {
                // Siguraduhon nga ang tenant room iya sa logged-in landlord.
                $query->where('fklandlordID', $landlordId);
            })
            ->first();

        // Kung walay makita nga tenant record, ibalik ang 404 response.
        if (!$tenant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tenant screening record not found.'
            ], 404);
        }

        // Ibalik ang tenant information isip JSON response.
        return response()->json([
            'status' => 'success',
            'tenant' => $tenant
        ]);
    }

    // I-update ang personal information ug status sa tenant.
    public function updateTenantInformation(Request $request, $id)
    {
        // Kuhaon ang landlord ID gikan sa session.
        $landlordId = session('landlord_id');

        // Siguraduhon nga naka-login ang landlord.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }

        // Pangitaon ang tenant uban sa iyang room ug dorm information.
        $tenant = approvetenantsModel::with('room.dorm')->find($id);

        // Siguraduhon nga existing ang tenant ug sakop siya sa landlord.
        if (!$tenant || !$tenant->room || $tenant->room->fklandlordID !== $landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tenant not found or unauthorized access.'
            ], 404);
        }

        // I-validate ang tenant personal information ug account status.
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

        // Kung naay validation errors, ibalik ang error details.
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        // I-update ang basic personal information sa tenant.
        $tenant->firstname = $request->input('firstname');
        $tenant->lastname = $request->input('lastname');
        $tenant->gender = $request->input('gender');
        $tenant->age = $request->input('age');
        $tenant->contactEmail = $request->input('contactEmail');
        $tenant->contactNumber = $request->input('contactNumber');

        // Variable para sa notification nga himuon base sa selected status.
        $notification = null;

        // Kung active ang status, i-update ang tenant ug maghimo og notification.
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

            // I-update ang tenant status ngadto sa transferring.
            $tenant->status = 'transferring';

            // Maghimo og notification para mahibaloan sa tenant ang status update.
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

            // I-update ang tenant status ngadto sa pending move-out.
            $tenant->status = 'pending_moveout';

            // Maghimo og notification para mahibaloan sa tenant ang status update.
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

            // I-update ang tenant status ngadto sa moved out.
            $tenant->status = 'moved_out';

            // I-apply ang room availability updates kung reservation ang source.
            if ($tenant->source_type === 'Reservation') {

                // Ihap-on ang ubang tenants sa parehas nga room nga active o pending.
                $otherTenants = approvetenantsModel::where('fkroomID', $tenant->fkroomID)
                    ->where('approvedID', '!=', $tenant->approvedID)
                    ->whereIn('status', ['active', 'pending'])
                    ->count();

                // Kung walay laing tenant, himoon nga available ang room.
                if ($otherTenants === 0) {
                    $tenant->room->availability = 'Available';
                } else {
                    // Kung naa pay laing tenant, magpabiling occupied ang room.
                    $tenant->room->availability = 'Occupied';
                }

                // I-reset ang extension ug rental notification fields.
                $tenant->extension_payment_status = 'pending';
                $tenant->extension_decision = 'not_extending';
                $tenant->notifyRent = 0;

                // I-set ang move-out date sa current date.
                $tenant->moveOutDate = Carbon::now();

                // I-clear ang extension date.
                $tenant->extensionDate = null;

                // I-save ang updated room availability.
                $tenant->room->save();
            }

            // Maghimo og notification para mahibaloan sa tenant ang move-out status.
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

        // I-save ang updated tenant information sa database.
        $tenant->save();

        // Ipadala ang in-app notification, SMS, ug email kung adunay nahimong notification.
        if ($notification) {

            // I-broadcast ang notification aron ma-receive kini in real time.
            broadcast(new \App\Events\NewNotificationEvent($notification));

            $type = 'tenant';
            $message = $notification->message;

            // Ipadala ang email gamit ang existing reminder template.
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
                // I-save sa Laravel log kung napakyas ang email sending.
                Log::error('Tenant status email failed: ' . $mailError->getMessage());
            }

            // Ipadala ang SMS notification sa tenant.
            try {
                if (!empty($tenant->contactNumber)) {
                    app(IprogSMSService::class)->sendSMS(
                        $tenant->contactNumber,
                        'DormDash: ' . $message
                    );
                }
            } catch (\Throwable $smsError) {
                // I-save sa Laravel log kung napakyas ang SMS sending.
                Log::error('iProG tenant status SMS failed: ' . $smsError->getMessage());
            }
        }

        // Ibalik ang success response ug updated tenant information.
        return response()->json([
            'status' => 'success',
            'message' => 'Tenant information updated successfully.',
            'tenant' => $tenant
        ]);
    }

    // Kuhaon ang tanang dorms nga registered sa logged-in landlord.
    public function getDorms()
    {
        // Kuhaon ang landlord ID gikan sa session.
        $landlordId = session('landlord_id');

        // Siguraduhon nga naka-login ang landlord.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }

        // Kuhaon ang dorms uban sa ilang room records.
        $dorms = dormModel::with('rooms')
            ->where('fklandlordID', $landlordId)
            ->get();

        // Kung walay dorm nga makita, ibalik ang 404 response.
        if ($dorms->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No dorms found for this landlord.'
            ], 404);
        }

        // Ibalik ang dorm list isip JSON response.
        return response()->json([
            'status' => 'success',
            'dorms' => $dorms
        ]);
    }

    // Kuhaon ang room types base sa room number ug optional dorm ID.
    public function getRoomTypes(Request $request)
    {
        // Kuhaon ang room number ug optional dorm ID gikan sa request.
        $roomNumber = $request->input('roomNumber');
        $dormId = $request->input('dormID');

        // Sugdan ang room query base sa room number.
        $query = roomModel::query()
            ->where('roomNumber', $roomNumber);

        // Kung adunay dorm ID, i-filter usab ang rooms base niini.
        if ($dormId) {
            $query->where('fkdormID', $dormId);
        }

        // Kuhaon ang roomType values gikan sa matching records.
        $roomTypes = $query->pluck('roomType');

        // Ibalik ang room types isip JSON response.
        return response()->json([
            'status' => 'success',
            'roomTypes' => $roomTypes
        ]);
    }

    // Kuhaon ang tanang rooms sulod sa selected dorm.
    public function getRooms(Request $request)
    {
        // Kuhaon ang dorm ID gikan sa request.
        $dormId = $request->input('dormID');

        // Pangitaon ang rooms nga sakop sa selected dorm.
        $rooms = roomModel::where('fkdormID', $dormId)->get();

        // Ibalik ang room records isip JSON response.
        return response()->json([
            'status' => 'success',
            'data' => $rooms
        ]);
    }

    // Kuhaon ang details sa selected room.
    public function getRoomsDetails(Request $request)
    {
        // Kuhaon ang room ID gikan sa request.
        $roomID = $request->input('roomID');

        // Pangitaon ang room record gamit ang iyang ID.
        $rooms = roomModel::where('roomID', $roomID)->get();

        // Ibalik ang room details isip JSON response.
        return response()->json([
            'status' => 'success',
            'data' => $rooms
        ]);
    }

    // Ibalhin ang tenant gikan sa current room ngadto sa bag-ong room.
    public function roomUpdate(Request $request, $roomID)
    {
        // I-validate ang current room ID ug tenant ID.
        $request->validate([
            'current_room_id' => 'required|integer|exists:rooms,roomID',
            'tenant_id' => 'required|integer|exists:approved_tenants,approvedID'
        ]);

        // Pangitaon ang current room ug himoon kini nga available.
        $currentRoom = roomModel::findOrFail($request->current_room_id);
        $currentRoom->availability = 'Available';
        $currentRoom->save();

        // Pangitaon ang selected new room ug i-update ang availability.
        $newRoom = roomModel::findOrFail($roomID);
        $newRoom->availability = 'Occupied';
        $newRoom->save();

        // Susihon ang availability sa selected room.
        if ($newRoom->availability === 'Occupied') {
            return response()->json([
                'status'  => 'error',
                'message' => 'The selected room is already occupied.'
            ]);
        }

        // Pangitaon ang tenant ug i-update ang iyang room assignment.
        $updateTenant = approvetenantsModel::findOrFail($request->tenant_id);
        $updateTenant->fkroomID = $roomID;
        $updateTenant->save();

        // Ibalik ang success response ug updated room information.
        return response()->json([
            'status' => 'success',
            'message' => 'Room updated successfully',
            'current_room' => $currentRoom,
            'new_room' => $newRoom
        ]);
    }

    // Mangita og tenants base sa ilang first name o last name.
    public function searchTenants(Request $request)
    {
        // Kuhaon ang landlord ID gikan sa session.
        $landlordId = session('landlord_id');

        // Siguraduhon nga naka-login ang landlord.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action.'
            ], 403);
        }

        // Kuhaon ug tangtangon ang unnecessary spaces sa search keyword.
        $searchTerm = trim($request->input('query', ''));

        // Pangitaon ang matching tenants nga sakop sa landlord.
        $tenants = approvetenantsModel::with('room.dorm')
            ->whereHas('room', function ($query) use ($landlordId) {
                $query->where('fklandlordID', $landlordId);
            })
            // I-apply ang name search kung adunay search keyword.
            ->when($searchTerm !== '', function ($query) use ($searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('firstname', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('lastname', 'LIKE', "%{$searchTerm}%");
                });
            })
            // Dili iapil ang pending tenants.
            ->where('status', '<>', 'pending')
            ->orderBy('updated_at', 'desc')
            ->get();

        // Ibalik ang matching tenant records isip JSON response.
        return response()->json([
            'status' => 'success',
            'tenants' => $tenants
        ]);
    }

    // I-filter ang tenant list base sa selected dorm.
    public function filterByDorm(Request $request)
    {
        // Kuhaon ang landlord ID ug selected dorm ID.
        $landlordId = session('landlord_id');
        $dormId = $request->input('dorm_id');

        // Siguraduhon nga naka-login ang landlord.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action.'
            ], 403);
        }

        // Sugdan ang tenant query ug iapil ang room ug dorm information.
        $query = approvetenantsModel::with('room.dorm')
            ->whereHas('room', function ($q) use ($landlordId, $dormId) {
                // Siguraduhon nga ang room iya sa logged-in landlord.
                $q->where('fklandlordID', $landlordId);

                // Kung adunay selected dorm, i-filter base sa dorm ID.
                if ($dormId) {
                    $q->where('fkdormID', $dormId);
                }
            })
            // Dili iapil ang pending tenants.
            ->where('approved_tenants.status', '<>', 'pending');

        // Kuhaon ang matching tenant records.
        $tenants = $query->get();

        // Ibalik ang filtered tenant list isip JSON response.
        return response()->json([
            'status' => 'success',
            'tenants' => $tenants
        ]);
    }

    // Magpadala og rental extension notification ngadto sa tenant.
    public function notifyTenant(Request $request)
    {
        try {
            // I-validate ang landlord ID ug approved tenant ID.
            $request->validate([
                'landlordID' => 'required|string',
                'approveID'  => 'required|integer',
            ]);

            // Pangitaon ang tenant uban sa room ug dorm information.
            $tenant = approvetenantsModel::with('room.dorm')
                ->findOrFail($request->approveID);

            // I-update ang rental notification ug extension payment status.
            $tenant->notifyRent = 1;
            $tenant->extension_payment_status = 'pending';
            $tenant->save();

            // Maghimo og in-app notification para sa rental extension.
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

            // I-broadcast ang notification aron ma-receive kini in real time.
            broadcast(new \App\Events\NewNotificationEvent($notification));

            $type = 'tenant';

            // Maghimo sa complete message para sa email ug SMS notification.
            $message = "Hi {$tenant->firstname} {$tenant->lastname}, 👋 This is a notification from your landlord regarding your stay at Room {$tenant->room->roomNumber} in {$tenant->room->dorm->dormName}. 📝 Your current booking has an extension request.";

            // Idugang ang extension details sa message.
            $message .= "\n\nExtension Details:";
            $message .= "\n- Move-out Date: {$tenant->moveOutDate}";
            $message .= "\n\nPlease contact your landlord if you have any questions or concerns. Thank you!";

            // Ipadala ang email gamit ang existing reminder template.
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
                // I-save sa Laravel log kung napakyas ang email sending.
                Log::error('Tenant extension email failed: ' . $mailError->getMessage());
            }

            // Ipadala ang SMS notification sa tenant.
            try {
                if (!empty($tenant->contactNumber)) {
                    app(IprogSMSService::class)->sendSMS(
                        $tenant->contactNumber,
                        'DormDash: ' . $message
                    );
                }
            } catch (\Throwable $smsError) {
                // I-save sa Laravel log kung napakyas ang SMS sending.
                Log::error('iProG extension SMS failed: ' . $smsError->getMessage());
            }

            // Ibalik ang success response human mapadala ang notification.
            return response()->json([
                'status'  => 'success',
                'message' => 'Notification sent to tenant successfully',
                'data'    => $notification
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {

            // Ibalik ang validation errors kung dili valid ang request.
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $e->errors()
            ], 422);

        } catch (\Exception $e) {

            // Ibalik ang error details kung adunay laing problema.
            return response()->json([
                'status'  => 'error',
                'message' => 'Something went wrong while sending notification',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // Kuhaon ang pending tenants nga available para sa move-in process.
    public function addMoveInTenant()
    {
        // Kuhaon ang landlord ID gikan sa session.
        $landlordId = session('landlord_id');

        // Siguraduhon nga naka-login ang landlord.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }

        // Kuhaon ang pending tenants nga sakop sa landlord.
        $tenant = approvetenantsModel::with(['room.dorm', 'room.landlord'])
            ->where('status', 'pending')
            ->whereHas('room', function ($query) use ($landlordId) {
                $query->where('fklandlordID', $landlordId);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(1);

        // Ibalik ang pending tenant records ug landlord ID.
        return response()->json([
            'status' => 'success',
            'moveInTenant' => $tenant,
            'landlord_id' => $landlordId
        ]);
    }

    // Mangita og pending tenants base sa ilang name o contact email.
    public function searchMoveInTenant(Request $request)
    {
        // Kuhaon ang landlord ID ug search keyword.
        $landlordId = session('landlord_id');
        $searchTerm = $request->input('searchMoveIn');

        // Siguraduhon nga naka-login ang landlord.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action.'
            ], 403);
        }

        // Pangitaon ang matching pending tenants nga sakop sa landlord.
        $tenants = approvetenantsModel::with('room.dorm')
            ->whereHas('room', function ($q) use ($landlordId) {
                $q->where('fklandlordID', $landlordId);
            })
            ->where('status', 'pending')
            // I-apply ang search sa first name, last name, o contact email.
            ->when($searchTerm, function ($q) use ($searchTerm) {
                $q->where(function ($sub) use ($searchTerm) {
                    $sub->where('firstname', 'like', "%{$searchTerm}%")
                        ->orWhere('lastname', 'like', "%{$searchTerm}%")
                        ->orWhere('contactEmail', 'like', "%{$searchTerm}%");
                });
            })
            ->paginate(1);

        // Ibalik ang matching pending tenant records.
        return response()->json([
            'status' => 'success',
            'tenants' => $tenants
        ]);
    }

    // I-move in ang selected tenant ug i-update ang room availability.
    public function moveInTenant(Request $request)
    {
        // Kuhaon ang landlord ID gikan sa session.
        $landlordId = session('landlord_id');

        // I-validate ang approved tenant ID.
        $request->validate([
            'approvedID' => 'required|integer',
        ]);

        try {
            // Kuhaon ang approved tenant ID gikan sa request.
            $approvedID = $request->input('approvedID');

            // Pangitaon ang tenant uban sa room, dorm, ug landlord information.
            $tenant = approvetenantsModel::with('room.dorm', 'room.landlord')
                ->findOrFail($approvedID);

            // Susihon kung adunay laing active tenant sa parehas nga room.
            $hasActiveTenant = approvetenantsModel::where('fkroomID', $tenant->fkroomID)
                ->where('status', 'active')
                ->exists();

            // Dili tugotan ang move-in kung naa nay active tenant sa room.
            if ($hasActiveTenant) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'This room already has an active tenant. Cannot move in another tenant.',
                ], 200);
            }

            // I-update ang tenant status ngadto sa active.
            $tenant->status = 'active';

            // I-update ang room availability ngadto sa occupied.
            if ($tenant->room) {
                $tenant->room->availability = 'Occupied';
                $tenant->room->save();
            }

            // I-save ang updated tenant record.
            $tenant->save();

            // Maghimo og in-app notification para sa successful move-in.
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

            // Maghimo sa move-in message para sa email ug SMS.
            $message = "Hi {$tenant->firstname} {$tenant->lastname}, 👋 Your booking for Room {$tenant->room->roomNumber} at {$tenant->room->dorm->dormName} is now active ✅. You can move in starting from {$tenant->moveInDate}. Enjoy your stay!";

            // Ipadala ang email gamit ang existing reminder template.
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
                // I-save sa Laravel log kung napakyas ang email sending.
                Log::error('Tenant move-in email failed: ' . $mailError->getMessage());
            }

            // Ipadala ang SMS notification sa tenant.
            try {
                if (!empty($tenant->contactNumber)) {
                    app(IprogSMSService::class)->sendSMS(
                        $tenant->contactNumber,
                        'DormDash: ' . $message
                    );
                }
            } catch (\Throwable $smsError) {
                // I-save sa Laravel log kung napakyas ang SMS sending.
                Log::error('iProG move-in SMS failed: ' . $smsError->getMessage());
            }

            // I-broadcast ang move-in notification in real time.
            broadcast(new \App\Events\NewNotificationEvent($notification));

            // Ibalik ang success response ug updated tenant data.
            return response()->json([
                'status'  => 'success',
                'message' => 'Tenant moved in successfully',
                'data'    => $tenant
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {

            // Ibalik ang validation errors kung dili valid ang request.
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $e->errors()
            ], 422);

        } catch (\Exception $e) {

            // Ibalik ang error details kung adunay problema sa move-in process.
            return response()->json([
                'status'  => 'error',
                'message' => 'Something went wrong while moving in tenant',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // Kuhaon ang tenant details ug pinakabag-o nga payment record.
    public function viewTenantPayment($id)
    {
        // Kuhaon ang landlord ID gikan sa session.
        $landlordId = session('landlord_id');

        // Siguraduhon nga naka-login ang landlord.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }

        // Pangitaon ang tenant uban sa room, dorm, ug latest payment.
        $tenant = approvetenantsModel::with([
            'room.dorm',
            'payments' => function ($query) {
                // Kuhaon lamang ang pinakabag-o nga payment record.
                $query->latest('created_at')->take(1);
            }
        ])
            ->where('approvedID', $id)
            ->whereHas('room', function ($query) use ($landlordId) {
                // Siguraduhon nga ang tenant room iya sa landlord.
                $query->where('fklandlordID', $landlordId);
            })
            ->first();

        // Kung walay makita nga tenant, ibalik ang 404 response.
        if (!$tenant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tenant screening record not found.'
            ], 404);
        }

        // Ibalik ang tenant ug payment details.
        return response()->json([
            'status' => 'success',
            'tenant' => $tenant
        ]);
    }

    // I-update ang rental extension details sa tenant.
    public function updateTenantExtension(Request $request, $id)
    {
        // Kuhaon ang landlord ID gikan sa session.
        $landlordId = session('landlord_id');

        // Siguraduhon nga naka-login ang landlord.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }

        // Siguraduhon nga valid ang extension date ug naa sa umaabot.
        $request->validate([
            'extensionDate' => 'required|date|after:today',
        ]);

        try {
            // Kuhaon ang extension date gikan sa request.
            $extensionDate = $request->input('extensionDate');

            // Pangitaon ang tenant uban sa room, dorm, landlord, ug payment information.
            $tenant = approvetenantsModel::with('room.dorm', 'room.landlord', 'payments')
                ->findOrFail($id);

            // Siguraduhon nga ang tenant sakop sa logged-in landlord.
            if ($tenant->room->fklandlordID !== $landlordId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized action. This tenant does not belong to you.'
                ], 403);
            }

            // I-convert ang extension date ngadto sa Carbon date object.
            $extensionDate = Carbon::parse($request->extensionDate);

            // I-update ang extension decision ug rental dates.
            $tenant->extension_decision = 'pending';
            $tenant->notifyRent = 0;
            $tenant->extensionDate = $extensionDate;
            $tenant->moveOutDate = $extensionDate->copy()->addMonth();
            $tenant->paymentOption = 'onsite';
            $tenant->extension_payment_status = 'done';
            $tenant->save();

            // I-update ang payment records sa tenant.
            $tenant->payments()->update([
                'fkapprovedID' => $id,
                'status' => 'approved',
                'paymentType' => 'onsite',
                'amount' => $tenant->room->price
            ]);

            // Maghimo og notification para mahibaloan sa tenant nga approved ang extension.
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

            // I-broadcast ang notification ngadto sa tenant in real time.
            broadcast(new \App\Events\NewNotificationEvent($notification));

            // Ipadala ang SMS notification sa tenant.
            try {
                if (!empty($tenant->contactNumber)) {
                    app(IprogSMSService::class)->sendSMS(
                        $tenant->contactNumber,
                        'DormDash: ' . $notification->message
                    );
                }
            } catch (\Throwable $smsError) {
                // I-save sa Laravel log kung napakyas ang SMS sending.
                Log::error('iProG SMS failed: ' . $smsError->getMessage());
            }

            // Ipadala ang email notification sa tenant.
            try {
                if (!empty($tenant->email)) {
                    Mail::raw($notification->message, function ($message) use ($tenant) {
                        $message->to($tenant->email)
                            ->subject('Rent Extension Approved');
                    });
                }
            } catch (\Throwable $mailError) {
                // I-save sa Laravel log kung napakyas ang email sending.
                Log::error('Tenant email failed: ' . $mailError->getMessage());
            }

            // Ibalik ang success response human ma-approve ang extension payment.
            return response()->json([
                'status'  => 'success',
                'message' => 'Tenant payment approved successfully',
                'data'    => $tenant
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {

            // Ibalik ang validation errors.
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $e->errors()
            ], 422);

        } catch (\Exception $e) {

            // Ibalik ang error details kung napakyas ang extension update.
            return response()->json([
                'status'  => 'error',
                'message' => 'Something went wrong while approving tenant payment',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // I-update ang status sa tenant extension payment.
    public function updateTenantPaymentExtension(Request $request, $id)
    {
        // Kuhaon ang landlord ID gikan sa session.
        $landlordId = session('landlord_id');

        // Siguraduhon nga naka-login ang landlord.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }

        // Tugotan lamang ang approved o rejected nga payment status.
        $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        try {
            // Kuhaon ang payment status, payment ID, ug extension date.
            $status = $request->input('status');
            $paymentID = $request->input('paymentID');
            $extensionDate = $request->input('extensionDate');

            // Pangitaon ang tenant uban sa room, dorm, landlord, ug payment information.
            $tenant = approvetenantsModel::with('room.dorm', 'room.landlord', 'payments')
                ->findOrFail($id);

            // Siguraduhon nga ang tenant sakop sa logged-in landlord.
            if ($tenant->room->fklandlordID !== $landlordId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized action. This tenant does not belong to you.'
                ], 403);
            }

            // Kung approved ang payment, i-update ang extension details.
            if ($status === 'approved') {

                $tenant->extension_decision = 'pending';
                $tenant->notifyRent = 0;
                $tenant->extension_payment_status = 'done';
                $tenant->extensionDate = Carbon::parse($extensionDate);
                $tenant->moveOutDate = Carbon::parse($extensionDate)->addMonth();
                $tenant->save();

                // I-update ang selected payment record ngadto sa approved.
                $tenant->payments()
                    ->where('paymentID', $paymentID)
                    ->update(['status' => $status]);

                // Maghimo og notification para sa approved extension payment.
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

                // I-broadcast ang approved payment notification.
                broadcast(new \App\Events\NewNotificationEvent($notification));

                // I-set ang email subject ug message para sa approved payment.
                $emailSubject = 'Rent Extension Payment Approved';
                $emailMessage = $notification->message;

            } else if ($status === 'rejected') {

                // I-reset ang extension notification ug payment status.
                $tenant->notifyRent = 0;
                $tenant->extension_payment_status = 'pending';
                $tenant->save();

                // I-update ang selected payment record ngadto sa rejected.
                $tenant->payments()
                    ->where('paymentID', $paymentID)
                    ->update(['status' => 'rejected']);

                // Maghimo og notification para sa rejected extension payment.
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

                // I-broadcast ang rejected payment notification.
                broadcast(new \App\Events\NewNotificationEvent($notification));

                // I-set ang email subject ug message para sa rejected payment.
                $emailSubject = 'Rent Extension Payment Rejected';
                $emailMessage = $notification->message;
            }

            // Ipadala ang SMS notification sa tenant.
            try {
                if (!empty($tenant->contactNumber)) {
                    app(IprogSMSService::class)->sendSMS(
                        $tenant->contactNumber,
                        'DormDash: ' . $notification->message
                    );
                }
            } catch (\Throwable $smsError) {
                // I-save sa Laravel log kung napakyas ang SMS sending.
                Log::error('iProG SMS failed: ' . $smsError->getMessage());
            }

            // Ipadala ang email notification sa tenant.
            try {
                if (!empty($tenant->email)) {
                    Mail::raw($emailMessage, function ($message) use ($tenant, $emailSubject) {
                        $message->to($tenant->email)
                            ->subject($emailSubject);
                    });
                }
            } catch (\Throwable $mailError) {
                // I-save sa Laravel log kung napakyas ang email sending.
                Log::error('Tenant email failed: ' . $mailError->getMessage());
            }

            // Ibalik ang success message base sa payment status.
            return response()->json([
                'status'  => 'success',
                'message' => $status === 'approved'
                    ? 'Tenant payment approved successfully'
                    : 'Tenant payment rejected successfully',
                'data'    => $tenant
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {

            // Ibalik ang validation errors.
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $e->errors()
            ], 422);

        } catch (\Exception $e) {

            // Ibalik ang error details kung napakyas ang payment update.
            return response()->json([
                'status'  => 'error',
                'message' => 'Something went wrong while approving tenant payment',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // I-soft delete ang tenant record imbis nga permanenteng tangtangon.
    public function softDelete(Request $request, $id)
    {
        // Kuhaon ang landlord ID gikan sa session.
        $landlordId = session('landlord_id');

        // Siguraduhon nga naka-login ang landlord.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }

        try {

            // Pangitaon ang tenant record ug i-set ang deleted flag.
            $tenant = approvetenantsModel::findOrFail($id);
            $tenant->isDeleted = true;
            $tenant->save();

            // Ibalik ang success response human ma-soft delete ang record.
            return response()->json([
                'status'  => 'success',
                'message' => 'Tenant record temporarily deleted successfully',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {

            // Ibalik ang validation errors.
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $e->errors()
            ], 422);

        } catch (\Exception $e) {

            // Ibalik ang error details kung napakyas ang soft delete.
            return response()->json([
                'status'  => 'error',
                'message' => 'Something went wrong while deleting tenant record',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // Mag-generate og PDF report sa active tenants sa landlord.
    public function generateActiveTenantReport(Request $request)
    {
        // Kuhaon ang landlord ID gikan sa session.
        $landlordId = session('landlord_id');

        // Siguraduhon nga naka-login ang landlord.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }

        // Kuhaon ang logo path ug optional date filter gikan sa request.
        $logoPath = public_path('images/Logo/logo.png');
        $date = $request->query('date');

        // Kuhaon ang active tenants nga sakop sa landlord.
        $tenants = approvetenantsModel::with('room.dorm')
            ->whereHas('room', function ($query) use ($landlordId) {
                $query->where('fklandlordID', $landlordId);
            })
            ->where('status', 'active')
            ->where('isDeleted', false)
            // Kung adunay date, i-filter ang move-in records base sa month ug year.
            ->when(!empty($date), function ($query) use ($date) {
                $query->whereYear('moveInDate', date('Y', strtotime($date)))
                    ->whereMonth('moveInDate', date('m', strtotime($date)));
            })
            ->orderBy('moveInDate', 'desc')
            ->get();

        // Ihap-on ang total active tenants nga naa sa report.
        $totalTenants = $tenants->count();

        // I-load ang active tenant report PDF view ug ipasa ang required data.
        $pdf = Pdf::loadView('landlord.reports.active-tenant-report', [
            'tenants' => $tenants,
            'logoPath' => $logoPath,
            'totalTenants' => $totalTenants,
            'selectedDate' => $date
        ]);

        // I-display ang generated PDF sa browser.
        return $pdf->stream("landlord.reports.active-tenant-report-{$landlordId}.pdf");
    }

    // Mag-generate og PDF report sa successful rental extension payments.
    public function gener9yMnTm4NSzvG9rrwjM2ec8xZgh1cafXH8(Request $request)
    {
        // Kuhaon ang landlord ID gikan sa session.
        $landlordId = session('landlord_id');

        // Siguraduhon nga naka-login ang landlord.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }

        // Kuhaon ang logo path ug optional date filter.
        $logoPath = public_path('images/Logo/logo.png');
        $date = $request->query('date');

        // Kuhaon ang tenants nga adunay completed extension payment.
        $tenantsWithExtensions = approvetenantsModel::with(['room.dorm', 'payments'])
            ->whereHas('room', fn($q) => $q->where('fklandlordID', $landlordId))
            ->where('extension_payment_status', 'done')
            ->where('isDeleted', false)
            // Kung adunay date, i-filter ang payment records base sa month ug year.
            ->when($date, function ($query) use ($date) {
                $query->whereHas('payments', fn($q) => $q
                    ->whereYear('created_at', date('Y', strtotime($date)))
                    ->whereMonth('created_at', date('m', strtotime($date))));
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Kwentahon ang total income gikan sa payment records sa tenants.
        $totalIncome = $tenantsWithExtensions->sum(function ($tenant) {
            return $tenant->payments->sum('amount');
        });

        // I-load ang extension payment report PDF view ug ipasa ang required data.
        $pdf = Pdf::loadView('landlord.reports.extension-payment-report', [
            'tenants' => $tenantsWithExtensions,
            'logoPath' => $logoPath,
            'totalIncome' => $totalIncome
        ]);

        // I-display ang generated PDF sa browser.
        return $pdf->stream("landlord-extension-payment-report-{$landlordId}.pdf");
    }
}
