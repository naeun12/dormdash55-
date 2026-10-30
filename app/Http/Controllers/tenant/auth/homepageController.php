<?php

namespace App\Http\Controllers\tenant\auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\landlord\landlordAccountModel;
use App\Models\landlord\roomModel;
use App\Models\notificationModel;
use App\Models\reviewandratingModel;
use App\Models\landlord\dormModel; 
use App\Models\tenant\tenantModel; 



class homepageController extends Controller
{
    public function homepage($tenant_id)
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
        return view('tenant.auth.homepage',['title' => 'Homepage  - DormDash',
        'tenant_id',$tenant,'cssPath' => asset('css/tenantpage/auth/home.css')
        ,'notifications' => $notifications,
             'unread_count' => $unreadCount,]);

    }
public function dormSurigao()
{
    return response()->json(
        dormModel::where('address', 'LIKE', '%Surigao%')
            ->select('dormID', 'dormName', 'address', 'latitude', 'longitude')
            ->with(['images' => function ($query) {
                $query->select('fkdormID', 'mainImage');
            }])
            ->get()
    );
}
public function topRatedDorms()
{
  $dorms = dormModel::with('images')       // eager load images
    ->withAvg('reviews', 'rating')          // calculate average rating
    ->withCount('reviews')                  // count reviews
    ->orderByDesc('reviews_avg_rating')     // top-rated first
    ->take(5)                               // get top 5
    ->get()
    ->map(function($dorm) {
        return [
            'fkdormID' => $dorm->dormID,
            'dorm'     => $dorm,
            'avg_rating'=> $dorm->reviews_avg_rating ? floatval($dorm->reviews_avg_rating) : 0,
        ];
    });

    return response()->json($dorms);
}public function genderPreference(Request $request, $tenant_id)
{
    // 1. Validate Tenant
    $tenant = tenantModel::find($tenant_id);
    if (!$tenant) {
        return response()->json(['dorms' => []], 404);
    }

    // 2. Get tenant preferences
    $gender = $tenant->gender;
    $location = $tenant->preferred_location;
    $tenantPrice = $tenant->preferred_room_price ?? null;

    $prefAmenities = json_decode($tenant->preferred_amenities, true) ?: [];
    $prefFeatures  = json_decode($tenant->preferred_features, true) ?: [];
    $prefRules     = json_decode($tenant->preferred_rules, true) ?: [];

    // 3. Query Dorms
    $dorms = dormModel::with(['images', 'amenities', 'rulesAndPolicy', 'rooms', 'rooms.features'])
        ->where('address', 'like', "%$location%")
        ->where('availability', 'Available')
        ->where(function($q) use ($gender) {
            if ($gender === 'Male') {
                $q->whereIn('occupancyType', ['Male only', 'Mixed', 'Mixed (Male & Female – separate floors)']);
            } elseif ($gender === 'Female') {
                $q->whereIn('occupancyType', ['Female only', 'Mixed', 'Mixed (Male & Female – separate floors)']);
            }
        })
        // Filter by preferred amenities
        ->when(!empty($prefAmenities), function($q) use ($prefAmenities) {
            $q->whereHas('amenities', function($a) use ($prefAmenities) {
                $a->whereIn('amenities.id', $prefAmenities);
            });
        })
        // Filter by preferred rules
        ->when(!empty($prefRules), function($q) use ($prefRules) {
            $q->whereHas('rulesAndPolicy', function($r) use ($prefRules) {
                $r->whereIn('rulesandpolicies.id', $prefRules);
            });
        })
        // Filter by preferred features
        ->when(!empty($prefFeatures), function($q) use ($prefFeatures) {
            $q->whereHas('rooms.features', function($f) use ($prefFeatures) {
                $f->whereIn('roomfeatures.id', $prefFeatures);
            });
        })
        ->orderBy('created_at', 'desc')
        ->get();

    // 4. Add cheapest room price to each dorm
    $dorms->map(function($dorm) {
        $dorm->preferred_room_price = $dorm->rooms->min('price'); // cheapest room price
        return $dorm;
    });

    // 5. Filter by tenant's preferred room price if set
    if ($tenantPrice) {
        $dorms = $dorms->filter(function($dorm) use ($tenantPrice) {
            return $dorm->preferred_room_price <= $tenantPrice;
        })->values(); // reindex collection
    }

    // 6. Return JSON
    return response()->json(['dorms' => $dorms]);
}



   

