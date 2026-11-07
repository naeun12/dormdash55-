<?php

namespace App\Http\Controllers\tenant\auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\landlord\dormModel; 
use App\Models\landlord\tenantscreeningModel; 
use Illuminate\Support\Facades\Http;
use App\Models\landlord\roomModel;
use App\Models\tenant\tenantModel;
use App\Models\notificationModel;
use App\Models\reviewandratingModel;
use Illuminate\Support\Facades\Log;
class dormdetailscontroller extends Controller
{
    public function dormDetailsIndex($dormitory_id,$tenant_id)
        {
            $sessionTenant_id = session('tenant_id');
             $notifications = notificationModel::where('receiverID', $sessionTenant_id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
            $unreadCount = notificationModel::where('receiverID', $tenant_id)
            ->where('isRead', false)
            ->count();
            if (!$sessionTenant_id) {
                return redirect()->route('tenant-login')->with('error', 'Please log in as a landlord.');
            }
        
            if ($tenant_id !== $sessionTenant_id) {
                return redirect()->route('tenant-login')->with('error', 'Unauthorized access.');
            }
                $dorm = dormModel::find($dormitory_id);

                $dorm->increment('views');

        
            $tenant = tenantModel::find($tenant_id);
            if (!$tenant) {
                return redirect()->route('tenant-login')->with('error', 'Landlord not found.');
            }
            $title = 'Tenant Dorm Details - DormDash';
            return view('tenant.auth.roomdetails',['title' => $title,
            'dormitory_id' => $dormitory_id,
            'tenant_id' => $tenant_id,
            'tenant' => $tenant,
            'cssPath' => asset('css/tenantpage/auth/roomdetails.css'),
        'notifications' => $notifications,
             'unread_count' => $unreadCount,]);
        }
        public function ViewDorms(Request $request)
{
    $dormitory_id = $request->query('dormitory_id');

    $dorm = dormModel::with([
        'amenities',
        'images',
        'rulesAndPolicy',
        'landlord',
        // Only available rooms
        'rooms' => function ($query) {
            $query->where('availability', 'Available');
        }
    ])
    ->withCount([
        // Count available rooms
        'rooms as totalRooms' => function ($query) {
            $query->where('availability', 'Available');
        },
        // Count rooms currently occupied (tenants residing)
        'rooms as totalCapacity' => function ($query) {
            $query->where('availability', 'Occupied');
        }
    ])
    ->where('dormID', $dormitory_id)
    ->first();

    if (!$dorm) {
        return response()->json([
            'status' => 'error',
            'message' => 'Dorm not found',
        ], 404);
    }

    return response()->json([
        'status' => 'success',
        'dorm' => $dorm,
        'landlord' => $dorm->landlord,
    ]);
}

           public function tenantInformation(Request $request)
{
    $validatedData = $request->validate([
        'firstname'     => 'required|string|max:255',
        'lastname'      => 'required|string|max:255',
        'contactInfo'   => [
            'required',
            'string',
            'max:255',
            'regex:/^(09|\+639)\d{9}$/'
        ],
        'age'           => 'required|integer|min:15|max:60',
        'sex'           => 'required|in:Male,Female',
        'email'         => 'required|email|max:255',
    ], [
        'firstname.required'     => 'Please enter your first name.',
        'lastname.required'      => 'Please enter your last name.',
        'contactInfo.required'   => 'We need your contact information.',
        'contactInfo.regex'      => 'Please enter a valid Philippine mobile number (e.g., 09XXXXXXXXX or +639XXXXXXXXX).',
        'age.required'           => 'Please enter your age.',
        'age.integer'            => 'Age should be a valid number.',
        'age.min'                => 'You must be at least 15 years old to register.',
        'age.max'                => 'You must be 60 years old or younger to register.',
        'sex.required'           => 'Please select your gender.',
        'sex.in'                 => 'Gender must be either Male or Female.',
        'email.required'         => 'Please enter your email address.',
        'email.email'            => 'That doesn’t look like a valid email address.',
    ]);

    try {
        return response()->json(['status' => 'success']);
    } catch(\Illuminate\Validation\ValidationException $e) {
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
        public function uploadTenantId(Request $request)
        {
            $validatedData = $request->validate([  
                 'tenant_picture' => 'required|image|mimes:jpg,jpeg,png|max:2048'
            ], [
                'tenant_picture.required' => 'Please upload a tenant picture.',
                'tenant_picture.image'    => 'The uploaded file must be an image.',
                'tenant_picture.mimes'    => 'The image must be a file of type: jpg, jpeg, png.',
                'tenant_picture.max'      => 'The image must not be larger than 2MB.',
               
            ]);
            if ($request->hasFile('tenant_picture')) {
                $image1 = $request->file('tenant_picture');
                $image1Name = time() . '_1.' . $image1->getClientOriginalExtension();
                $image1Path = $image1->storeAs('public/uploads/roomImages', $image1Name);
                $mainImageUrl = asset('storage/uploads/roomImages/' . $image1Name);
            }
            try
            {
                return response()->json(['status' => 'success',       
                'image_url' => $mainImageUrl,
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
        public function reviewStats($dormID)
        {
            $query = reviewandratingModel::where('fkdormID',$dormID);
            $total = $query->count();
            $averageRating = $query->avg('rating');
            $percentage = $averageRating ? round(($averageRating / 5) * 100, 2) : 0;
             return response()->json([
        'total_reviewers' => $total,
        'average_percentage' => $percentage
            ]);

        }
        public function getdormAskAI($id)
        {
            $dorm = dormModel::with(['amenities', 'rulesAndPolicy', 'images', 'rooms', 'landlord'])
                    ->find($id);
                     if (!$dorm) {
            return response()->json([
                'success' => false,
                'message' => 'Dormitory not found'
            ], 404);
        }
             return response()->json([
            'status' => 'success',
            'data' => $dorm
        ], 200);
        }
   public function askAI(Request $request)
{
    $request->validate([
        'dormID' => 'required',
        'question' => 'required|string',
    ]);

    $dormId = $request->input('dormID');
    $question = $request->input('question');

    // Correct Flask API URL
    $apiUrl = "https://pythonaideploy-production.up.railway.app/ask-ai/" . $dormId;

    try {
        $response = Http::timeout(60)->post($apiUrl, [
            'question' => $question,
        ]);

        if ($response->successful()) {
            $result = $response->json();

            return response()->json([
                'success' => true,
                'answer' => $result['answer'] ?? 'No answer found',
                'dorm' => $result['dorm'] ?? null,
                'rooms' => $result['rooms'] ?? [],
                'images' => $result['images'] ?? null,
            ]);
        }

        Log::error('Flask AI API Error', [
            'url' => $apiUrl,
            'status' => $response->status(),
            'response' => $response->body(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'AI request failed',
            'status' => $response->status(),
            'error' => $response->body(),
        ], 502);

    } catch (\Exception $e) {

        Log::error('Laravel AI Error', [
            'message' => $e->getMessage(),
            'url' => $apiUrl,
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Unable to connect to AI server.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
 public function roomDetails($room_id)
    {
        $roomDetail = roomModel::with('features')
            ->where('roomID', $room_id)
            ->first();

        return response()->json([
            'success' => true,
            'roomDetail' => $roomDetail
        ]);
    }
    public function filluptenant(Request $request)
{
    $tenant_id = $request->input('tenant_id');


    $tenant = tenantModel::find($tenant_id);


    if (!$tenant) {
        return response()->json([
            'status' => 'error',
            'message' => 'Tenant not found',
        ], 404);
    }


    return response()->json([
        'status' => 'success',
        'tenant' => $tenant,
    ]);
}



}