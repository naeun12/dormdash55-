<?php
namespace App\Http\Controllers\landlord\auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Models\landlord\landlordModel;
use App\Models\landlord\roomModel;
use App\Models\landlord\dormModel;
use App\Models\landlord\roomfeaturesModel;
use App\Models\landlord\featuresModel;
use App\Services\IprogSMSService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\notificationModel;
use Illuminate\Support\Facades\Auth;
class roompageController extends Controller
{
   public function RoomManagement($landlordId)
{
    try {
        $sessionLandlordId = session('landlord_id');


        if (!$sessionLandlordId) {
            return redirect()->route('loginLandlord')->with('error', 'Please log in as a landlord.');
        }


        if ($landlordId !== $sessionLandlordId) {
            return redirect()->route('loginLandlord')->with('error', 'Unauthorized access.');
        }


        $landlord = landlordModel::where('landlordID', $landlordId)->first();
        if (!$landlord) {
            return redirect()->route('loginLandlord')->with('error', 'Landlord not found.');
        }


        $dorms = dormModel::where('fklandlordID', $landlordId)->get();


        $notifications = notificationModel::where('receiverID', $sessionLandlordId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();


        $unreadCount = notificationModel::where('receiverID', $landlordId)
            ->where('isRead', false)
            ->count();


        return view('landlord.auth.roomManagement', [
            'title' => 'Landlord - Room Management',
            'headerName' => 'Room Management',
            'color' =>'primary',
            'landlord' => $landlord,
            'landlord_id' => $sessionLandlordId,
            'dorms' => $dorms,
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);


    } catch (\Exception $e) {
        Log::error('RoomManagement error: '.$e->getMessage().' in '.$e->getFile().' on line '.$e->getLine());
        return response()->json(['error' => 'Something went wrong. Check logs for details.'], 500);
    }
}


    public function getRoomsByDorm($dormId)
    {
        $landlordId = session('landlord_id');
        $rooms = roomModel::where('fklandlordID', $landlordId)
            ->where('fkdormID', $dormId)
            ->with('dorm')
            ->paginate(6);
            return response()->json([
                'rooms' => $rooms // ✅ this is important!
            ]);  
    }
    public function getRoomsByGender($gender)
    {
        $landlordId = session('landlord_id');
        $rooms = roomModel::where('fklandlordID', $landlordId)
            ->where('genderPreference', $gender)
            ->paginate(6);
        return response()->json([
            'rooms' => $rooms
        ]);
    }
    public function getRoomsByAvailability($availability)
    {
        $landlordId = session('landlord_id');
        $rooms = roomModel::where('fklandlordID', $landlordId)
            ->where('availability', $availability)
            ->paginate(6);
        return response()->json([
            'rooms' => $rooms
        ]);
    }
    public function getRoomsByRoomType(Request $request)
    {
        $roomType = $request->query('roomType'); // or $request->input('type')


        $landlordId = session('landlord_id');
        $rooms = roomModel::where('fklandlordID', $landlordId)
            ->where('roomType', $roomType)
            ->paginate(6);
        return response()->json([
            'rooms' => $rooms
        ]);
    }


   public function addRoom(Request $request)
{
    $validator = Validator::make($request->all(), [
        'dormsId' => 'required|integer',
        'roomNumber' => ['required', 'string', 'max:255'],
        'roomType' => 'required|string|max:255',
        'availability' => 'required|string|max:255',
        'listing_type' => 'required|string|max:255',
        'area_sqm' => 'required|string|max:255',
        'gender_preference' => 'required|string|max:255',
        'furnishing_status' => 'required|string|max:255',
        'roomImageFile' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        'price' => 'required|numeric|min:0',
    ], [
        'dormsId.required' => 'Please select a dormitory.',
        'dormsId.integer' => 'Invalid dormitory selection.',
        'roomNumber.required' => 'Please enter the room number.',
        'roomNumber.string' => 'Room number must be text.',
        'roomNumber.max' => 'Room number must be 255 characters or fewer.',
        'roomType.required' => 'Please select a room type.',
        'roomType.string' => 'Room type must be text.',
        'roomType.max' => 'Room type must be 255 characters or fewer.',
        'availability.required' => 'Please select availability status.',
        'availability.string' => 'Availability must be text.',
        'availability.max' => 'Availability must be 255 characters or fewer.',
        'listing_type.required' => 'Please select a bed type.',
        'listing_type.string' => 'Bed type must be text.',
        'listing_type.max' => 'Bed type must be 255 characters or fewer.',
        'area_sqm.required' => 'Please enter the room area in square meters.',
        'area_sqm.string' => 'Room area must be text.',
        'area_sqm.max' => 'Room area must be 255 characters or fewer.',
        'gender_preference.required' => 'Please select a gender preference.',
        'gender_preference.string' => 'Gender preference must be text.',
        'gender_preference.max' => 'Gender preference must be 255 characters or fewer.',
        'furnishing_status.required' => 'Please specify the furnishing status.',
        'furnishing_status.string' => 'Furnishing status must be text.',
        'furnishing_status.max' => 'Furnishing status must be 255 characters or fewer.',
        'roomImageFile.required' => 'Please upload a room image.',
        'roomImageFile.image' => 'The uploaded file must be an image.',
        'roomImageFile.mimes' => 'Accepted image formats: jpg, jpeg, png, webp.',
        'roomImageFile.max' => 'Image size must not exceed 2MB.',
        'price.required' => 'Please enter the room price.',
        'price.numeric' => 'Price must be a valid number.',
        'price.min' => 'Price cannot be negative.',
    ]);


    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'errors' => $validator->errors()
        ], 422);
    }