public function getTenantPersonalized()
{
    $sessionTenant_id = session('tenant_id');

    $tenant = tenantModel::find($sessionTenant_id);

    if (!$tenant) {
        return response()->json(['error' => 'Tenant not found'], 404);
    }

    // Make sure isPersonalized is boolean
    $tenant->isPersonalized = (bool) $tenant->isPersonalized;
    
    return response()->json(['tenant' => $tenant]);
}public function getRoomandDormPersonalized()
{
    $tenantId = session('tenant_id');
    $tenant = tenantModel::find($tenantId);

    // Get dorms with relationships
    $dorms = dormModel::with(['amenities','rulesAndPolicy'])
        ->where('availability', 'Available')
        ->get();

    // Flatten and remove duplicates for amenities
    $amenitiesArray = $dorms->flatMap(fn($dorm) => $dorm->amenities)
                            ->unique('id')
                            ->values()
                            ->toArray();

    // Flatten and remove duplicates for rules
    $rulesarray = $dorms->flatMap(fn($dorm) => $dorm->rulesAndPolicy)
                        ->unique('id')
                        ->values()
                        ->toArray();

    // Get rooms with features
    $room = roomModel::with('features')
        ->get();

    // Flatten and remove duplicates for features
    $featureArray = $room->flatMap(fn($room) => $room->features)
                        ->unique('id')
                        ->values()
                        ->toArray();

    return response()->json([
        'tenant' => $tenant,
        'amenitiesArray' => $amenitiesArray,
        'rulesarray' => $rulesarray,
        'featureArray' => $featureArray,
        'dorms' => $dorms,
        'room' => $room
    ]);
}


public function updateSubmitPersonalized(Request $request)
{
    // Validate inputs
    $request->validate([
        'preferredPrice' => 'required|numeric|min:0',
        'preferredLocation' => 'required|string|max:255',
        'preferredAmenities' => 'nullable|array',
        'preferredFeature' => 'nullable|array',
        'preferredRules' => 'nullable|array',
    ], [
        'preferredPrice.required' => 'Room price is required.',
        'preferredPrice.numeric' => 'Room price must be a number.',
        'preferredPrice.min' => 'Room price must be at least 0.',
        'preferredLocation.required' => 'Location is required.',
        'preferredLocation.string' => 'Location must be text.',
        'preferredLocation.max' => 'Location cannot exceed 255 characters.',
        'preferredAmenities.array' => 'Amenities must be an array.',
        'preferredFeature.array' => 'Features must be an array.',
        'preferredRules.array' => 'Rules must be an array.',
    ]);

    // Get tenant
    $tenantId = session('tenant_id');
    $tenant = tenantModel::find($tenantId);

    if (!$tenant) {
        return response()->json(['error' => 'Tenant not found'], 404);
    }

    // Update tenant preferences
    $updateData = [
        'isPersonalized' => true,
        'preferred_room_price' => $request->input('preferredPrice'),
        'preferred_location' => $request->input('preferredLocation'),
        'preferred_amenities' => $request->input('preferredAmenities', []),
        'preferred_features' => $request->input('preferredFeature', []),
        'preferred_rules' => $request->input('preferredRules', []),
    ];

    $tenant->update($updateData);

    return response()->json([
        'tenant' => $tenant,
        'message' => 'Preferences saved successfully'
    ]);
}

}