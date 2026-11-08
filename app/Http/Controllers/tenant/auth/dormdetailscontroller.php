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
    // Ipakita ang dorm details page sa tenant.
    public function dormDetailsIndex($dormitory_id, $tenant_id)
    {
        // Kuhaon ang tenant ID nga naka-login sa session.
        $sessionTenant_id = session('tenant_id');

        // Kuhaon ang pinakabag-ong lima ka notifications sa tenant.
        $notifications = notificationModel::where('receiverID', $sessionTenant_id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Ihap ang wala pa mabasa nga notifications.
        $unreadCount = notificationModel::where('receiverID', $tenant_id)
            ->where('isRead', false)
            ->count();

        // Ibalik sa login page kung walay naka-login nga tenant.
        if (!$sessionTenant_id) {
            return redirect()->route('tenant-login')->with('error', 'Please log in as a landlord.');
        }

        // Siguraduhon nga ang tenant ID sa URL parehas sa naka-login nga tenant.
        if ($tenant_id !== $sessionTenant_id) {
            return redirect()->route('tenant-login')->with('error', 'Unauthorized access.');
        }

        // Pangitaon ang dorm record gamit ang dormitory ID.
        $dorm = dormModel::find($dormitory_id);

        // Dugangan og usa ang view count sa dorm kada pagbisita.
        $dorm->increment('views');

        // Pangitaon ang tenant record sa database.
        $tenant = tenantModel::find($tenant_id);

        // Ibalik sa login page kung walay nakit-an nga tenant.
        if (!$tenant) {
            return redirect()->route('tenant-login')->with('error', 'Landlord not found.');
        }

        // Title nga gamiton sa dorm details page.
        $title = 'Tenant Dorm Details - DormDash';

        // I-load ang dorm details view uban sa tenant ug notification information.
        return view('tenant.auth.roomdetails', [
            'title' => $title,
            'dormitory_id' => $dormitory_id,
            'tenant_id' => $tenant_id,
            'tenant' => $tenant,
            'cssPath' => asset('css/tenantpage/auth/roomdetails.css'),
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    // Kuhaon ang kompleto nga detalye sa usa ka dorm.
    public function ViewDorms(Request $request)
    {
        // Kuhaon ang dormitory ID gikan sa request query.
        $dormitory_id = $request->query('dormitory_id');

        // Pangitaon ang dorm ug i-load ang mga related information niini.
        $dorm = dormModel::with([
            'amenities',
            'images',
            'rulesAndPolicy',
            'landlord',

            // Kuhaon lamang ang mga room nga available pa.
            'rooms' => function ($query) {
                $query->where('availability', 'Available');
            }
        ])

        // Ihap ang available ug occupied rooms sa dorm.
        ->withCount([

            // Ihap ang mga room nga available pa.
            'rooms as totalRooms' => function ($query) {
                $query->where('availability', 'Available');
            },

            // Ihap ang mga room nga occupied na.
            'rooms as totalCapacity' => function ($query) {
                $query->where('availability', 'Occupied');
            }
        ])

        // I-filter ang dorm base sa dormitory ID.
        ->where('dormID', $dormitory_id)
        ->first();

        // Ibalik ang 404 error kung walay nakit-an nga dorm.
        if (!$dorm) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dorm not found',
            ], 404);
        }

        // Ibalik ang dorm ug landlord information isip JSON response.
        return response()->json([
            'status' => 'success',
            'dorm' => $dorm,
            'landlord' => $dorm->landlord,
        ]);
    }

    // I-validate ang personal information nga gisulod sa tenant.
    public function tenantInformation(Request $request)
    {
        // Susihon kung sakto ug kompleto ang gipasa nga tenant information.
        $validatedData = $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'contactInfo' => [
                'required',
                'string',
                'max:255',
                'regex:/^(09|\\+639)\d{9}$/'
            ],
            'age' => 'required|integer|min:15|max:60',
            'sex' => 'required|in:Male,Female',
            'email' => 'required|email|max:255',
        ], [
            // Custom error messages kung naay kulang o sayop nga input.
            'firstname.required' => 'Please enter your first name.',
            'lastname.required' => 'Please enter your last name.',
            'contactInfo.required' => 'We need your contact information.',
            'contactInfo.regex' => 'Please enter a valid Philippine mobile number (e.g., 09XXXXXXXXX or +639XXXXXXXXX).',
            'age.required' => 'Please enter your age.',
            'age.integer' => 'Age should be a valid number.',
            'age.min' => 'You must be at least 15 years old to register.',
            'age.max' => 'You must be 60 years old or younger to register.',
            'sex.required' => 'Please select your gender.',
            'sex.in' => 'Gender must be either Male or Female.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'That doesn’t look like a valid email address.',
        ]);

        try {
            // Ibalik ang success response kung nakapasar sa validation.
            return response()->json(['status' => 'success']);

        } catch (\Illuminate\Validation\ValidationException $e) {

            // Ibalik ang validation errors kung naay invalid nga impormasyon.
            return response()->json([
                'status' => 'error',
                'message' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {

            // Ibalik ang error message kung adunay laing problema.
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // Modawat ug mo-upload sa tenant ID picture.
    public function uploadTenantId(Request $request)
    {
        // I-validate ang uploaded tenant picture.
        $validatedData = $request->validate([
            'tenant_picture' => 'required|image|mimes:jpg,jpeg,png|max:2048'
        ], [
            // Custom error messages para sa tenant picture validation.
            'tenant_picture.required' => 'Please upload a tenant picture.',
            'tenant_picture.image' => 'The uploaded file must be an image.',
            'tenant_picture.mimes' => 'The image must be a file of type: jpg, jpeg, png.',
            'tenant_picture.max' => 'The image must not be larger than 2MB.',
        ]);

        // Susihon kung adunay gi-upload nga tenant picture.
        if ($request->hasFile('tenant_picture')) {

            // Kuhaon ang uploaded image file.
            $image1 = $request->file('tenant_picture');

            // Maghimo og filename gamit ang current timestamp ug file extension.
            $image1Name = time() . '_1.' . $image1->getClientOriginalExtension();

            // I-save ang image sulod sa public storage folder.
            $image1Path = $image1->storeAs('public/uploads/roomImages', $image1Name);

            // Kuhaon ang accessible URL sa uploaded image.
            $mainImageUrl = asset('storage/uploads/roomImages/' . $image1Name);
        }

        try {
            // Ibalik ang success response uban sa image URL.
            return response()->json([
                'status' => 'success',
                'image_url' => $mainImageUrl,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {

            // Ibalik ang validation errors kung mapakyas ang validation.
            return response()->json([
                'status' => 'error',
                'message' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {

            // Ibalik ang error message kung adunay problema sa image upload.
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // Kuhaon ang review statistics sa usa ka dorm.
    public function reviewStats($dormID)
    {
        // Pangitaon ang tanang reviews nga konektado sa dorm ID.
        $query = reviewandratingModel::where('fkdormID', $dormID);

        // Ihap ang kinatibuk-ang gidaghanon sa reviews.
        $total = $query->count();

        // Kwentahon ang average rating sa dorm.
        $averageRating = $query->avg('rating');

        // I-convert ang average rating ngadto sa percentage nga base sa 5 ka stars.
        $percentage = $averageRating ? round(($averageRating / 5) * 100, 2) : 0;

        // Ibalik ang total reviewers ug average rating percentage.
        return response()->json([
            'total_reviewers' => $total,
            'average_percentage' => $percentage
        ]);
    }

    // Kuhaon ang dorm information nga gamiton sa AI feature.
    public function getdormAskAI($id)
    {
        // Pangitaon ang dorm uban sa amenities, rules, images, rooms, ug landlord.
        $dorm = dormModel::with([
            'amenities',
            'rulesAndPolicy',
            'images',
            'rooms',
            'landlord'
        ])->find($id);

        // Ibalik ang 404 error kung wala makit-i ang dormitory.
        if (!$dorm) {
            return response()->json([
                'success' => false,
                'message' => 'Dormitory not found'
            ], 404);
        }

        // Ibalik ang kompleto nga dorm information para magamit sa AI.
        return response()->json([
            'status' => 'success',
            'data' => $dorm
        ], 200);
    }

    // Modawat sa pangutana sa tenant ug ipadala kini sa AI server.
    public function askAI(Request $request)
    {
        // Siguraduhon nga naay dorm ID ug pangutana nga gipasa.
        $request->validate([
            'dormID' => 'required',
            'question' => 'required|string',
        ]);

        // Kuhaon ang dorm ID ug question gikan sa request.
        $dormId = $request->input('dormID');
        $question = $request->input('question');

        // URL sa Flask AI API nga modawat sa tenant question.
        $apiUrl = "https://pythonaideploy-production.up.railway.app/ask-ai/" . $dormId;

        try {
            // Ipadala ang pangutana ngadto sa AI server gamit ang POST request.
            $response = Http::timeout(60)->post($apiUrl, [
                'question' => $question,
            ]);

            // Susihon kung successful ang tubag gikan sa AI server.
            if ($response->successful()) {

                // I-convert ang AI server response ngadto sa JSON data.
                $result = $response->json();

                // Ibalik ang AI answer ug related dorm information.
                return response()->json([
                    'success' => true,
                    'answer' => $result['answer'] ?? 'No answer found',
                    'dorm' => $result['dorm'] ?? null,
                    'rooms' => $result['rooms'] ?? [],
                    'images' => $result['images'] ?? null,
                ]);
            }

            // I-save sa Laravel log ang detalye kung mapakyas ang AI API request.
            Log::error('Flask AI API Error', [
                'url' => $apiUrl,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            // Ibalik ang error response kung unsuccessful ang AI server response.
            return response()->json([
                'success' => false,
                'message' => 'AI request failed',
                'status' => $response->status(),
                'error' => $response->body(),
            ], 502);

        } catch (\Exception $e) {

            // I-save sa log ang connection error o exception gikan sa AI request.
            Log::error('Laravel AI Error', [
                'message' => $e->getMessage(),
                'url' => $apiUrl,
            ]);

            // Ibalik ang error response kung dili makakonekta sa AI server.
            return response()->json([
                'success' => false,
                'message' => 'Unable to connect to AI server.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Kuhaon ang detalye sa usa ka room.
    public function roomDetails($room_id)
    {
        // Pangitaon ang room gamit ang room ID ug i-load ang iyang features.
        $roomDetail = roomModel::with('features')
            ->where('roomID', $room_id)
            ->first();

        // Ibalik ang room details isip JSON response.
        return response()->json([
            'success' => true,
            'roomDetail' => $roomDetail
        ]);
    }

    // Kuhaon ang personal information sa tenant gamit ang tenant ID.
    public function filluptenant(Request $request)
    {
        // Kuhaon ang tenant ID gikan sa request.
        $tenant_id = $request->input('tenant_id');

        // Pangitaon ang tenant record sa database.
        $tenant = tenantModel::find($tenant_id);

        // Ibalik ang 404 error kung walay nakit-an nga tenant.
        if (!$tenant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tenant not found',
            ], 404);
        }

        // Ibalik ang tenant information aron magamit sa form.
        return response()->json([
            'status' => 'success',
            'tenant' => $tenant,
        ]);
    }
}

