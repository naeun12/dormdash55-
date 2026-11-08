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
    // I-display ang room management page sa landlord.
    public function RoomManagement($landlordId)
    {
        try {
            // Kuhaa ang landlord ID gikan sa current session.
            $sessionLandlordId = session('landlord_id');

            // I-check kung naka-login ba ang landlord.
            if (!$sessionLandlordId) {
                return redirect()->route('loginLandlord')->with('error', 'Please log in as a landlord.');
            }

            // Siguraduhon nga ang gi-access nga landlord ID parehas sa session ID.
            if ($landlordId !== $sessionLandlordId) {
                return redirect()->route('loginLandlord')->with('error', 'Unauthorized access.');
            }

            // Pangitaa ang landlord record sa database.
            $landlord = landlordModel::where('landlordID', $landlordId)->first();

            // Kung walay makita nga landlord, ibalik sa login page.
            if (!$landlord) {
                return redirect()->route('loginLandlord')->with('error', 'Landlord not found.');
            }

            // Kuhaa ang tanang dormitory nga iya sa landlord.
            $dorms = dormModel::where('fklandlordID', $landlordId)->get();

            // Kuhaa ang pinakabag-ong lima ka notifications sa landlord.
            $notifications = notificationModel::where('receiverID', $sessionLandlordId)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            // I-count ang unread notifications sa landlord.
            $unreadCount = notificationModel::where('receiverID', $landlordId)
                ->where('isRead', false)
                ->count();

            // I-load ang room management page uban sa landlord, dorms, ug notifications.
            return view('landlord.auth.roomManagement', [
                'title' => 'Landlord - Room Management',
                'headerName' => 'Room Management',
                'color' => 'primary',
                'landlord' => $landlord,
                'landlord_id' => $sessionLandlordId,
                'dorms' => $dorms,
                'notifications' => $notifications,
                'unread_count' => $unreadCount
            ]);

        } catch (\Exception $e) {
            // I-record sa Laravel log ang error details kung napakyas ang pag-load sa page.
            Log::error('RoomManagement error: '.$e->getMessage().' in '.$e->getFile().' on line '.$e->getLine());

            return response()->json(['error' => 'Something went wrong. Check logs for details.'], 500);
        }
    }

    // Kuhaa ang mga room nga sakop sa specific dorm.
    public function getRoomsByDorm($dormId)
    {
        // Kuhaa ang landlord ID gikan sa session.
        $landlordId = session('landlord_id');

        // Pangitaa ang rooms base sa landlord ID ug dorm ID, apil ang dorm information.
        $rooms = roomModel::where('fklandlordID', $landlordId)
            ->where('fkdormID', $dormId)
            ->with('dorm')
            ->paginate(6);

        // Ibalik ang room list isip JSON response.
        return response()->json([
            'rooms' => $rooms // Importante kini para ma-display ang rooms sa frontend.
        ]);
    }

    // I-filter ang rooms base sa gender preference.
    public function getRoomsByGender($gender)
    {
        // Kuhaa ang landlord ID gikan sa session.
        $landlordId = session('landlord_id');

        // Pangitaa ang rooms nga parehas sa gipiling gender preference.
        $rooms = roomModel::where('fklandlordID', $landlordId)
            ->where('genderPreference', $gender)
            ->paginate(6);

        // Ibalik ang filtered rooms isip JSON.
        return response()->json([
            'rooms' => $rooms
        ]);
    }

    // I-filter ang rooms base sa availability status.
    public function getRoomsByAvailability($availability)
    {
        // Kuhaa ang landlord ID gikan sa session.
        $landlordId = session('landlord_id');

        // Pangitaa ang rooms nga parehas sa gipiling availability.
        $rooms = roomModel::where('fklandlordID', $landlordId)
            ->where('availability', $availability)
            ->paginate(6);

        // Ibalik ang filtered rooms isip JSON.
        return response()->json([
            'rooms' => $rooms
        ]);
    }

    // I-filter ang rooms base sa room type nga gipili sa user.
    public function getRoomsByRoomType(Request $request)
    {
        // Kuhaa ang room type gikan sa query parameter.
        $roomType = $request->query('roomType'); // Pwede usab gamiton ang $request->input('type').

        // Kuhaa ang landlord ID gikan sa session.
        $landlordId = session('landlord_id');

        // Pangitaa ang rooms base sa landlord ID ug room type.
        $rooms = roomModel::where('fklandlordID', $landlordId)
            ->where('roomType', $roomType)
            ->paginate(6);

        // Ibalik ang filtered rooms isip JSON.
        return response()->json([
            'rooms' => $rooms
        ]);
    }

    // Magdugang og bag-ong room sa dormitory sa landlord.
    public function addRoom(Request $request)
    {
        // I-validate ang tanang impormasyon nga gikinahanglan sa pagdugang og room.
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
            // Custom validation messages para sa mas klaro nga error sa frontend.
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

        // Kung naay validation errors, ibalik kini sa frontend.
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Kuhaa ang landlord ID gikan sa current session.
            $landlordId = session('landlord_id');

            // Siguraduhon nga naka-login ang landlord sa dili pa magdugang og room.
            if (!$landlordId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized action. Please log in as a landlord.'
                ], 403);
            }

            // Pangitaa ang dorm ug siguroha nga iya kini sa naka-login nga landlord.
            $dorm = dormModel::where('dormID', $request->input('dormsId'))
                ->where('fklandlordID', $landlordId)
                ->first();

            // Kung walay dorm nga nakit-an, ibalik ang 404 response.
            if (!$dorm) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dormitory not found.'
                ], 404);
            }

            // I-check kung naa na ba daan ang room number sulod sa gipiling dorm.
            $existingRoom = roomModel::where('fkdormID', $request->input('dormsId'))
                ->where('roomNumber', $request->input('roomNumber'))
                ->first();

            // Kung bag-o ang room number ug wala nay available capacity, dili tugotan ang pagdugang.
            if (!$existingRoom && $dorm->totalRooms <= 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No available capacity for new rooms in this dormitory.'
                ], 422);
            }

            // Maghimo og bag-ong room record ug i-assign ang mga impormasyon gikan sa request.
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

            // I-check kung adunay gi-upload nga room image.
            if ($request->hasFile('roomImageFile')) {
                // Kuhaa ang uploaded image ug himoa og unique filename gamit ang current timestamp.
                $image1 = $request->file('roomImageFile');
                $image1Name = time() . '_1.' . $image1->getClientOriginalExtension();

                // I-save ang image sulod sa public storage folder.
                $image1->storeAs('public/uploads/roomImages', $image1Name);

                // Himoa ang public URL nga gamiton sa pag-display sa room image.
                $mainImageUrl = asset('storage/uploads/roomImages/' . $image1Name);
            } else {
                // Kung walay uploaded image, walay image URL nga i-assign.
                $mainImageUrl = null;
            }

            // I-assign ang image URL ug i-save ang bag-ong room record.
            $room->roomImages = $mainImageUrl;
            $room->save();

            // I-decrease ang totalRooms kung bag-o ang room number.
            if (!$existingRoom) {
                $dorm->totalRooms = max(0, $dorm->totalRooms - 1);
                $dorm->save();
            }

            // Kuhaa ang landlord details para sa notifications.
            $landlord = landlordModel::find($landlordId);

            if ($landlord) {
                // Message nga gamiton sa in-app notification, SMS, ug email.
                $message = "DormDash: Room {$room->roomNumber} has been successfully added to your dorm '{$dorm->dormName}'.";

                // Maghimo og in-app notification para pahibal-on ang landlord nga nadugang na ang room.
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

                // I-broadcast ang bag-ong notification ngadto sa connected clients.
                broadcast(new \App\Events\NewNotificationEvent($notifications));

                // SMS Notification
                try {
                    // I-check kung naa bay registered phone number ang landlord.
                    if (!empty($landlord->phoneNumber)) {
                        // Ipadala ang room added notification pinaagi sa iProg SMS service.
                        app(IprogSMSService::class)->sendSMS(
                            $landlord->phoneNumber,
                            $message
                        );
                    }
                } catch (\Throwable $e) {
                    // I-record sa Laravel log kung napakyas ang SMS sending.
                    Log::error('Add room SMS failed: ' . $e->getMessage());
                }

                // Email Notification
                try {
                    // I-check kung naa bay registered email ang landlord.
                    if (!empty($landlord->email)) {
                        // Ipadala ang notification message pinaagi sa email.
                        Mail::raw($message, function ($mail) use ($landlord) {
                            $mail->to($landlord->email)
                                ->subject('DormDash - Room Added Successfully');
                        });
                    }
                } catch (\Throwable $e) {
                    // I-record sa Laravel log kung napakyas ang email sending.
                    Log::error('Add room email failed: ' . $e->getMessage());
                }
            }

            // Ibalik ang success response uban sa bag-ong room information ug room ID.
            return response()->json([
                'status' => 'success',
                'message' => 'Room added successfully.',
                'room' => $room,
                'room_id' => $room->roomID
            ]);

        } catch (\Illuminate\Database\QueryException $e) {
            // I-record ang database error kung napakyas ang pagdugang sa room.
            Log::error('Add room database error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add room.'
            ], 500);

        } catch (\Exception $e) {
            // I-record ang ubang errors nga nahitabo sa pagdugang sa room.
            Log::error('Add room error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add room.'
            ], 500);
        }
    }

    // Kuhaa ug i-display ang tanang rooms nga iya sa naka-login nga landlord.
    public function ListRooms(Request $request)
    {
        // Kuhaa ang landlord ID gikan sa session.
        $landlordId = session('landlord_id');

        // Kung walay landlord session, ibalik ang unauthorized response.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }

        // Kuhaa ang rooms uban sa dorm ug features information.
        $rooms = roomModel::with(['dorm', 'features'])->where('fklandlordID', $landlordId)
            ->orderBy('created_at', 'desc')
            ->paginate(6);

        // Ibalik ang room list isip JSON response.
        return response()->json([
            'status' => 'success',
            'rooms' => $rooms
        ]);
    }

    // Magdugang og feature o amenity sa usa ka room.
    public function addRoomFeatures(Request $request)
    {
        // Kuhaa ang landlord ID gikan sa session.
        $landlordId = session('landlord_id');

        // Siguraduhon nga naka-login ang landlord.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }

        // I-validate ang feature name ug room ID.
        $validated = $request->validate([
            'features' => 'required|string|max:255',
            'room_id' => 'required|integer',
        ], [
            'features.required' => 'Please fill up features.',
            'features.string' => 'Feature name must be a character or word.',
            'features.max' => 'Feature name must not exceed 255 characters.',
        ]);

        try {
            // Tangtanga ang sobra nga spaces sa feature name.
            $features = trim($validated['features']);

            // Kuhaa ang room ID nga padad-on gikan sa request.
            $roomId = $validated['room_id'];

            // I-check kung naa na ba ang feature sa global features table.
            $existingFeature = featuresModel::where('featureName', $features)->first();

            // Kung wala pa ang feature, maghimo og bag-ong feature record.
            if (!$existingFeature) {
                $existingFeature = featuresModel::create([
                    'featureName' => $features
                ]);
            }

            // I-check kung naka-assign na ba daan ang feature sa maong room.
            $alreadyLinked = roomfeaturesModel::where('fkroomID', $roomId)
                ->where('fkfeatureID', $existingFeature->id)
                ->exists();

            // Kung naka-assign na ang feature, dili na kini idugang pag-usab.
            if ($alreadyLinked) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This feature is already linked to this room.'
                ], 400);
            }

            // I-link ang feature ngadto sa room pinaagi sa roomfeatures table.
            roomfeaturesModel::create([
                'fkroomID' => $roomId,
                'fkfeatureID' => $existingFeature->id,
            ]);

            // Ibalik ang success response human ma-assign ang feature.
            return response()->json([
                'status' => 'success',
                'message' => 'Features added successfully!',
            ]);

        } catch (\Exception $e) {
            // I-record sa Laravel log kung naay error sa pagdugang og feature.
            Log::error('Error adding features: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Error adding features.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Ipakita ang detalye sa usa ka room base sa room ID.
    public function ViewRoom($id)
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

        // Pangitaa ang room gamit ang ID ug i-load ang dorm ug features information.
        $room = roomModel::with(['dorm', 'features'])
            ->where('roomID', $id)
            ->where('fklandlordID', $landlordId)
            ->first();

        // I-check kung naa ba ang room record.
        if (!$room) {
            return response()->json([
                'status' => 'error',
                'message' => 'Room not found.'
            ], 404);
        }

        // Ibalik ang room details isip JSON response.
        return response()->json([
            'status' => 'success',
            'room' => $room
        ]);
    }

    // I-update ang impormasyon sa existing room.
    public function UpdateRoom(Request $request, $id)
    {
        // Kuhaa ang landlord ID gikan sa current session.
        $landlordId = session('landlord_id');

        // Kung walay naka-login nga landlord, ibalik ang unauthorized response.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }

        // I-validate ang mga impormasyon nga ipadala sa pag-update sa room.
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
            // Custom validation messages para sa room information.
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

        // Kung napakyas ang validation, ibalik ang validation errors.
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        // Kuhaa ang validated input values.
        $validated = $validator->validated();

        // Pangitaa ang room base sa room ID ug landlord ID.
        $room = roomModel::where('roomID', $id)
            ->where('fklandlordID', $landlordId)
            ->first();

        // Kung walay room nga nakit-an, ibalik ang 404 response.
        if ($room === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Room not found.'
            ], 404);
        }

        // I-update ang room details gamit ang validated ug request values.
        $room->roomNumber = $validated['room_number'];
        $room->roomType = $validated['room_type'];
        $room->availability = $validated['availability'];
        $room->furnishing_status = $request->input('furnishing_status');
        $room->listingType = $request->input('listing_type');
        $room->areaSqm = $request->input('area_sqm');
        $room->genderPreference = $request->input('gender_preference');
        $room->price = round($validated['price'], 2);

        // I-check kung adunay bag-ong room image nga gi-upload.
        if ($request->hasFile('roomImageFile')) {
            // Kuhaa ang bag-ong image ug himoa og unique filename.
            $image1 = $request->file('roomImageFile');
            $image1Name = time() . '_1.' . $image1->getClientOriginalExtension();

            // I-save ang uploaded image sa public storage folder.
            $image1->storeAs('public/uploads/roomImages', $image1Name);

            // Himoa ang public URL sa bag-ong image ug i-assign sa room.
            $mainImageUrl = asset('storage/uploads/roomImages/' . $image1Name);
            $room->roomImages = $mainImageUrl;
        } else {
            // Kung walay bag-ong image, ipadayon ang existing image URL.
            $room->roomImages = $request->input('existingImage');
        }

        try {
            // I-save ang updated room information sa database.
            $room->save();

            // Kuhaa ang landlord details para sa notification.
            $landlord = landlordModel::find($landlordId);

            if ($landlord) {
                // Message nga gamiton sa in-app notification, SMS, ug email.
                $message = "DormDash: Room {$room->roomNumber} has been successfully updated.";

                // Maghimo og in-app notification para sa landlord.
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

                // I-broadcast ang updated room notification.
                broadcast(new \App\Events\NewNotificationEvent($notifications));

                // SMS Notification
                try {
                    // I-check kung naa bay registered phone number ang landlord.
                    if (!empty($landlord->phoneNumber)) {
                        // Ipadala ang update notification pinaagi sa iProg SMS service.
                        app(IprogSMSService::class)->sendSMS(
                            $landlord->phoneNumber,
                            $message
                        );
                    }
                } catch (\Throwable $e) {
                    // I-record sa Laravel log kung napakyas ang SMS sending.
                    Log::error('Update room SMS failed: ' . $e->getMessage());
                }

                // Email Notification
                try {
                    // I-check kung naa bay registered email address ang landlord.
                    if (!empty($landlord->email)) {
                        // Ipadala ang update notification pinaagi sa email.
                        Mail::raw($message, function ($mail) use ($landlord) {
                            $mail->to($landlord->email)
                                ->subject('DormDash - Room Updated Successfully');
                        });
                    }
                } catch (\Throwable $e) {
                    // I-record sa Laravel log kung napakyas ang email sending.
                    Log::error('Update room email failed: ' . $e->getMessage());
                }
            }

            // Ibalik ang success response uban sa updated room information.
            return response()->json([
                'status' => 'success',
                'message' => 'Room updated successfully.',
                'room' => $room
            ]);

        } catch (\Exception $e) {
            // I-record sa Laravel log kung napakyas ang pag-save sa updated room.
            Log::error('Update room failed: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating the room.'
            ], 500);
        }
    }

    // Tangtangon ang feature nga naka-assign sa usa ka room.
    public function deleteRoomFeatures($pivotId)
    {
        // Kuhaa ang landlord ID gikan sa session.
        $landlordId = session('landlord_id');

        // Kung walay naka-login nga landlord, dili tugotan ang request.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Please log in.'
            ], 403);
        }

        // Pangitaa ang feature record gamit ang pivot ID.
        $pivot = featuresModel::find($pivotId);

        // Kung walay feature record nga nakit-an, ibalik ang 404 response.
        if (!$pivot) {
            return response()->json([
                'status' => 'error',
                'message' => 'Feature link not found.'
            ], 404);
        }

        // I-check kung ang room nga gi-assignan sa feature iya ba sa landlord.
        $ownsRoom = roomModel::where('roomID', $pivot->fkroomID)
            ->where('fklandlordID', $landlordId)
            ->exists();

        // Kung dili iya sa landlord ang room, dili tugotan ang pag-delete sa feature.
        if (!$ownsRoom) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized to delete this feature.'
            ], 403);
        }

        // I-delete ang feature record.
        $pivot->delete();

        // Ibalik ang success response human matangtang ang feature.
        return response()->json([
            'status' => 'success',
            'message' => 'Feature removed from room.'
        ]);
    }

    // I-delete ang room nga iya sa naka-login nga landlord.
    public function DeleteRoom($id)
    {
        // Kuhaa ang landlord ID gikan sa session.
        $landlordId = session('landlord_id');

        // Kung walay naka-login nga landlord, dili tugotan ang request.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }

        // Pangitaa ang room gamit ang room ID ug landlord ID.
        $room = roomModel::where('roomID', $id)
            ->where('fklandlordID', $landlordId)
            ->first();

        // Kung walay room nga nakit-an, ibalik ang 404 response.
        if (!$room) {
            return response()->json([
                'status' => 'error',
                'message' => 'Room not found.'
            ], 404);
        }

        try {
            // I-delete ang room record sa database.
            $room->delete();

            // Ibalik ang success response human ma-delete ang room.
            return response()->json([
                'status' => 'success',
                'message' => 'Room deleted successfully.'
            ]);

        } catch (\Exception $e) {
            // Kung napakyas ang pag-delete, ibalik ang error details.
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the room: ' . $e->getMessage()
            ], 500);
        }
    }

    // I-enable ang reservation option sa usa ka room.
    public function allowReserve($id)
    {
        // Pangitaa ang room gamit ang ID; mo-fail kung walay record nga makita.
        $room = roomModel::findOrFail($id);

        // I-set ang room isip pwede nang ma-reserve.
        $room->isReservable = true;
        $room->save();

        // Ibalik ang success response human ma-enable ang reservation.
        return response()->json([
            'success' => true,
            'message' => 'The room has been set as available for reservations.'
        ]);
    }
}
