<?php
namespace App\Http\Controllers\tenant\auth;
use App\Models\landlord\landlordModel;
use App\Models\landlord\roomModel;
use App\Models\landlord\dormModel;
use App\Models\tenant\tenantModel;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\notificationModel;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Http;
class dormitories extends Controller
{
    public function dormitoriesListing($tenant_id)
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
   
        $tenant = tenantModel::find($tenant_id);
        if (!$tenant) {
            return redirect()->route('tenant-login')->with('error', 'Landlord not found.');
        }
        return view('tenant.auth.dormitories',['title' => 'Dormitories  - DormDash',
        'tenant_id',$tenant,'cssPath' => asset('css/tenantpage/auth/dormitory.css')
        ,'notifications' => $notifications,
             'unread_count' => $unreadCount,
    ]);




    }
   
public function Listdorms()
{
    $dorms = dormModel::with(['amenities', 'images'])
        ->where('address', 'LIKE', '%Surigao%')
        ->paginate(10);


    return response()->json([
        'status' => 'success',
        'dorms' => $dorms,
        'total_dorms' => $dorms->total(),
        'surigao_dorms' => $dorms->total(),
    ]);
}


    public function selectedCityDorms(Request $request)
{
    $city = $request->input('city', 'Surigao');


    $dorms = dormModel::with(['amenities', 'images'])
        ->where('address', 'LIKE', '%' . $city . '%')
        ->paginate(10);


    return response()->json([
        'status' => 'success',
        'total_dorms' => $dorms->total(),
        'dorms' => $dorms,
    ]);
}




// Optional helper to handle common city aliases


public function fetchAmenities()
{
    $amenities = DB::table('amenities')->get();




    return response()->json([
        'status' => 'success',
        'amenities' => $amenities,
    ]);
}
public function getAmenities(Request $request)
{
    $amenities = $request->input('amenities', []); // array of amenity IDs




    $dormsQuery = dormModel::with(['amenities', 'images']);




    if (!empty($amenities)) {
        $dormsQuery->whereHas('amenities', function($q) use ($amenities) {
            $q->whereIn('amenities.id', $amenities); // <-- fixed table name here
        }, '=', count($amenities)); // ensures dorm has all selected amenities
    }




    $dorms = $dormsQuery->paginate(10);




    return response()->json([
        'status' => 'success',
        'dorms' => $dorms,
    ]);
}
public function getRate(Request $request)
{
    $rate = $request->input('rating');




    $dormsQuery = dormModel::with(['images', 'reviews']);




    if ($rate !== null && is_numeric($rate)) {
        $dormsQuery->whereHas('reviews', fn($q) => $q->where('rating', '>=', $rate));
    }




    $dorms = $dormsQuery->paginate(10)->through(function($dorm) {
    $avgRating = $dorm->reviews->avg('rating') ?? 0;
    $dorm->rating_percentage = round(($avgRating / 5) * 100, 2);




    unset($dorm->reviews);




    return $dorm;
});
    return response()->json([
        'status' => 'success',
        'dorms' => $dorms,
    ]);
}








