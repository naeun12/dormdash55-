<?php

namespace App\Http\Controllers\landlord\auth;

use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\landlord\landlordModel;
use App\Models\landlord\dormaminitiesModel;
use App\Models\landlord\aminitiesModel;
use App\Models\landlord\dormModel;
use App\Models\landlord\dormimagesModel;
use App\Models\landlord\dormrulesModel;
use App\Models\landlord\rulesModel;
use App\Models\notificationModel;
use App\Services\IprogSMSService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class dormpageController extends Controller
{
    // Ipakita ang Dorm Management page sa landlord.
    public function DormManagement($landlordId)
    {
        try {
            // Kuhaon ang landlord ID nga naa sa current session.
            $sessionLandlordId = session('landlord_id');

            // Siguraduhon nga naka-login ang landlord.
            if (!$sessionLandlordId) {
                return redirect()->route('loginLandlord')->with('error', 'Please log in as a landlord.');
            }

            // Siguraduhon nga ang landlord ID sa URL parehas sa session.
            if ($landlordId !== $sessionLandlordId) {
                return redirect()->route('loginLandlord')->with('error', 'Unauthorized access.');
            }

            // Pangitaon ang landlord record gamit ang iyang ID.
            $landlord = landlordModel::find($landlordId);

            if (!$landlord) {
                return redirect()->route('loginLandlord')->with('error', 'Landlord not found.');
            }

            // Kuhaon ang pinakabag-o nga lima ka notifications sa landlord.
            $notifications = notificationModel::where('receiverID', $sessionLandlordId)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            // Ihap pila ang notifications nga wala pa mabasa.
            $unreadCount = notificationModel::where('receiverID', $landlordId)
                ->where('isRead', false)
                ->count();

            // I-load ang Dorm Management view uban sa gikinahanglang data.
            return view('landlord.auth.DormManagement', [
                'title' => 'Landlord - Dorm Management',
                'headerName' => 'Dorm Management',
                'color' => 'primary',
                'landlord' => $landlord,
                'landlord_id' => $landlordId,
                'notifications' => $notifications,
                'unread_count' => $unreadCount
            ]);

        } catch (\Exception $e) {
            // I-save sa Laravel log ang detalye kung adunay error.
            Log::error('DormManagement error: '.$e->getMessage().' in '.$e->getFile().' on line '.$e->getLine());

            // Ibalik ang error response kung mapakyas ang proseso.
            return response()->json(['error' => 'Something went wrong. Check logs for details.'], 500);
        }
    }

    // Kuhaon ang verification status sa landlord gikan sa database.
    public function getlandlordVerifiedStatus()
    {
        $landlordId = session('landlord_id');

        // Pangitaon ang landlord base sa iyang session ID.
        $landlord = landlordModel::where('landlordID', $landlordId)->first();

        return response()->json([
            'status' => 'success',
            'isVerified' => $landlord->isVerified,
        ]);
    }

    // Mangita og dormitories gamit ang search term.
    public function searchDorms(Request $request)
    {
        $landlordId = session('landlord_id');

        // Dili tugotan ang request kung walay naka-login nga landlord.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }

        // Kuhaon ang search input ug himoon nga lowercase.
        $searchTerm = strtolower($request->input('search', ''));

        // Kuhaon ang dormitories nga iya sa landlord ug motakdo sa search.
        $dorms = dormModel::with('images')
            ->where('fklandlordID', $landlordId)
            ->where(function ($query) use ($searchTerm) {
                if (!empty($searchTerm)) {
                    $query->whereRaw('LOWER(dormName) LIKE ?', ["%$searchTerm%"]);
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate(5);

        // Ibalik ang message kung walay nakitang dormitory.
        if ($dorms->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'No dormitories found matching your search criteria.',
                'dorms' => $dorms
            ]);
        }

        return response()->json([
            'status' => 'success',
            'dorms' => $dorms
        ]);
    }

    // I-filter ang dormitories base sa ilang location o address.
    public function filterLocations(Request $request)
    {
        $landlordId = session('landlord_id');

        // Siguraduhon nga naka-login ang landlord.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }

        // Kuhaon ang location nga gipili sa user.
        $location = strtolower($request->input('location', ''));

        // Kuhaon ang dormitories sa landlord nga motakdo sa location filter.
        $dorms = dormModel::with('images')
            ->where('fklandlordID', $landlordId)
            ->when($location !== 'all' && !empty($location), function ($query) use ($location) {
                // REGEXP aron ma-match ang tibuok pulong ug dili parte lang sa laing pulong.
                $query->whereRaw("LOWER(address) REGEXP ?", ['[[:<:]]' . $location . '[[:>:]]']);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(5);

        // Ibalik ang message kung walay dormitory nga motakdo sa filter.
        if ($dorms->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'No dormitories found matching your search criteria.',
                'dorms' => $dorms
            ]);
        }

        return response()->json([
            'status' => 'success',
            'dorms' => $dorms
        ]);
    }

    // I-filter ang dormitories base sa ilang availability status.
    public function filteredAvailability(Request $request)
    {
        $landlordId = session('landlord_id');
        $availability = strtolower($request->input('availability', ''));

        // Kuhaon ang dormitories nga iya sa landlord ug motakdo sa availability.
        $dorms = dormModel::with('images')
            ->where('fklandlordID', $landlordId)
            ->where(function ($query) use ($availability) {
                if (!empty($availability) && $availability !== 'all') {
                    $query->whereRaw('LOWER(availability) = ?', [$availability]);
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate(5);

        // Ibalik ang message kung walay dormitory nga motakdo sa filter.
        if ($dorms->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'No dormitories found matching your search criteria.',
                'dorms' => $dorms
            ]);
        }

        return response()->json([
            'status' => 'success',
            'dorms' => $dorms
        ]);
    }

    // I-validate ang mga input fields sa dili pa magdugang og dormitory.
    public function inputFieldDorm(Request $request)
    {
        try {
            // Susiha kung sakto ug kompleto ang mga impormasyon nga gisulod.
            $validated = $request->validate([
                'dorm_name' => 'required|string|max:255|unique:dorms,dormName',
                'address' => 'required|string|max:255',
                'description' => 'required|string',
                'total_rooms' => 'required|integer|min:1',
                'contact_email' => 'required|email|max:255',
                'contact_phone' => 'required|string|max:11|min:11',
                'availability' => 'required|string',
                'occupancy_type' => 'required|string',
                'building_type' => 'required|string',
                'gcashNumber'  => 'required|string|regex:/^09[0-9]{9}$/',
            ], [
                'dorm_name.required' => 'Please enter the dormitory name.',
                'dorm_name.unique' => 'This dormitory name is already taken.',
                'dorm_name.max' => 'The dormitory name must not exceed 255 characters.',
                'availability.required' => 'Please enter the Availability.',
                'occupancy_type.required' => 'Please enter the Occupancy type name.',
                'building_type.required' => 'Please enter the Building type.',
                'gcashNumber.required' => 'Please enter your GCash number.',
                'gcashNumber.regex'    => 'Invalid GCash number format. Must be 11 digits starting with 09.',
                'address.required' => 'Please enter the address.',
                'address.max' => 'The address must not exceed 255 characters.',
                'total_rooms.required' => 'Please enter the total number of rooms.',
                'total_rooms.integer' => 'Total rooms must be a number.',
                'total_rooms.min' => 'There must be at least 1 room.',
                'contact_email.required' => 'Please enter a contact email.',
                'contact_email.email' => 'Please enter a valid email address.',
                'contact_email.max' => 'The contact email must not exceed 255 characters.',
                'contact_phone.required' => 'Please enter a contact phone number.',
                'contact_phone.max' => 'The contact phone number must not exceed 11 characters.',
            ]);

            // Ibalik ang success response kung nakapasar sa validation.
            return response()->json([
                'status' => 'success'
            ]);
        }
        catch(\Illuminate\Validation\ValidationException $e) {
            // Ibalik ang validation errors aron ma-display sa frontend.
            return response()->json([
                'status' => 'error',
                'message' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            // Ibalik ang server error kung adunay wala damhang problema.
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // I-validate ang main image sa dormitory.
    public function uploadmainImage(Request $request)
    {
        try {
            // Siguraduhon nga image file ang gi-upload ug dili molapas sa 2MB.
            $validated = $request->validate([
                'roomImage1File' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ], [
                'roomImage1File.required' => 'Please upload an image of the room.',
                'roomImage1File.image' => 'The uploaded file must be an image.',
                'roomImage1File.mimes' => 'Only jpeg, png, jpg, gif, or svg formats are allowed.',
                'roomImage1File.max' => 'The image must not exceed 2MB in size.',
            ]);

            return response()->json([
                'status' => 'success'
            ]);
        }
        catch(\Illuminate\Validation\ValidationException $e) {
            // Ibalik ang mga validation errors sa frontend.
            return response()->json([
                'status' => 'error',
                'errors' => $e->errors(),
            ], 422);
        }
        catch (\Exception $e) {
            // Ibalik ang error kung mapakyas ang image validation.
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // I-validate ang secondary image sa dormitory.
    public function uploadsecondaryImage(Request $request)
    {
        try {
            // Susiha ang image format ug file size.
            $validated = $request->validate([
                'roomImage2File' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ], [
                'roomImage2File.required' => 'Please upload an image of the room.',
                'roomImage2File.image' => 'The uploaded file must be an image.',
                'roomImage2File.mimes' => 'Only jpeg, png, jpg, gif, or svg formats are allowed.',
                'roomImage2File.max' => 'The image must not exceed 2MB in size.',
            ]);

            return response()->json([
                'status' => 'success'
            ]);
        }
        catch(\Illuminate\Validation\ValidationException $e) {
            // Ibalik ang validation errors kung dili mosunod sa requirements ang image.
            return response()->json([
                'status' => 'error',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            // Ibalik ang server error kung mapakyas ang proseso.
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // Maghimo og bag-ong dormitory ug i-save ang iyang impormasyon ug images.
    public function AddDorm(Request $request)
    {
        $landlordId = session('landlord_id');

        // Siguraduhon nga naka-login ang landlord sa dili pa magdugang og dormitory.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }

        // I-validate ang dorm details, location coordinates, contact information ug images.
        $validated = $request->validate([
            'dorm_name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'description' => 'nullable|string',
            'total_rooms' => 'required|integer|min:1',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'required|string|max:11',
            'rules' => 'nullable|string',
            'roomImage1File' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'roomImage2File' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'roomImage3File' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'availability' => 'required|string',
            'occupancy_type' => 'required|string',
            'building_type' => 'required|string',
            'gcashNumber' => 'required|string',
        ], [
            'roomImage3File.required' => 'Please upload an image of the room.',
            'roomImage3File.image' => 'The uploaded file must be an image.',
            'roomImage3File.mimes' => 'Only jpeg, png, jpg, gif, or svg formats are allowed.',
            'roomImage3File.max' => 'The image must not exceed 2MB in size.',
        ]);

        try {
            // Maghimo og random dorm ID nga wala pa nagamit sa database.
            do {
                $randomDormId = rand(1000, 9999);
            } while (dormModel::where('dormID', $randomDormId)->exists());

            // Ibutang ang validated dorm information sa dorm model.
            $dorm = new dormModel();
            $dorm->dormID = $randomDormId;
            $dorm->dormName = $validated['dorm_name'];
            $dorm->address = $validated['address'];
            $dorm->fklandlordID = $landlordId;
            $dorm->latitude = $validated['latitude'];
            $dorm->longitude = $validated['longitude'];
            $dorm->description = $validated['description'] ?? null;
            $dorm->totalRooms = $validated['total_rooms'];
            $dorm->gcashNumber = $validated['gcashNumber'];
            $dorm->contactEmail = $validated['contact_email'];
            $dorm->contactPhone = $validated['contact_phone'];
            $dorm->availability = $validated['availability'];
            $dorm->occupancyType = $validated['occupancy_type'];
            $dorm->buildingType = $validated['building_type'];
            $dorm->save();

            // I-upload ang unang image ug himoon ang iyang public URL.
            if ($request->hasFile('roomImage1File')) {
                $image1 = $request->file('roomImage1File');
                $image1Name = time() . '_1.' . $image1->getClientOriginalExtension();
                $image1->storeAs('public/uploads/roomImages', $image1Name);
                $mainImageUrl = asset('storage/uploads/roomImages/' . $image1Name);
            } else {
                $mainImageUrl = null;
            }

            // I-upload ang ikaduhang image ug himoon ang iyang public URL.
            if ($request->hasFile('roomImage2File')) {
                $image2 = $request->file('roomImage2File');
                $image2Name = time() . '_2.' . $image2->getClientOriginalExtension();
                $image2->storeAs('public/uploads/roomImages', $image2Name);
                $secondImageUrl = asset('storage/uploads/roomImages/' . $image2Name);
            } else {
                $secondImageUrl = null;
            }

            // I-upload ang ikatulong image ug himoon ang iyang public URL.
            if ($request->hasFile('roomImage3File')) {
                $image3 = $request->file('roomImage3File');
                $image3Name = time() . '_3.' . $image3->getClientOriginalExtension();
                $image3->storeAs('public/uploads/roomImages', $image3Name);
                $thirdImageUrl = asset('storage/uploads/roomImages/' . $image3Name);
            } else {
                $thirdImageUrl = null;
            }

            // I-save ang tulo ka image URLs ug i-connect kini sa dorm ID.
            $dormImage = new dormimagesModel();
            $dormImage->fkdormID = $dorm->dormID;
            $dormImage->mainImage = $mainImageUrl;
            $dormImage->secondaryImage = $secondImageUrl;
            $dormImage->thirdImage = $thirdImageUrl;
            $dormImage->save();

            // Kuhaon ang impormasyon sa landlord para sa notifications.
            $landlord = \App\Models\landlord\landlordModel::where(
                'landlordID',
                $landlordId
            )->first();

            if ($landlord) {
                // Maghimo og mensahe nga ipakita sa landlord notification.
                $notificationMessage = "Your dorm '{$dorm->dormName}' has been successfully added to DormDash.";

                // I-save ang notification sa database.
                $notifications = notificationModel::create([
                    'senderID' => $landlord->landlordID,
                    'senderType' => 'landlord',
                    'receiverID' => $landlord->landlordID,
                    'receiverType' => 'landlord',
                    'title' => 'Dorm Added Successfully',
                    'message' => $notificationMessage,
                    'isRead' => false,
                    'readAt' => null,
                ]);

                // I-broadcast ang notification aron ma-update ang real-time notification listeners.
                broadcast(new \App\Events\NewNotificationEvent($notifications));

                $message = "DormDash: Congratulations! Your dorm '{$dorm->dormName}' has been successfully added to your account.";

                // Ipadala ang SMS confirmation ngadto sa registered phone number sa landlord.
                try {
                    app(IprogSMSService::class)->sendSMS(
                        $landlord->phoneNumber,
                        $message
                    );
                } catch (\Throwable $e) {
                    // I-log ang SMS error kung mapakyas ang pagpadala.
                    Log::error('Add dorm SMS failed: ' . $e->getMessage());
                }

                // Ipadala usab ang email confirmation ngadto sa landlord.
                try {
                    Mail::raw($message, function ($mail) use ($landlord) {
                        $mail->to($landlord->email)
                            ->subject('DormDash - Dorm Added Successfully');
                    });
                } catch (\Throwable $e) {
                    // I-log ang email error kung mapakyas ang pagpadala.
                    Log::error('Add dorm email failed: ' . $e->getMessage());
                }
            }

            // Ibalik ang success response uban sa bag-ong dorm ID.
            return response()->json([
                'status' => 'success',
                'message' => 'Dorm added successfully!',
                'dormId' => $dorm->dormID,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Ibalik ang validation errors kung adunay invalid nga input.
            return response()->json([
                'status' => 'error',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            // Ibalik ang server error ug iapil ang error details para sa debugging.
            return response()->json([
                'status' => 'error',
                'message' => 'Error adding dorm.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // I-update ang impormasyon sa existing dormitory.
    public function UpdateDorm(Request $request, $id)
    {
        try {
            // I-validate ang bag-ong dorm details.
            // Ang unique rule mo-ignore sa current dorm aron dili ma-consider nga duplicate ang iyang kaugalingong name.
            $validated = $request->validate([
                'dormName' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('dorms', 'dormName')->ignore($id, 'dormID'),
                ],
                'address' => 'required|string|max:255',
                'description' => 'required|string|max:1000',
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'totalRooms' => 'required|integer|min:1',
                'contactEmail' => 'required|email|max:255',
                'contactPhone' => 'required|string|min:11|max:11|regex:/^\+?[0-9]{7,11}$/',
                'availability' => 'required|string',
                'occupancyType' => 'required|string',
                'gcashNumber' => 'required|string|regex:/^09[0-9]{9}$/',
                'buildingType' => 'required|string',
            ], [
                'dormName.required' => 'Please enter the dormitory name.',
                'dormName.unique' => 'This dormitory name is already taken.',
                'dormName.max' => 'The dormitory name must not exceed 255 characters.',
                'availability.required' => 'Please enter the Availability.',
                'occupancyType.required' => 'Please enter the Occupancy type name.',
                'buildingType.required' => 'Please enter the Building type.',
                'address.required' => 'Please enter the address.',
                'address.max' => 'The address must not exceed 255 characters.',
                'gcashNumber.required' => 'Please enter your GCash number.',
                'gcashNumber.regex' => 'Invalid GCash number format. Must be 11 digits starting with 09.',
                'totalRooms.required' => 'Please enter the total number of rooms.',
                'totalRooms.integer' => 'Total rooms must be a number.',
                'totalRooms.min' => 'There must be at least 1 room.',
                'contactEmail.required' => 'Please enter a contact email.',
                'contactEmail.email' => 'Please enter a valid email address.',
                'contactEmail.max' => 'The contact email must not exceed 255 characters.',
                'contactPhone.required' => 'Please enter a contact phone number.',
                'contactPhone.max' => 'The contact phone number must not exceed 11 characters.',
            ]);

            $landlordId = session('landlord_id');

            // Siguraduhon nga naka-login ang landlord.
            if (!$landlordId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized action. Please log in as a landlord.'
                ], 403);
            }

            // Pangitaon ang dorm base sa ID ug tag-iya niini.
            $dorm = dormModel::where('dormID', $id)
                ->where('fklandlordID', $landlordId)
                ->first();

            // Ibalik ang 404 kung walay dorm nga nakit-an.
            if (!$dorm) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dorm not found.'
                ], 404);
            }

            // I-update ang mga fields sa dorm gamit ang validated data.
            $dorm->dormName = $validated['dormName'];
            $dorm->address = $validated['address'];
            $dorm->latitude = $validated['latitude'];
            $dorm->longitude = $validated['longitude'];
            $dorm->description = $validated['description'] ?? null;
            $dorm->totalRooms = $validated['totalRooms'];
            $dorm->gcashNumber = $validated['gcashNumber'];
            $dorm->contactEmail = $validated['contactEmail'];
            $dorm->contactPhone = $validated['contactPhone'];
            $dorm->availability = $validated['availability'];
            $dorm->occupancyType = $validated['occupancyType'];
            $dorm->buildingType = $validated['buildingType'];
            $dorm->save();

            // Kuhaon ang landlord details para sa notification.
            $landlord = \App\Models\landlord\landlordModel::where(
                'landlordID',
                $landlordId
            )->first();

            if ($landlord) {
                // Maghimo og notification message para sa successful update.
                $notificationMessage = "Your dorm '{$dorm->dormName}' has been successfully updated.";

                // I-save ang notification sa database.
                $notifications = notificationModel::create([
                    'senderID' => $landlord->landlordID,
                    'senderType' => 'landlord',
                    'receiverID' => $landlord->landlordID,
                    'receiverType' => 'landlord',
                    'title' => 'Dorm Updated Successfully',
                    'message' => $notificationMessage,
                    'isRead' => false,
                    'readAt' => null,
                ]);

                // I-broadcast ang notification para ma-notify ang frontend in real time.
                broadcast(new \App\Events\NewNotificationEvent($notifications));

                $message = "DormDash: Your dorm '{$dorm->dormName}' has been successfully updated. Check your DormDash account for details.";

                // Ipadala ang SMS notification sa landlord.
                try {
                    app(IprogSMSService::class)->sendSMS(
                        $landlord->phoneNumber,
                        $message
                    );
                } catch (\Throwable $e) {
                    // I-log ang SMS error kung dili madala ang mensahe.
                    Log::error('Update dorm SMS failed: ' . $e->getMessage());
                }

                // Ipadala ang email notification sa landlord.
                try {
                    Mail::raw($message, function ($mail) use ($landlord) {
                        $mail->to($landlord->email)
                            ->subject('DormDash - Dorm Updated Successfully');
                    });
                } catch (\Throwable $e) {
                    // I-log ang email error kung mapakyas ang pagpadala.
                    Log::error('Update dorm email failed: ' . $e->getMessage());
                }
            }

            // Ibalik ang success response human ma-update ang dorm.
            return response()->json([
                'status' => 'success',
                'message' => 'Dorm updated successfully!',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Ibalik ang validation errors sa frontend.
            return response()->json([
                'status' => 'error',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            // Ibalik ang error details kung mapakyas ang pag-update.
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating dorm.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // I-delete ang dormitory nga iya sa naka-login nga landlord.
    public function DeleteDorm($id)
    {
        $landlordId = session('landlord_id');

        // Siguraduhon nga adunay active landlord session.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }

        // Pangitaon ang dorm gamit ang ID ug landlord ID.
        $dorm = dormModel::where('dormID', $id)
            ->where('fklandlordID', $landlordId)
            ->first();

        // Dili mopadayon kung walay dorm nga nakit-an.
        if (!$dorm) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dorm not found.'
            ], 404);
        }

        // I-save una ang dorm name kay gamiton kini sa notification human sa deletion.
        $dormName = $dorm->dormName;

        // I-delete ang dorm record gikan sa database.
        $dorm->delete();

        // Kuhaon ang landlord details para sa notification.
        $landlord = \App\Models\landlord\landlordModel::where(
            'landlordID',
            $landlordId
        )->first();

        if ($landlord) {
            // Maghimo og mensahe nga nagpahibalo nga na-delete na ang dorm.
            $notificationMessage = "Your dorm '{$dormName}' has been successfully deleted from DormDash.";

            // I-save ang notification sa database.
            $notifications = notificationModel::create([
                'senderID' => $landlord->landlordID,
                'senderType' => 'landlord',
                'receiverID' => $landlord->landlordID,
                'receiverType' => 'landlord',
                'title' => 'Dorm Deleted Successfully',
                'message' => $notificationMessage,
                'isRead' => false,
                'readAt' => null,
            ]);

            // I-broadcast ang notification para ma-update ang real-time listeners.
            broadcast(new \App\Events\NewNotificationEvent($notifications));

            $message = "DormDash: Your dorm '{$dormName}' has been successfully deleted from your account.";

            // Ipadala ang SMS confirmation sa landlord.
            try {
                app(IprogSMSService::class)->sendSMS(
                    $landlord->phoneNumber,
                    $message
                );
            } catch (\Throwable $e) {
                // I-log ang SMS error kung mapakyas ang pagpadala.
                Log::error('Delete dorm SMS failed: ' . $e->getMessage());
            }

            // Ipadala ang email confirmation sa landlord.
            try {
                Mail::raw($message, function ($mail) use ($landlord) {
                    $mail->to($landlord->email)
                        ->subject('DormDash - Dorm Deleted Successfully');
                });
            } catch (\Throwable $e) {
                // I-log ang email error kung mapakyas ang pagpadala.
                Log::error('Delete dorm email failed: ' . $e->getMessage());
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Dorm deleted successfully!',
        ]);
    }

    // Kuhaon ang kompleto nga detalye sa usa ka dormitory.
    public function ViewDorm($id)
    {
        $landlordId = session('landlord_id');

        // Siguraduhon nga naka-login ang landlord.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }

        // Kuhaon ang dorm uban sa iyang amenities, images, rules ug tenant reviews.
        $dorm = dormModel::with(['amenities','images','rulesAndPolicy','reviews.tenant'])
            ->where('dormID', $id)
            ->where('fklandlordID', $landlordId)
            ->first();

        // Ibalik ang 404 kung dili makita ang dorm.
        if (!$dorm) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dorm not found.'
            ], 404);
        }

        // Ihap ang total reviews nga nadawat sa dorm.
        $totalReviews = $dorm->reviews->count();

        // I-format ang matag review aron magamit sa frontend.
        $reviews = $dorm->reviews->map(function ($review) {
            // I-convert ang numerical rating ngadto sa star symbols.
            $stars = str_repeat('⭐', (int)$review->rating);

            return [
                'id' => $review->id,
                'rating' => $review->rating,
                'stars' => $stars,
                'comment' => $review->review,
                'created_at' => $review->created_at->format('F d, Y h:i A'),
                'firstname' => $review->tenant->firstname ?? 'Anonymous',
                'lastname' => $review->tenant->lastname ?? '',
                'profileImage' => $review->tenant->pictureID ?? null,
            ];
        });

        // Ibalik ang dorm details, total reviews ug formatted reviews.
        return response()->json([
            'status' => 'success',
            'dorm' => $dorm,
            'total_reviews' => $totalReviews,
            'reviews' => $reviews
        ]);
    }

    // Kuhaon ang listahan sa tanang dormitories sa naka-login nga landlord.
    public function ListDorms()
    {
        $landlordId = session('landlord_id');

        // Dili tugotan ang request kung walay landlord session.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }

        // Kuhaon ang dormitories nga iya sa landlord ug i-paginate ang resulta.
        $dorms = dormModel::where('fklandlordID', $landlordId)->paginate(2);

        return response()->json([
            'status' => 'success',
            'dorms' => $dorms,
            'landlord_id' => $landlordId
        ]);
    }

    // Magdugang og bag-ong amenity sa usa ka dormitory.
    public function AddAmenities(Request $request)
    {
        $landlordId = session('landlord_id');

        // Siguraduhon nga naka-login ang landlord.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }

        // I-validate ang amenity name ug dorm ID.
        $validated = $request->validate([
            'amenities' => 'required|string|max:255',
            'dorm_id' => 'required|integer',
        ]);

        try {
            $amenityName = trim($validated['amenities']);
            $dormId = $validated['dorm_id'];

            // Susiha kung naa na ba kini nga amenity sa global amenities table.
            $existingAmenity = aminitiesModel::where('aminityName', $amenityName)->first();

            if ($existingAmenity) {
                // Susiha kung naka-link na ba ang existing amenity sa maong dorm.
                $exists = dormaminitiesModel::where('fkdormID', $dormId)
                    ->where('id', $existingAmenity->id)
                    ->exists();

                // Dili tugotan ang pagdugang kung naa na kini sa dorm.
                if ($exists) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'This amenity already exists for this dormitory.'
                    ], 400);
                }
            }

            // Gamiton ang existing amenity o maghimo og bag-ong record kung wala pa.
            $amenity = aminitiesModel::firstOrCreate(
                ['aminityName' => $amenityName]
            );

            // I-link ang amenity ngadto sa dorm gamit ang pivot table.
            dormaminitiesModel::create([
                'fkdormID' => $dormId,
                'fkaminityID' => $amenity->id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Amenity added successfully!',
            ]);

        } catch (\Exception $e) {
            // I-log ang error aron mas dali mahibaw-an ang hinungdan sa problema.
            Log::error('Error adding amenities: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Error adding amenities.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Tangtangon ang usa ka amenity gikan sa dormitory.
    public function DeleteAmenities($pivotId)
    {
        $landlordId = session('landlord_id');

        // Siguraduhon nga naka-login ang landlord.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Please log in.'
            ], 403);
        }

        // Pangitaon ang pivot record nga nag-link sa dorm ug amenity.
        $pivot = dormaminitiesModel::find($pivotId);

        if (!$pivot) {
            return response()->json([
                'status' => 'error',
                'message' => 'Amenity link not found.'
            ], 404);
        }

        // Susiha kung ang dorm nga adunay maong amenity iya sa current landlord.
        $ownsDorm = dormModel::where('dormID', $pivot->fkdormID)
            ->where('fklandlordID', $landlordId)
            ->exists();

        // Dili tugotan ang deletion kung dili iya sa landlord ang dorm.
        if (!$ownsDorm) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized to delete this amenity.'
            ], 403);
        }

        // I-delete ang pivot record aron matangtang ang link sa amenity ug dorm.
        $pivot->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Amenity removed from dorm.'
        ]);
    }

    // Magdugang og rules ug policies sa usa ka dormitory.
    public function addRulesAndPolicy(Request $request)
    {
        $landlordId = session('landlord_id');

        // Siguraduhon nga naka-login ang landlord.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }

        // I-validate ang rule name ug dorm ID.
        $validated = $request->validate([
            'rules' => 'required|string|max:255',
            'dorm_id' => 'required|integer',
        ]);

        try {
            $rules = trim($validated['rules']);
            $dormId = $validated['dorm_id'];

            // Susiha kung naa na ba kini nga rule sa global rules table.
            $existingRule = rulesModel::where('rulesName', $rules)->first();

            // Kung wala pa, maghimo og bag-ong rule record.
            if (!$existingRule) {
                $existingRule = rulesModel::create([
                    'rulesName' => $rules
                ]);
            }

            // Susiha kung naka-link na ba kini nga rule sa maong dorm.
            $alreadyLinked = dormrulesModel::where('fkdormID', $dormId)
                ->where('fkruleID', $existingRule->id)
                ->exists();

            // Dili tugotan ang duplicate nga rule assignment.
            if ($alreadyLinked) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This rule is already linked to this dormitory.'
                ], 400);
            }

            // I-link ang rule ngadto sa dorm gamit ang pivot table.
            dormrulesModel::create([
                'fkdormID' => $dormId,
                'fkruleID' => $existingRule->id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Rules added successfully!',
            ]);

        } catch (\Exception $e) {
            // I-log ang error kung mapakyas ang pagdugang sa rules.
            Log::error('Error adding rules: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Error adding rules.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Tangtangon ang rule o policy nga naka-link sa usa ka dormitory.
    public function deleteRulesAndPolicies($pivotId)
    {
        $landlordId = session('landlord_id');

        // Siguraduhon nga naka-login ang landlord.
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Please log in.'
            ], 403);
        }

        // Pangitaon ang pivot record nga nag-link sa dorm ug rule.
        $pivot = dormrulesModel::find($pivotId);

        if (!$pivot) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rule link not found.'
            ], 404);
        }

        // Susiha kung ang dorm nga adunay maong rule iya sa current landlord.
        $ownsDorm = dormModel::where('dormID', $pivot->fkdormID)
            ->where('fklandlordID', $landlordId)
            ->exists();

        // Dili tugotan ang deletion kung dili iya sa landlord ang dorm.
        if (!$ownsDorm) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized to delete this rule.'
            ], 403);
        }

        // I-delete ang pivot record aron matangtang ang rule assignment.
        $pivot->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Rule removed from dorm.'
        ]);
    }

    // Mag-upload og tulo ka images ug i-save kini isip dorm images.
    public function dormImages(Request $request)
    {
        // I-validate ang tulo ka image files ug dorm ID.
        $request->validate([
            'roomImage1File' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'roomImage2File' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'roomImage3File' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'room_id' => 'required|Integer'
        ]);

        // Ibutang ang uploaded images sulod sa public storage folder.
        if ($request->hasFile('roomImage1File')) {
            // I-save ang main image ug kuhaon ang public URL niini.
            $image1 = $request->file('roomImage1File');
            $image1Name = time() . '_1.' . $image1->getClientOriginalExtension();
            $image1Path = $image1->storeAs('public/uploads/roomImages', $image1Name);
            $mainImageUrl = asset('storage/uploads/roomImages/' . $image1Name);
        } else {
            $mainImageUrl = null;
        }

        if ($request->hasFile('roomImage2File')) {
            // I-save ang secondary image ug kuhaon ang public URL niini.
            $image2 = $request->file('roomImage2File');
            $image2Name = time() . '_2.' . $image2->getClientOriginalExtension();
            $image2Path = $image2->storeAs('public/uploads/roomImages', $image2Name);
            $secondImageUrl = asset('storage/uploads/roomImages/' . $image2Name);
        } else {
            $secondImageUrl = null;
        }

        if ($request->hasFile('roomImage3File')) {
            // I-save ang third image ug kuhaon ang public URL niini.
            $image3 = $request->file('roomImage3File');
            $image3Name = time() . '_3.' . $image3->getClientOriginalExtension();
            $image3Path = $image3->storeAs('public/uploads/roomImages', $image3Name);
            $thirdImageUrl = asset('storage/uploads/roomImages/' . $image3Name);
        } else {
            $thirdImageUrl = null;
        }

        // I-save ang tulo ka image URLs ug i-connect sa dorm ID.
        $roomImage = new dormimagesModel();
        $roomImage->fkdormID = $request->room_id;
        $roomImage->mainImage = $mainImageUrl;
        $roomImage->secondaryImage = $secondImageUrl;
        $roomImage->thirdImage = $thirdImageUrl;
        $roomImage->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Images uploaded successfully!',
        ]);
    }

    // I-validate ang main image kung adunay bag-ong file nga gi-upload.
    public function editmainImage(Request $request)
    {
        try {
            // Dili kinahanglan i-validate kung walay gi-upload nga image.
            if ($request->hasFile('roomImage1File')) {
                $validated = $request->validate([
                    'roomImage1File' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                ], [
                    'roomImage1File.image' => 'The uploaded file must be an image.',
                    'roomImage1File.mimes' => 'Only jpeg, png, jpg, gif, or svg formats are allowed.',
                    'roomImage1File.max' => 'The image must not exceed 2MB in size.',
                ]);
            }

            return response()->json([
                'status' => 'success'
            ]);
        }
        catch(\Illuminate\Validation\ValidationException $e) {
            // Ibalik ang validation errors kung invalid ang uploaded image.
            return response()->json([
                'status' => 'error',
                'message' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            // Ibalik ang server error kung mapakyas ang validation.
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // I-validate ang secondary image kung adunay bag-ong file nga gi-upload.
    public function editsecondaryImage(Request $request)
    {
        try {
            // I-validate lamang kung adunay gipili nga secondary image.
            if ($request->hasFile('roomImage2File')) {
                $validated = $request->validate([
                    'roomImage2File' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                ], [
                    'roomImage2File.image' => 'The uploaded file must be an image.',
                    'roomImage2File.mimes' => 'Only jpeg, png, jpg, gif, or svg formats are allowed.',
                    'roomImage2File.max' => 'The image must not exceed 2MB in size.',
                ]);
            }

            return response()->json([
                'status' => 'success'
            ]);
        }
        catch(\Illuminate\Validation\ValidationException $e) {
            // Ibalik ang validation errors sa frontend.
            return response()->json([
                'status' => 'error',
                'message' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            // Ibalik ang server error kung adunay problema.
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // I-update ang existing dorm images base sa images nga gi-upload.
    public function imageUpdated(Request $request, $id)
    {
        try {
            // I-validate ang optional image files ug required dorm ID.
            $validated = $request->validate([
                'roomImage1File' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'roomImage2File' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'roomImage3File' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'dorm_id' => 'required|integer'
            ]);

            // Pangitaon ang image record base sa image ID ug dorm ID.
            $roomImage = dormimagesModel::where('imagesID', $id)
                ->where('fkdormID', $request->dorm_id)
                ->first();

            // Ibalik ang 404 kung walay image record nga nakit-an.
            if (!$roomImage) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Image record not found.'
                ], 404);
            }

            // Ilisan lamang ang main image kung adunay bag-ong gi-upload.
            if ($request->hasFile('roomImage1File')) {
                $image1Name = time() . '_1.' . $request->file('roomImage1File')->getClientOriginalExtension();
                $request->file('roomImage1File')->storeAs('public/uploads/roomImages', $image1Name);
                $roomImage->mainImage = asset('storage/uploads/roomImages/' . $image1Name);
            }

            // Ilisan lamang ang secondary image kung adunay bag-ong gi-upload.
            if ($request->hasFile('roomImage2File')) {
                $image2Name = time() . '_2.' . $request->file('roomImage2File')->getClientOriginalExtension();
                $request->file('roomImage2File')->storeAs('public/uploads/roomImages', $image2Name);
                $roomImage->secondaryImage = asset('storage/uploads/roomImages/' . $image2Name);
            }

            // Ilisan lamang ang third image kung adunay bag-ong gi-upload.
            if ($request->hasFile('roomImage3File')) {
                $image3Name = time() . '_3.' . $request->file('roomImage3File')->getClientOriginalExtension();
                $request->file('roomImage3File')->storeAs('public/uploads/roomImages', $image3Name);
                $roomImage->thirdImage = asset('storage/uploads/roomImages/' . $image3Name);
            }

            // I-update ang dorm ID ug i-save ang updated image record.
            $roomImage->fkdormID = $request->dorm_id;
            $roomImage->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Images updated successfully!',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Ibalik ang validation errors kung adunay invalid nga input.
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        }
    }
}
