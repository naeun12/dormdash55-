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
    // Ipakita ang dormitory map page para sa naka-login nga tenant.
    public function dormitoriesMap($tenant_id)
    {
        // Kuhaa ang tenant ID gikan sa current session.
        $sessionTenant_id = session('tenant_id');

        // Ibalik sa login page kung walay naka-login nga tenant.
        if (!$sessionTenant_id) {
            return redirect()->route('tenant-login')
                ->with('error', 'Please log in as a tenant.');
        }

        // Siguraduhon nga ang tenant ID sa URL parehas sa tenant nga naka-login.
        if ((string) $tenant_id !== (string) $sessionTenant_id) {
            return redirect()->route('tenant-login')
                ->with('error', 'Unauthorized access.');
        }

        // Pangitaa ang tenant record sa database.
        $tenant = tenantModel::find($tenant_id);

        // Ibalik sa login page kung wala makit-i ang tenant.
        if (!$tenant) {
            return redirect()->route('tenant-login')
                ->with('error', 'Tenant not found.');
        }

        // Kuhaa ang pinakabag-ong 5 ka notifications sa tenant.
        $notifications = notificationModel::where(
            'receiverID',
            $sessionTenant_id
        )
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Ihap ang wala pa mabasang notifications sa tenant.
        $unreadCount = notificationModel::where(
            'receiverID',
            $sessionTenant_id
        )
            ->where('isRead', false)
            ->count();

        // I-load ang dormitory map page uban sa tenant ug notification data.
        return view('tenant.auth.dormitorieslocation', [
            'title' => 'Dormitories Map - DormDash',
            'tenant_id' => $tenant_id,
            'cssPath' => 'css/tenant/dormitoriesmap.css',
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    // Pangitaa ang mga dormitory nga duol sa current location sa tenant.
    public function getNearbyByCoordinates(Request $request)
    {
        // Kuhaa ang latitude ug longitude gikan sa frontend.
        $lat = $request->input('lat');
        $lng = $request->input('lng');

        // Ang maximum distance sa pagpangita kay 2 kilometers.
        $radius = 2; // Radius in KM

        // Pangitaa ang dormitories gamit ang distance formula.
        $nearbyDorms = dormModel::with('images')
            ->select('dormID', 'dormName', 'address', 'latitude', 'longitude', 'occupancyType')

            // Kalkulahon ang gilay-on sa matag dormitory gikan sa user coordinates.
            ->selectRaw("(
                6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )
            ) AS distance", [$lat, $lng, $lat])

            // I-apil lamang ang dormitories nga sulod sa 2 kilometers.
            ->having('distance', '<', $radius)

            // Unahon ang pinakaduol nga dormitory.
            ->orderBy('distance')
            ->get()

            // I-format ang distance ngadto sa duha ka decimal places.
            ->map(function ($dorm) {
                $dorm->distance_km = round($dorm->distance, 2);

                return $dorm;
            });

        // Ibalik ang nearby dormitories ug total count isip JSON.
        return response()->json([
            'status' => 'success',
            'count' => $nearbyDorms->count(),
            'data' => $nearbyDorms
        ]);
    }

    // I-filter ang nearby dormitories base sa price range nga gipili sa user.
    public function priceRange(Request $request)
    {
        // Kuhaa ang price range ug current coordinates gikan sa frontend.
        $range = $request->input('price_range');
        $lat = $request->input('lat');
        $lng = $request->input('lng');

        // Pangitaa ang dormitories uban sa ilang rooms ug images.
        $dorms = dormModel::with(['rooms', 'images'])
            ->select('dorms.*')

            // Kalkulahon ang distance sa dormitory gikan sa user location.
            ->selectRaw("(
                6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )
            ) AS distance_km", [$lat, $lng, $lat])

            // I-apil lamang ang dormitories nga sulod sa 2 kilometers.
            ->having('distance_km', '<=', 2)

            // I-filter ang dormitories base sa presyo sa ilang rooms.
            ->whereHas('rooms', function ($query) use ($range) {

                // Kung all ang gipili, dili mag-apply og price filter.
                if ($range !== 'all') {

                    // Pangitaa ang rooms nga nagkantidad og 301 pataas.
                    if ($range === '301+') {
                        $query->where('price', '>=', 301);

                    // Kung range format ang gipili, kuhaa ang minimum ug maximum price.
                    } elseif (str_contains($range, '-')) {
                        [$min, $max] = explode('-', $range);

                        // I-aplikar ang price range sa rooms.
                        $query->whereBetween('price', [(int)$min, (int)$max]);
                    }
                }
            })

            // Kuhaa ang matching dormitories gikan sa database.
            ->get()

            // Dugangan og computed fields ang matag dormitory record.
            ->map(function ($dorm) {

                // Kuhaa ang presyo sa unang room sa dormitory.
                $dorm->price = optional($dorm->rooms->first())->price;

                // Kuhaa ang main image sa dormitory kung adunay image.
                $dorm->mainImage = optional($dorm->images)->mainImage;

                return $dorm;
            });

        // Ibalik ang filtered dormitories isip JSON response.
        return response()->json([
            'status' => 'success',
            'data' => $dorms
        ]);
    }
}
