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
public function DormManagement($landlordId)
{
    try {
        $sessionLandlordId = session('landlord_id');


        if (!$sessionLandlordId) {
            return redirect()->route('loginLandlord')->with('error', 'Please log in as a landlord.');
        }


        if ($landlordId !== $sessionLandlordId) {
            return redirect()->route('loginLandlord')->with('error', 'Unauthorized access.');
        }


        $landlord = landlordModel::find($landlordId);
        if (!$landlord) {
            return redirect()->route('loginLandlord')->with('error', 'Landlord not found.');
        }


        $notifications = notificationModel::where('receiverID', $sessionLandlordId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();


        $unreadCount = notificationModel::where('receiverID', $landlordId)
            ->where('isRead', false)
            ->count();


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
        // Log the full error to Laravel logs
        Log::error('DormManagement error: '.$e->getMessage().' in '.$e->getFile().' on line '.$e->getLine());
        // Optional: return a simple message to user
        return response()->json(['error' => 'Something went wrong. Check logs for details.'], 500);
    }
}




    public function getlandlordVerifiedStatus()
    {
        $landlordId = session('landlord_id');
        $landlord = landlordModel::where('landlordID', $landlordId)->first();
        return response()->json([
            'status' => 'success',
            'isVerified' => $landlord->isVerified,
        ]);
    }
     public function searchDorms(Request $request)
    {
        $landlordId = session('landlord_id');
   
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }
   
        $searchTerm = strtolower($request->input('search', ''));
     
       
        $dorms = dormModel::with('images')
            ->where('fklandlordID', $landlordId)
            ->where(function ($query) use ($searchTerm) {
                if (!empty($searchTerm)) {
                    $query->whereRaw('LOWER(dormName) LIKE ?', ["%$searchTerm%"]);
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate(5);
       
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
   public function filterLocations(Request $request)
   {
    $landlordId = session('landlord_id');
   
    if (!$landlordId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized action. Please log in as a landlord.'
        ], 403);
    }


    $location = strtolower($request->input('location', ''));


    $dorms = dormModel::with('images')
        ->where('fklandlordID', $landlordId)
        ->when($location !== 'all' && !empty($location), function ($query) use ($location) {
            // Use REGEXP to match only full words (not part of other words)
            $query->whereRaw("LOWER(address) REGEXP ?", ['[[:<:]]' . $location . '[[:>:]]']);
        })
        ->orderBy('created_at', 'desc')
        ->paginate(5);
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
   public function filteredAvailability(Request $request)
   {
       $landlordId = session('landlord_id');
       $availability = strtolower($request->input('availability', ''));
       $dorms = dormModel::with('images')
           ->where('fklandlordID', $landlordId)
           ->where(function ($query) use ($availability) {
               if (!empty($availability) && $availability !== 'all') {
                $query->whereRaw('LOWER(availability) = ?', [$availability]);
            }
           })
           ->orderBy('created_at', 'desc')
           ->paginate(5);
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


    public function inputFieldDorm(Request $request)
    {
        try
        {
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
           
            return response()->json([
                'status' => 'success'
            ]);
        }
        catch(\Illuminate\Validation\ValidationException $e)
        {
            return response()->json([
                'status' => 'error',
                'message' => $e->errors(),
            ], 422);
   
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function uploadmainImage(Request $request)
    {
        try
        {
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
       catch(\Illuminate\Validation\ValidationException $e)
            {
                return response()->json([
                    'status' => 'error',
                    'errors' => $e->errors(), // <-- change 'message' to 'errors'
                ], 422);
            }
        catch (\Exception $e) {
              return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function uploadsecondaryImage(Request $request)
    {
        try
        {
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
        catch(\Illuminate\Validation\ValidationException $e)
        {
            return response()->json([
                'status' => 'error',
                'errors' => $e->errors(),
            ], 422);
   
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function AddDorm(Request $request)
{
    $landlordId = session('landlord_id');


    if (!$landlordId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized action. Please log in as a landlord.'
        ], 403);
    }


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


        // Generate unique dorm ID
        do {
            $randomDormId = rand(1000, 9999);
        } while (dormModel::where('dormID', $randomDormId)->exists());


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


        // Upload Image 1
        if ($request->hasFile('roomImage1File')) {
            $image1 = $request->file('roomImage1File');
            $image1Name = time() . '_1.' . $image1->getClientOriginalExtension();
            $image1->storeAs('public/uploads/roomImages', $image1Name);
            $mainImageUrl = asset('storage/uploads/roomImages/' . $image1Name);
        } else {
            $mainImageUrl = null;
        }


        // Upload Image 2
        if ($request->hasFile('roomImage2File')) {
            $image2 = $request->file('roomImage2File');
            $image2Name = time() . '_2.' . $image2->getClientOriginalExtension();
            $image2->storeAs('public/uploads/roomImages', $image2Name);
            $secondImageUrl = asset('storage/uploads/roomImages/' . $image2Name);
        } else {
            $secondImageUrl = null;
        }


        // Upload Image 3
        if ($request->hasFile('roomImage3File')) {
            $image3 = $request->file('roomImage3File');
            $image3Name = time() . '_3.' . $image3->getClientOriginalExtension();
            $image3->storeAs('public/uploads/roomImages', $image3Name);
            $thirdImageUrl = asset('storage/uploads/roomImages/' . $image3Name);
        } else {
            $thirdImageUrl = null;
        }


        // Save Dorm Images
        $dormImage = new dormimagesModel();
        $dormImage->fkdormID = $dorm->dormID;
        $dormImage->mainImage = $mainImageUrl;
        $dormImage->secondaryImage = $secondImageUrl;
        $dormImage->thirdImage = $thirdImageUrl;
        $dormImage->save();


        // Get landlord details
        $landlord = \App\Models\landlord\landlordModel::where(
            'landlordID',
            $landlordId
        )->first();


        if ($landlord) {


            // In-app notification
            $notificationMessage = "Your dorm '{$dorm->dormName}' has been successfully added to DormDash.";


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


            broadcast(new \App\Events\NewNotificationEvent($notifications));


            $message = "DormDash: Congratulations! Your dorm '{$dorm->dormName}' has been successfully added to your account.";


            // SMS Notification
            try {
                app(IprogSMSService::class)->sendSMS(
                    $landlord->phoneNumber,
                    $message
                );
            } catch (\Throwable $e) {
                Log::error('Add dorm SMS failed: ' . $e->getMessage());
            }


            // Email Notification
            try {
                Mail::raw($message, function ($mail) use ($landlord) {
                    $mail->to($landlord->email)
                        ->subject('DormDash - Dorm Added Successfully');
                });
            } catch (\Throwable $e) {
                Log::error('Add dorm email failed: ' . $e->getMessage());
            }
        }


        return response()->json([
            'status' => 'success',
            'message' => 'Dorm added successfully!',
            'dormId' => $dorm->dormID,
        ]);


    } catch (\Illuminate\Validation\ValidationException $e) {


        return response()->json([
            'status' => 'error',
            'errors' => $e->errors(),
        ], 422);


    } catch (\Exception $e) {


        return response()->json([
            'status' => 'error',
            'message' => 'Error adding dorm.',
            'error' => $e->getMessage()
        ], 500);
    }
}
   public function UpdateDorm(Request $request, $id)
{
    try {
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


        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }


        $dorm = dormModel::where('dormID', $id)
            ->where('fklandlordID', $landlordId)
            ->first();


        if (!$dorm) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dorm not found.'
            ], 404);
        }


        // Update Dorm
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


        // Get landlord details
        $landlord = \App\Models\landlord\landlordModel::where(
            'landlordID',
            $landlordId
        )->first();


        if ($landlord) {


            // In-app Notification
            $notificationMessage = "Your dorm '{$dorm->dormName}' has been successfully updated.";


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


            broadcast(new \App\Events\NewNotificationEvent($notifications));


            $message = "DormDash: Your dorm '{$dorm->dormName}' has been successfully updated. Check your DormDash account for details.";


            // SMS Notification
            try {
                app(IprogSMSService::class)->sendSMS(
                    $landlord->phoneNumber,
                    $message
                );
            } catch (\Throwable $e) {
                Log::error('Update dorm SMS failed: ' . $e->getMessage());
            }


            // Email Notification
            try {
                Mail::raw($message, function ($mail) use ($landlord) {
                    $mail->to($landlord->email)
                        ->subject('DormDash - Dorm Updated Successfully');
                });
            } catch (\Throwable $e) {
                Log::error('Update dorm email failed: ' . $e->getMessage());
            }
        }


        return response()->json([
            'status' => 'success',
            'message' => 'Dorm updated successfully!',
        ]);


    } catch (\Illuminate\Validation\ValidationException $e) {


        return response()->json([
            'status' => 'error',
            'errors' => $e->errors(),
        ], 422);


    } catch (\Exception $e) {


        return response()->json([
            'status' => 'error',
            'message' => 'Error updating dorm.',
            'error' => $e->getMessage()
        ], 500);
    }
}


  public function DeleteDorm($id)
{
    $landlordId = session('landlord_id');


    if (!$landlordId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized action. Please log in as a landlord.'
        ], 403);
    }


    // Fetch the dorm by ID
    $dorm = dormModel::where('dormID', $id)
        ->where('fklandlordID', $landlordId)
        ->first();


    if (!$dorm) {
        return response()->json([
            'status' => 'error',
            'message' => 'Dorm not found.'
        ], 404);
    }


    // Get dorm name before deletion
    $dormName = $dorm->dormName;


    // Delete the dorm
    $dorm->delete();


    // Get landlord details
    $landlord = \App\Models\landlord\landlordModel::where(
        'landlordID',
        $landlordId
    )->first();


    if ($landlord) {


        // In-app notification
        $notificationMessage = "Your dorm '{$dormName}' has been successfully deleted from DormDash.";


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


        broadcast(new \App\Events\NewNotificationEvent($notifications));


        $message = "DormDash: Your dorm '{$dormName}' has been successfully deleted from your account.";


        // SMS Notification
        try {
            app(IprogSMSService::class)->sendSMS(
                $landlord->phoneNumber,
                $message
            );
        } catch (\Throwable $e) {
            Log::error('Delete dorm SMS failed: ' . $e->getMessage());
        }


        // Email Notification
        try {
            Mail::raw($message, function ($mail) use ($landlord) {
                $mail->to($landlord->email)
                    ->subject('DormDash - Dorm Deleted Successfully');
            });
        } catch (\Throwable $e) {
            Log::error('Delete dorm email failed: ' . $e->getMessage());
        }
    }


    return response()->json([
        'status' => 'success',
        'message' => 'Dorm deleted successfully!',
    ]);
}
   public function ViewDorm($id)
{
    $landlordId = session('landlord_id');
    if (!$landlordId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized action. Please log in as a landlord.'
        ], 403);
    }


    $dorm = dormModel::with(['amenities','images','rulesAndPolicy','reviews.tenant'])
        ->where('dormID', $id)
        ->where('fklandlordID', $landlordId)
        ->first();


    if (!$dorm) {
        return response()->json([
            'status' => 'error',
            'message' => 'Dorm not found.'
        ], 404);
    }


    $totalReviews = $dorm->reviews->count();


    $reviews = $dorm->reviews->map(function ($review) {
        $stars = str_repeat('⭐', (int)$review->rating); // convert rating to stars
        return [
            'id' => $review->id,
            'rating' => $review->rating,
            'stars' => $stars, // e.g., ⭐⭐⭐⭐
            'comment' => $review->review,
            'created_at' => $review->created_at->format('F d, Y h:i A'),
            'firstname' => $review->tenant->firstname ?? 'Anonymous',
            'lastname' => $review->tenant->lastname ?? '',
            'profileImage' => $review->tenant->pictureID ?? null,
        ];
    });


    return response()->json([
        'status' => 'success',
        'dorm' => $dorm,
        'total_reviews' => $totalReviews,
        'reviews' => $reviews
    ]);
}




    public function ListDorms()
    {
        $landlordId = session('landlord_id');


        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }


        // Get dorms by landlord
        $dorms = dormModel::where('fklandlordID', $landlordId)->paginate(2);


        return response()->json([
            'status' => 'success',
            'dorms' => $dorms,
            'landlord_id' => $landlordId
        ]);
    }
   
    public function AddAmenities(Request $request)
    {
        $landlordId = session('landlord_id');
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action. Please log in as a landlord.'
            ], 403);
        }
   
        $validated = $request->validate([
            'amenities' => 'required|string|max:255',
            'dorm_id' => 'required|integer',
        ]);
   
        try {
            $amenityName = trim($validated['amenities']);
            $dormId = $validated['dorm_id'];
   
            // First check if this amenity name already exists for this dorm
            $existingAmenity = aminitiesModel::where('aminityName', $amenityName)->first();
   
            if ($existingAmenity) {
                $exists = dormaminitiesModel::where('fkdormID', $dormId)
                    ->where('id', $existingAmenity->id)
                    ->exists();
   
                if ($exists) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'This amenity already exists for this dormitory.'
                    ], 400);
                }
            }
   
            // Create the amenity if it doesn't exist globally
            $amenity = aminitiesModel::firstOrCreate(
                ['aminityName' => $amenityName]
            );
   
            // Link amenity to the dorm
            dormaminitiesModel::create([
                'fkdormID' => $dormId,
                'fkaminityID' => $amenity->id,
            ]);
   
            return response()->json([
                'status' => 'success',
                'message' => 'Amenity added successfully!',
            ]);
   
        } catch (\Exception $e) {
            Log::error('Error adding amenities: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error adding amenities.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function DeleteAmenities($pivotId)
    {
        $landlordId = session('landlord_id');
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Please log in.'
            ], 403);
        }
   
        $pivot = dormaminitiesModel::find($pivotId); // this is the pivot record
   
        if (!$pivot) {
            return response()->json([
                'status' => 'error',
                'message' => 'Amenity link not found.'
            ], 404);
        }
   
        // Check if the dorm_id belongs to the landlord
        $ownsDorm = dormModel::where('dormID', $pivot->fkdormID)
        ->where('fklandlordID', $landlordId)
            ->exists();
   
        if (!$ownsDorm) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized to delete this amenity.'
            ], 403);
        }
   
        $pivot->delete(); // Delete the pivot record (unlink)
   
        return response()->json([
            'status' => 'success',
            'message' => 'Amenity removed from dorm.'
        ]);
    }
    public function addRulesAndPolicy(Request $request)
{
    $landlordId = session('landlord_id');
    if (!$landlordId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized action. Please log in as a landlord.'
        ], 403);
    }


    $validated = $request->validate([
        'rules' => 'required|string|max:255',
        'dorm_id' => 'required|integer',
    ]);


    try {
        $rules = trim($validated['rules']);
        $dormId = $validated['dorm_id'];


        // Check if the rule already exists for this dorm
        $existingRule = rulesModel::where('rulesName', $rules)->first();


        // If it exists globally, use it, otherwise create it
        if (!$existingRule) {
            $existingRule = rulesModel::create([
                'rulesName' => $rules
            ]);
        }


        // Check if the dorm already has this rule assigned
        $alreadyLinked = dormrulesModel::where('fkdormID', $dormId)
            ->where('fkruleID', $existingRule->id)
            ->exists();


        if ($alreadyLinked) {
            return response()->json([
                'status' => 'error',
                'message' => 'This rule is already linked to this dormitory.'
            ], 400);
        }


        // Link the rule to the dorm
        dormrulesModel::create([
            'fkdormID' => $dormId,
            'fkruleID' => $existingRule->id,
        ]);


        return response()->json([
            'status' => 'success',
            'message' => 'Rules added successfully!',
        ]);


    } catch (\Exception $e) {
        Log::error('Error adding rules: ' . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => 'Error adding rules.',
            'error' => $e->getMessage()
        ], 500);
    }
}
public function deleteRulesAndPolicies($pivotId)
    {
        $landlordId = session('landlord_id');
        if (!$landlordId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Please log in.'
            ], 403);
        }
        $pivot = dormrulesModel::find($pivotId);
        if (!$pivot) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rule link not found.'
            ], 404);
        }
   
        // Check if the dorm_id belongs to the landlord
        $ownsDorm = dormModel::where('dormID', $pivot->fkdormID)
        ->where('fklandlordID', $landlordId)
            ->exists();
   
        if (!$ownsDorm) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized to delete this rule.'
            ], 403);
        }
   
        $pivot->delete();
   
        return response()->json([
            'status' => 'success',
            'message' => 'Rule removed from dorm.'
        ]);
    }


    public function dormImages(Request $request)
    {
        $request->validate([
            'roomImage1File' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'roomImage2File' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'roomImage3File' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'room_id' => 'required|Integer'
        ]);
       // Define the folder path inside 'storage/app/public'
       if ($request->hasFile('roomImage1File')) {
        $image1 = $request->file('roomImage1File');
        $image1Name = time() . '_1.' . $image1->getClientOriginalExtension();
        $image1Path = $image1->storeAs('public/uploads/roomImages', $image1Name);
        $mainImageUrl = asset('storage/uploads/roomImages/' . $image1Name);
    } else {
        $mainImageUrl = null;
    }
   
    if ($request->hasFile('roomImage2File')) {
        $image2 = $request->file('roomImage2File');
        $image2Name = time() . '_2.' . $image2->getClientOriginalExtension();
        $image2Path = $image2->storeAs('public/uploads/roomImages', $image2Name);
        $secondImageUrl = asset('storage/uploads/roomImages/' . $image2Name);
    } else {
        $secondImageUrl = null;
    }
   
    if ($request->hasFile('roomImage3File')) {
        $image3 = $request->file('roomImage3File');
        $image3Name = time() . '_3.' . $image3->getClientOriginalExtension();
        $image3Path = $image3->storeAs('public/uploads/roomImages', $image3Name);
        $thirdImageUrl = asset('storage/uploads/roomImages/' . $image3Name);
    } else {
        $thirdImageUrl = null;
    }
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
    public function editmainImage(Request $request)
{
    try
    {
        // Only validate if file exists
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
    catch(\Illuminate\Validation\ValidationException $e)
    {
        return response()->json([
            'status' => 'error',
            'message' => $e->errors(),
        ], 422);


    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}


    public function editsecondaryImage(Request $request)
    {
        try
        {
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
        catch(\Illuminate\Validation\ValidationException $e)
        {
            return response()->json([
                'status' => 'error',
                'message' => $e->errors(),
            ], 422);
   
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function imageUpdated(Request $request, $id)
{
    try
    {
        $validated = $request->validate([
            'roomImage1File' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'roomImage2File' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'roomImage3File' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'dorm_id' => 'required|integer'
        ]);
   
        $roomImage = dormimagesModel::where('imagesID', $id)->where('fkdormID', $request->dorm_id)->first();
   
        if (!$roomImage) {
            return response()->json([
                'status' => 'error',
                'message' => 'Image record not found.'
            ], 404);
        }
   
        // Only update images if uploaded
        if ($request->hasFile('roomImage1File')) {
            $image1Name = time() . '_1.' . $request->file('roomImage1File')->getClientOriginalExtension();
            $request->file('roomImage1File')->storeAs('public/uploads/roomImages', $image1Name);
            $roomImage->mainImage = asset('storage/uploads/roomImages/' . $image1Name);
        }
   
        if ($request->hasFile('roomImage2File')) {
            $image2Name = time() . '_2.' . $request->file('roomImage2File')->getClientOriginalExtension();
            $request->file('roomImage2File')->storeAs('public/uploads/roomImages', $image2Name);
            $roomImage->secondaryImage = asset('storage/uploads/roomImages/' . $image2Name);
        }
   
        if ($request->hasFile('roomImage3File')) {
            $image3Name = time() . '_3.' . $request->file('roomImage3File')->getClientOriginalExtension();
            $request->file('roomImage3File')->storeAs('public/uploads/roomImages', $image3Name);
            $roomImage->thirdImage = asset('storage/uploads/roomImages/' . $image3Name);
        }
   
        $roomImage->fkdormID = $request->dorm_id;
   
        $roomImage->save();
   
        return response()->json([
            'status' => 'success',
            'message' => 'Images updated successfully!',
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed.',
            'errors' => $e->errors()
        ], 422);
    }
   
}


}
 