    try {
        $landlordId = session('landlord_id');


        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }


        $dorm = dormModel::where('dormID', $request->input('dormsId'))
            ->where('fklandlordID', $landlordId)
            ->first();


        if (!$dorm) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dormitory not found.'
            ], 404);
        }


        $existingRoom = roomModel::where('fkdormID', $request->input('dormsId'))
            ->where('roomNumber', $request->input('roomNumber'))
            ->first();


        if (!$existingRoom && $dorm->totalRooms <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'No available capacity for new rooms in this dormitory.'
            ], 422);
        }


        $room = new roomModel();
        $room->fkdormID = $request->input('dormsId');
        $room->fklandlordID = $landlordId;
        $room->roomNumber = $request->input('roomNumber');
        $room->availability = $request->input('availability');
        $room->roomType = $request->input('roomType');
        $room->furnishing_status = $request->input('furnishing_status');
        $room->listingType = $request->input('listing_type');
        $room->areaSqm = $request->input('area_sqm');
        $room->genderPreference = $request->input('gender_preference');
        $room->price = round($request->input('price'), 2);


        if ($request->hasFile('roomImageFile')) {
            $image1 = $request->file('roomImageFile');
            $image1Name = time() . '_1.' . $image1->getClientOriginalExtension();
            $image1->storeAs('public/uploads/roomImages', $image1Name);
            $mainImageUrl = asset('storage/uploads/roomImages/' . $image1Name);
        } else {
            $mainImageUrl = null;
        }


        $room->roomImages = $mainImageUrl;
        $room->save();


        // Decrease totalRooms only if it's a new room number
        if (!$existingRoom) {
            $dorm->totalRooms = max(0, $dorm->totalRooms - 1);
            $dorm->save();
        }


        // Get landlord details
        $landlord = landlordModel::find($landlordId);


        if ($landlord) {
            $message = "DormDash: Room {$room->roomNumber} has been successfully added to your dorm '{$dorm->dormName}'.";


            // In-app notification
            $notifications = notificationModel::create([
                'senderID' => $landlordId,
                'senderType' => 'landlord',
                'receiverID' => $landlordId,
                'receiverType' => 'landlord',
                'title' => 'Room Added Successfully',
                'message' => $message,
                'isRead' => false,
                'readAt' => null,
            ]);


            broadcast(new \App\Events\NewNotificationEvent($notifications));


            // SMS Notification
            try {
                if (!empty($landlord->phoneNumber)) {
                    app(IprogSMSService::class)->sendSMS(
                        $landlord->phoneNumber,
                        $message
                    );
                }
            } catch (\Throwable $e) {
                Log::error('Add room SMS failed: ' . $e->getMessage());
            }


            // Email Notification
            try {
                if (!empty($landlord->email)) {
                    Mail::raw($message, function ($mail) use ($landlord) {
                        $mail->to($landlord->email)
                            ->subject('DormDash - Room Added Successfully');
                    });
                }
            } catch (\Throwable $e) {
                Log::error('Add room email failed: ' . $e->getMessage());
            }
        }


        return response()->json([
            'status' => 'success',
            'message' => 'Room added successfully.',
            'room' => $room,
            'room_id' => $room->roomID
        ]);


    } catch (\Illuminate\Database\QueryException $e) {
        Log::error('Add room database error: ' . $e->getMessage());


        return response()->json([
            'status' => 'error',
            'message' => 'Failed to add room.'
        ], 500);


    } catch (\Exception $e) {
        Log::error('Add room error: ' . $e->getMessage());


        return response()->json([
            'status' => 'error',
            'message' => 'Failed to add room.'
        ], 500);
    }
}


    public function ListRooms(Request $request)
    {
        $landlordId = session('landlord_id');
   
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }


        $rooms = roomModel::with(['dorm', 'features'])->where('fklandlordID', $landlordId)
        ->orderBy('created_at', 'desc')
        ->paginate(6);    
        return response()->json([
            'status' => 'success',
            'rooms' => $rooms
        ]);
    }
    public function addRoomFeatures(Request $request)
    {
        $landlordId = session('landlord_id');
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }    
        $validated = $request->validate([
            'features' => 'required|string|max:255',
            'room_id' => 'required|integer',
        ],
        [
            'features.required' => 'Please fill up features.',
            'features.string' => 'Feature name must be a character or word.',
            'features.max' => 'Feature name must not exceed 255 characters.',
        ]);
   
        try {
            $features = trim($validated['features']);
            $roomId = $validated['room_id'];
            // Check if the feature already exists for this room
            $existingFeature = featuresModel::where('featureName', $features)->first();


            // If it exists globally, use it, otherwise create it
            if (!$existingFeature) {
                $existingFeature = featuresModel::create([
                    'featureName' => $features
                ]);
            }
            // Check if the room already has this feature assigned
            $alreadyLinked = roomfeaturesModel::where('fkroomID', $roomId)
                ->where('fkfeatureID', $existingFeature->id)
                ->exists();
   
            if ($alreadyLinked) {
                return response()->json([
                'status' => 'error',
                    'message' => 'This feature is already linked to this room.'
                ], 400);
            }


            // Link the feature to the room
            roomfeaturesModel::create([
                'fkroomID' => $roomId,
                'fkfeatureID' => $existingFeature->id,
            ]);
   
            return response()->json([
                'status' => 'success',
                'message' => 'Features added successfully!',
            ]);
   
        } catch (\Exception $e) {
            Log::error('Error adding features: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error adding features.',
                'error' => $e->getMessage()
            ], 500);
        }
    }  
     public function ViewRoom($id)
    {
        $landlordId = session('landlord_id');
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }
   
        // Fetch the room by ID with its dorm info
        $room = roomModel::with(['dorm', 'features'])
        ->where('roomID', $id)
        ->where('fklandlordID', $landlordId)
        ->first();
        // Check if the room exists
        if (!$room) {
            return response()->json([
                'status' => 'error',
                'message' => 'Room not found.'
            ], 404);
        }
        return response()->json([
            'status' => 'success',
            'room' => $room
        ]);
    }
 public function UpdateRoom(Request $request, $id)
{
    $landlordId = session('landlord_id');


    if (!$landlordId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized action. Please log in as a landlord.'
        ], 403);
    }


    $validator = Validator::make($request->all(), [
        'dormitory_id' => 'required|integer',
        'room_number' => ['required', 'string', 'max:255'],
        'room_type' => 'required|string|max:255',
        'availability' => 'required|string|max:255',
        'listing_type' => 'required|string|max:255',
        'area_sqm' => 'required|string|max:255',
        'gender_preference' => 'required|string|max:255',
        'furnishing_status' => 'required|string|max:255',
        'roomImageFile' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        'price' => 'required|numeric|min:0',
        'existingImage' => 'nullable|string'
    ], [
        'roomNumber.required' => 'Room number is required.',
        'roomNumber.string' => 'Room number must be a string.',
        'roomNumber.max' => 'Room number must not exceed 255 characters.',


        'room_type.required' => 'Room type is required.',
        'room_type.string' => 'Room type must be a string.',
        'room_type.max' => 'Room type must not exceed 255 characters.',


        'availability.required' => 'Availability status is required.',
        'availability.string' => 'Availability must be a string.',
        'availability.max' => 'Availability must not exceed 255 characters.',


        'listing_type.required' => 'Bed type is required.',
        'listing_type.string' => 'Bed type must be a string.',
        'listing_type.max' => 'Bed type must not exceed 255 characters.',


        'area_sqm.required' => 'Room area (sqm) is required.',
        'area_sqm.string' => 'Room area must be a string.',
        'area_sqm.max' => 'Room area must not exceed 255 characters.',


        'gender_preference.required' => 'Gender preference is required.',
        'gender_preference.string' => 'Gender preference must be a string.',
        'gender_preference.max' => 'Gender preference must not exceed 255 characters.',


        'furnishing_status.required' => 'Furnishing status is required.',
        'furnishing_status.string' => 'Furnishing status must be a string.',
        'furnishing_status.max' => 'Furnishing status must not exceed 255 characters.',


        'roomImageFile.image' => 'The uploaded file must be an image.',
        'roomImageFile.mimes' => 'The room image must be a file of type: jpg, jpeg, png, webp.',
        'roomImageFile.max' => 'The room image may not be greater than 2MB.',


        'price.required' => 'Price is required.',
        'price.numeric' => 'Price must be a valid number.',
        'price.min' => 'Price must not be negative.',
    ]);


    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed.',
            'errors' => $validator->errors()
        ], 422);
    }


    $validated = $validator->validated();


    // Fetch the room
    $room = roomModel::where('roomID', $id)
        ->where('fklandlordID', $landlordId)
        ->first();


    if ($room === null) {
        return response()->json([
            'status' => 'error',
            'message' => 'Room not found.'
        ], 404);
    }


    $room->roomNumber = $validated['room_number'];
    $room->roomType = $validated['room_type'];
    $room->availability = $validated['availability'];
    $room->furnishing_status = $request->input('furnishing_status');
    $room->listingType = $request->input('listing_type');
    $room->areaSqm = $request->input('area_sqm');
    $room->genderPreference = $request->input('gender_preference');
    $room->price = round($validated['price'], 2);


    if ($request->hasFile('roomImageFile')) {
        $image1 = $request->file('roomImageFile');
        $image1Name = time() . '_1.' . $image1->getClientOriginalExtension();
        $image1->storeAs('public/uploads/roomImages', $image1Name);


        $mainImageUrl = asset('storage/uploads/roomImages/' . $image1Name);
        $room->roomImages = $mainImageUrl;
    } else {
        $room->roomImages = $request->input('existingImage');
    }


    try {
        $room->save();


        // Get landlord details
        $landlord = landlordModel::find($landlordId);


        if ($landlord) {
            $message = "DormDash: Room {$room->roomNumber} has been successfully updated.";


            // In-app notification
            $notifications = notificationModel::create([
                'senderID' => $landlordId,
                'senderType' => 'landlord',
                'receiverID' => $landlordId,
                'receiverType' => 'landlord',
                'title' => 'Room Updated Successfully',
                'message' => $message,
                'isRead' => false,
                'readAt' => null,
            ]);


            broadcast(new \App\Events\NewNotificationEvent($notifications));


            // SMS Notification
            try {
                if (!empty($landlord->phoneNumber)) {
                    app(IprogSMSService::class)->sendSMS(
                        $landlord->phoneNumber,
                        $message
                    );
                }
            } catch (\Throwable $e) {
                Log::error('Update room SMS failed: ' . $e->getMessage());
            }


            // Email Notification
            try {
                if (!empty($landlord->email)) {
                    Mail::raw($message, function ($mail) use ($landlord) {
                        $mail->to($landlord->email)
                            ->subject('DormDash - Room Updated Successfully');
                    });
                }
            } catch (\Throwable $e) {
                Log::error('Update room email failed: ' . $e->getMessage());
            }
        }


        return response()->json([
            'status' => 'success',
            'message' => 'Room updated successfully.',
            'room' => $room
        ]);


    } catch (\Exception $e) {
        Log::error('Update room failed: ' . $e->getMessage());


        return response()->json([
            'status' => 'error',
            'message' => 'An error occurred while updating the room.'
        ], 500);
    }
}
public function deleteRoomFeatures($pivotId)
    {
        $landlordId = session('landlord_id');
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Please log in.'
            ], 403);
        }
        $pivot = featuresModel::find($pivotId);
        if (!$pivot) {
            return response()->json([
                'status' => 'error',
                'message' => 'Feature link not found.'
            ], 404);
        }
   
        // Check if the dorm_id belongs to the landlord
        $ownsRoom = roomModel::where('roomID', $pivot->fkroomID)
            ->where('fklandlordID', $landlordId)
            ->exists();


        if (!$ownsRoom) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized to delete this feature.'
            ], 403);
        }
   
        $pivot->delete();
   
        return response()->json([
            'status' => 'success',
            'message' => 'Feature removed from room.'
        ]);
    }
    public function DeleteRoom($id)
    {
        $landlordId = session('landlord_id');
   
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }
   
        $room = roomModel::where('roomID', $id)
            ->where('fklandlordID', $landlordId)
            ->first();
   
        if (!$room) {
            return response()->json([
                'status' => 'error',
                'message' => 'Room not found.'
            ], 404);
        }
        try {
            $room->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Room deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the room: ' . $e->getMessage()
            ], 500);
        }
    }
    public function allowReserve($id)
{
    $room = roomModel::findOrFail($id);
    $room->isReservable = true;
    $room->save();


 return response()->json([
        'success' => true,
        'message' => 'The room has been set as available for reservations.'
    ]);}


   
   
}



