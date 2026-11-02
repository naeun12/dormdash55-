<?php

namespace App\Http\Controllers\tenant\auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\landlord\landlordAccountModel;
use App\Models\landlord\roomModel;
use App\Models\landlord\dormModel; 
use App\Models\tenant\tenantModel; 
use Illuminate\Support\Facades\DB;
use App\Models\notificationModel;



class dormitoriesmapcontroller extends Controller
{
 public function dormitoriesMap($tenant_id)
{
    $sessionTenant_id = session('tenant_id');

    if (!$sessionTenant_id) {
        return redirect()->route('tenant-login')
            ->with('error', 'Please log in as a tenant.');
    }

    if ((string) $tenant_id !== (string) $sessionTenant_id) {
        return redirect()->route('tenant-login')
            ->with('error', 'Unauthorized access.');
    }

    $tenant = tenantModel::find($tenant_id);

    if (!$tenant) {
        return redirect()->route('tenant-login')
            ->with('error', 'Tenant not found.');
    }

    $notifications = notificationModel::where(
        'receiverID',
        $sessionTenant_id
    )
        ->orderBy('created_at', 'desc')
        ->take(5)
        ->get();

    $unreadCount = notificationModel::where(
        'receiverID',
        $sessionTenant_id
    )
        ->where('isRead', false)
        ->count();

    return view('tenant.auth.dormitorieslocation', [
        'title' => 'Dormitories Map - DormDash',
        'tenant_id' => $tenant_id,
        'cssPath' => 'css/tenant/dormitoriesmap.css',
        'notifications' => $notifications,
        'unread_count' => $unreadCount,
    ]);
}
    public function getNearbyByCoordinates(Request $request)
{
    $lat = $request->input('lat');
    $lng = $request->input('lng');
    $radius = 2; // Radius in KM

    $nearbyDorms = dormModel::with('images')
    ->select('dormID', 'dormName', 'address', 'latitude', 'longitude','occupancyType')
    ->selectRaw("(
        6371 * acos(
            cos(radians(?)) * cos(radians(latitude)) *
            cos(radians(longitude) - radians(?)) +
            sin(radians(?)) * sin(radians(latitude))
        )
    ) AS distance", [$lat, $lng, $lat])
    ->having('distance', '<', $radius)
    ->orderBy('distance')
    ->get()
    ->map(function ($dorm)
    {
       $dorm->distance_km = round($dorm->distance, 2);
       return $dorm; 
    });

return response()->json([
    'status' => 'success',
    'count' => $nearbyDorms->count(),
    'data' => $nearbyDorms
]);
}
public function priceRange(Request $request)
{
    $range = $request->input('price_range');
    $lat = $request->input('lat');
    $lng = $request->input('lng');

    $dorms = dormModel::with(['rooms', 'images'])
        ->select('dorms.*')
        ->selectRaw("(
            6371 * acos(
                cos(radians(?)) * cos(radians(latitude)) * 
                cos(radians(longitude) - radians(?)) + 
                sin(radians(?)) * sin(radians(latitude))
            )
        ) AS distance_km", [$lat, $lng, $lat])
        ->having('distance_km', '<=', 2)
        ->whereHas('rooms', function ($query) use ($range) {
            if ($range !== 'all') {
                if ($range === '301+') {
                    $query->where('price', '>=', 301);
                } elseif (str_contains($range, '-')) {
                    [$min, $max] = explode('-', $range);
                    $query->whereBetween('price', [(int)$min, (int)$max]);
                }
            }
        })
        ->get()
        ->map(function ($dorm) {
            // Add computed field to each dorm
            $dorm->price = optional($dorm->rooms->first())->price;
            $dorm->mainImage = optional($dorm->images)->mainImage;
            return $dorm;
        });

    return response()->json([
        'status' => 'success',
        'data' => $dorms
    ]);
}




}