public function getSortByDate(Request $request)
{
    $sortBy = $request->input('sortBy'); // from frontend




    $dormsQuery = dormModel::with('images');




    if ($sortBy === 'new-old') {
        $dormsQuery->orderBy('updated_at', 'desc');
    } elseif ($sortBy === 'old-new') {
        $dormsQuery->orderBy('updated_at', 'asc');
    }




    $dorms = $dormsQuery->paginate(10);




    return response()->json([
        'status' => 'success',
        'total_dorms' => $dorms->count(),
        'dorms' => $dorms,
    ]);
}
public function getAvailability(Request $request)
{
    $availability = $request->input('availability');
   
    $dormsQuery = dormModel::with(['rooms', 'images']);




    if ($availability && $availability !== 'all') {
        $dormsQuery->whereHas('rooms', function($q) use ($availability) {
            $q->whereRaw('LOWER(availability) = ?', [strtolower($availability)]);
        });
    }




    $dorms = $dormsQuery->paginate(10);




    return response()->json([
        'status' => 'success',
        'dorms' => $dorms,
    ]);
}


   


    protected function normalize($text)
    {
        return preg_replace('/[^a-z0-9]+/', '', strtolower($text));
    }




 




   public function searchLocations(Request $request)
{
    try {
        // Validate input
        $validated = $request->validate([
            'location' => 'required|string',
        ]);




        $keyword = strtolower(trim($validated['location']));
        $keywordNormalized = $this->normalize($keyword);




        // Base query
        $query = DB::table('dorms as d')
            ->leftJoin('dormimages as i', 'i.fkdormID', '=', 'd.dormID')
            ->select('d.*', 'i.mainImage')
            ->whereNotNull('d.latitude')
            ->whereNotNull('d.longitude');




        // Paginate before filtering
        $dormsPaginated = $query->paginate(10);




        // Apply Collection filtering to handle normalization & synonyms
        $filteredCollection = $dormsPaginated->getCollection()->filter(function ($dorm) use ($keywordNormalized) {
            $addressNormalized = preg_replace('/[^a-z0-9]+/', '', strtolower($dorm->address));
            return strpos($addressNormalized, $keywordNormalized) !== false
                  ;
        });




        // Replace the collection in the paginator so pagination metadata remains
        $dormsPaginated->setCollection($filteredCollection->values());




        return response()->json([
            'status' => 'success',
            'recommendations' => $dormsPaginated,
        ]);




    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Laravel controller error',
            'details' => $e->getMessage()
        ], 500);
    }
}
   public function priceRecommendations(Request $request)
{
    try {
        $minPrice = floatval($request->input('min_price', 0));
        $maxPrice = floatval($request->input('max_price', 999999));




        $adjustedMin = $minPrice * 0.8;
        $adjustedMax = $maxPrice * 1.2;




        // Use Eloquent with relationships
        $results = \App\Models\landlord\roomModel::with(['dorm.images'])
            ->whereBetween('price', [$adjustedMin, $adjustedMax])
            ->whereRaw('LOWER(availability) = ?', ['available'])
            ->orderBy('price', 'asc')
            ->paginate(10)
             ->through(function ($room) {
                return [
                    'dormID'        => $room->dorm->dormID ?? null,
                    'dormavailability'  => $room->dorm->availability,
                    'dormName'      => $room->dorm->dormName ?? null,
                    'occupancyType' => $room->dorm->occupancyType ?? null,
                    'address'       => $room->dorm->address ?? null,
                    'latitude'      => $room->dorm->latitude ?? null,
                    'longitude'     => $room->dorm->longitude ?? null,
                    'price'         => $room->price,
                    'genderPreference' => $room->genderPreference,
                    'roomType'      => $room->roomType,
                    'furnishing_status' => $room->furnishing_status,
                    'availability'  => $room->availability,
                    'mainImage'     => $room->dorm->images->mainImage ?? null,
                ];
            });




        return response()->json([
            'status' => 'success',
            'data' => $results,
        ]);




    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}




   public function genderRecommendations(Request $request)
{
    try {
        $validated = $request->validate([
            'occupancy_type' => 'required|string',
        ]);




        $inputType = strtolower(trim($validated['occupancy_type']));




        $query = DB::table('dorms as d')
            ->leftJoin('dormimages as i', 'i.fkdormID', '=', 'd.dormID')
            ->select('d.*', 'i.mainImage');




        // Filter sa query level para preserve pagination
        if ($inputType != 'all') {
            if ($inputType == 'male') {
                $query->whereRaw("LOWER(occupancyType) = 'male only'");
            } elseif ($inputType == 'female') {
                $query->whereRaw("LOWER(occupancyType) = 'female only'");
            } elseif ($inputType == 'mixed') {
                $query->whereRaw("LOWER(occupancyType) LIKE '%mixed%'");
            }
        }




        $dorms = $query->paginate(10); // Pagination preserved




        return response()->json([
            'status' => 'success',
            'recommendations' => $dorms, // items + pagination metadata
        ]);




    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Laravel error',
            'details' => $e->getMessage(),
        ], 500);
    }
}




    public function mostWatchedDorm($id)
    {
        $dorm = dormModel::with('images')->orderBy('views', 'desc')->get();
            return response()->json([
            'message' => 'Most watched dorm retrieved successfully.',
            'dorm' => $dorm
    ]);
    }
    public function searchDormsWithFilters(array $filters)
{
    $query = dormModel::query();




    if (!empty($filters['location'])) {
        $query->where('address', 'like', '%' . $filters['location'] . '%');
    }




    // You can join rooms table and filter by room type or price
    if (!empty($filters['type']) || !empty($filters['max_price'])) {
        $query->whereHas('rooms', function ($q) use ($filters) {
            if (!empty($filters['type'])) {
                $q->where('roomType', $filters['type']);
            }
            if (!empty($filters['max_price'])) {
                $q->where('price', '<=', $filters['max_price']);
            }
        });
    }




    $dorms = $query->with('rooms')->get();




    return $dorms;
}
public function getQuestionRecommendations(Request $request)
{
    $request->validate([
        'question' => 'required|string|max:1000',
    ]);


    try {
        $question = trim($request->input('question'));


        // Python AI API URL
        $apiUrl = 'https://pythonaideploy-production.up.railway.app/ask-ai/dormitories';


        // Send JSON request to Python API
        $response = Http::timeout(60)
            ->acceptJson()
            ->asJson()
            ->post($apiUrl, [
                'question' => $question,
            ]);


        if ($response->failed()) {
            Log::error('DormDash AI API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);


            return response()->json([
                'message' => 'AI service is currently unavailable.',
                'recommendations' => [],
            ], 502);
        }


        $data = $response->json() ?? [];


        $aiRaw = $data['message'] ?? 'Here are your dormitory recommendations.';


        // Extract AI recommendations
        $aiRecommendations = $data['recommendations'] ?? [];


        if (empty($aiRecommendations)) {
            $aiRecommendations = $data['result'] ?? [];
        }


        // Extract JSON from Markdown code blocks
        if (empty($aiRecommendations)) {
            if (preg_match('/```json\s*(.*?)\s*```/s', $aiRaw, $matches)) {
                $decoded = json_decode(trim($matches[1]), true);


                if (is_array($decoded)) {
                    $aiRecommendations = $decoded;
                }
            }
        }


        // Normalize AI recommendations
        $recommendations = [];


        if (is_array($aiRecommendations)) {
            foreach ($aiRecommendations as $dorm) {
                if (!is_array($dorm)) {
                    continue;
                }


                $rooms = [];


                foreach (($dorm['rooms'] ?? []) as $room) {
                    if (!is_array($room)) {
                        continue;
                    }


                    $features = $room['features'] ?? [];


                    if (is_string($features)) {
                        $features = array_filter(
                            array_map('trim', explode(',', $features))
                        );
                    }


                    $rooms[] = [
                        'roomNumber' => $room['roomNumber'] ?? 'N/A',
                        'type' => $room['type'] ?? $room['roomType'] ?? 'Standard',
                        'price' => $room['price'] ?? 'Contact landlord',
                        'availability' => $room['availability'] ?? 'Unknown',
                        'features' => $features,
                    ];
                }


                $recommendations[] = [
                    'dormID' => $dorm['dormID'] ?? null,
                    'dormName' => $dorm['dormName'] ?? 'Unnamed Dorm',
                    'address' => $dorm['address'] ?? 'No address provided',
                    'occupancyType' => $dorm['occupancyType'] ?? 'Mixed',
                    'rooms' => $rooms,
                ];
            }
        }


        // Fallback: Get actual Surigao City dormitories from database
        if (empty($recommendations)) {
            $dorms = dormModel::with(['rooms', 'images', 'amenities'])
                ->where('address', 'LIKE', '%Surigao%')
                ->get();


            foreach ($dorms as $dorm) {
                $rooms = [];


                foreach ($dorm->rooms as $room) {
                    $features = $room->features ?? [];


                    if (is_string($features)) {
                        $features = array_filter(
                            array_map('trim', explode(',', $features))
                        );
                    }


                    $rooms[] = [
                        'roomNumber' => $room->roomNumber ?? 'N/A',
                        'type' => $room->roomType ?? 'Standard',
                        'price' => $room->price ?? 'Contact landlord',
                        'availability' => $room->availability ?? 'Unknown',
                        'features' => $features,
                    ];
                }


                $recommendations[] = [
                    'dormID' => $dorm->dormID,
                    'dormName' => $dorm->dormName,
                    'address' => $dorm->address,
                    'occupancyType' => $dorm->occupancyType,
                    'rooms' => $rooms,
                ];
            }
        }


        return response()->json([
            'message' => $aiRaw,
            'recommendations' => $recommendations,
        ]);


    } catch (\Throwable $e) {
        Log::error('DormDash AI Recommendation Error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);


        return response()->json([
            'message' => 'Naa’y error sa pagkuha og AI recommendations.',
            'recommendations' => [],
        ], 500);
    }
}


}
