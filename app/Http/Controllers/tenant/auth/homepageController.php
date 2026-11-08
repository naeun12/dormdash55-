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
    // Ipakita ang homepage sa tenant nga naka-login.
    public function homepage($tenant_id)
    {
        // Kuhaa ang tenant ID gikan sa current session.
        $sessionTenant_id = session('tenant_id');

        // Kuhaa ang pinakabag-ong 5 ka notifications sa tenant.
        $notifications = notificationModel::where('receiverID', $sessionTenant_id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Ihap ang wala pa mabasang notifications.
        $unreadCount = notificationModel::where('receiverID', $tenant_id)
            ->where('isRead', false)
            ->count();

        // Ibalik sa login page kung walay naka-login nga tenant.
        if (!$sessionTenant_id) {
            return redirect()->route('tenant-login')->with('error', 'Please log in as a landlord.');
        }

        // Siguraduhon nga ang tenant ID sa URL parehas sa naa sa session.
        if ($tenant_id !== $sessionTenant_id) {
            return redirect()->route('tenant-login')->with('error', 'Unauthorized access.');
        }

        // Pangitaa ang tenant record sa database.
        $tenant = tenantModel::find($tenant_id);

        // Ibalik sa login page kung wala makit-i ang tenant.
        if (!$tenant) {
            return redirect()->route('tenant-login')->with('error', 'Landlord not found.');
        }

        // I-load ang homepage uban sa tenant ug notification information.
        return view('tenant.auth.homepage', [
            'title' => 'Homepage  - DormDash',
            'tenant_id',
            $tenant,
            'cssPath' => asset('css/tenantpage/auth/home.css'),
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    // Kuhaa ang mga dormitory nga naa sa Surigao.
    public function dormSurigao()
    {
        // Pangitaa ang dormitories nga ang address adunay "Surigao".
        return response()->json(
            dormModel::where('address', 'LIKE', '%Surigao%')
                // Pilia lamang ang importanteng dormitory information.
                ->select('dormID', 'dormName', 'address', 'latitude', 'longitude')

                // I-load ang main image sa matag dormitory.
                ->with(['images' => function ($query) {
                    $query->select('fkdormID', 'mainImage');
                }])
                ->get()
        );
    }

    // Kuhaa ang top 5 nga dormitories base sa average rating.
    public function topRatedDorms()
    {
        // I-load ang images, kuhaa ang average rating ug ihap sa reviews.
        $dorms = dormModel::with('images')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')

            // Unahon ang dormitory nga adunay pinakataas nga average rating.
            ->orderByDesc('reviews_avg_rating')

            // Limitahan ang resulta ngadto sa top 5 dormitories.
            ->take(5)
            ->get()

            // I-format ang matag dormitory para sa frontend.
            ->map(function ($dorm) {
                return [
                    'fkdormID' => $dorm->dormID,
                    'dorm' => $dorm,

                    // I-convert ang average rating ngadto sa number.
                    'avg_rating' => $dorm->reviews_avg_rating ? floatval($dorm->reviews_avg_rating) : 0,
                ];
            });

        // Ibalik ang top-rated dormitories isip JSON.
        return response()->json($dorms);
    }

    // Mag-recommend og dormitories base sa personal preferences sa tenant.
    public function genderPreference(Request $request, $tenant_id)
    {
        // Pangitaa ug i-validate kung naa ba ang tenant sa database.
        $tenant = tenantModel::find($tenant_id);

        if (!$tenant) {
            return response()->json(['dorms' => []], 404);
        }

        // Kuhaa ang gender, preferred location ug maximum preferred room price.
        $gender = $tenant->gender;
        $location = $tenant->preferred_location;
        $tenantPrice = $tenant->preferred_room_price ?? null;

        // I-convert ang saved JSON preferences ngadto sa PHP arrays.
        $prefAmenities = json_decode($tenant->preferred_amenities, true) ?: [];
        $prefFeatures = json_decode($tenant->preferred_features, true) ?: [];
        $prefRules = json_decode($tenant->preferred_rules, true) ?: [];

        // Sugdi ang dormitory query ug i-load ang related information.
        $dorms = dormModel::with([
            'images',
            'amenities',
            'rulesAndPolicy',
            'rooms',
            'rooms.features'
        ])

            // I-filter ang dormitories base sa preferred location.
            ->where('address', 'like', "%$location%")

            // I-apil lamang ang available nga dormitories.
            ->where('availability', 'Available')

            // I-filter ang occupancy type base sa gender sa tenant.
            ->where(function ($q) use ($gender) {
                if ($gender === 'Male') {
                    $q->whereIn('occupancyType', [
                        'Male only',
                        'Mixed',
                        'Mixed (Male & Female – separate floors)'
                    ]);
                } elseif ($gender === 'Female') {
                    $q->whereIn('occupancyType', [
                        'Female only',
                        'Mixed',
                        'Mixed (Male & Female – separate floors)'
                    ]);
                }
            })

            // I-filter ang dormitories base sa preferred amenities.
            ->when(!empty($prefAmenities), function ($q) use ($prefAmenities) {
                $q->whereHas('amenities', function ($a) use ($prefAmenities) {
                    $a->whereIn('amenities.id', $prefAmenities);
                });
            })

            // I-filter ang dormitories base sa preferred rules.
            ->when(!empty($prefRules), function ($q) use ($prefRules) {
                $q->whereHas('rulesAndPolicy', function ($r) use ($prefRules) {
                    $r->whereIn('rulesandpolicies.id', $prefRules);
                });
            })

            // I-filter ang dormitories base sa preferred room features.
            ->when(!empty($prefFeatures), function ($q) use ($prefFeatures) {
                $q->whereHas('rooms.features', function ($f) use ($prefFeatures) {
                    $f->whereIn('roomfeatures.id', $prefFeatures);
                });
            })

            // Unahon ang pinakabag-ong dormitory records.
            ->orderBy('created_at', 'desc')
            ->get();

        // Ibutang ang pinakabarato nga room price sa matag dormitory.
        $dorms->map(function ($dorm) {
            $dorm->preferred_room_price = $dorm->rooms->min('price');

            return $dorm;
        });

        // I-filter pa ang dormitories base sa maximum preferred price sa tenant.
        if ($tenantPrice) {
            $dorms = $dorms->filter(function ($dorm) use ($tenantPrice) {
                return $dorm->preferred_room_price <= $tenantPrice;
            })->values(); // I-reset ang collection indexes human sa filtering.
        }

        // Ibalik ang personalized dormitory recommendations.
        return response()->json(['dorms' => $dorms]);
    }

    // Kuhaa ang personal information ug personalization status sa tenant.
    public function getTenantPersonalized()
    {
        // Kuhaa ang tenant ID gikan sa current session.
        $sessionTenant_id = session('tenant_id');

        // Pangitaa ang tenant record sa database.
        $tenant = tenantModel::find($sessionTenant_id);

        // Ibalik ang error kung wala makit-i ang tenant.
        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }

        // Siguraduhon nga boolean ang personalization status.
        $tenant->isPersonalized = (bool) $tenant->isPersonalized;

        // Ibalik ang tenant information isip JSON.
        return response()->json(['tenant' => $tenant]);
    }

    // Kuhaa ang available dormitories, amenities, rules ug room features para sa personalization.
    public function getRoomandDormPersonalized()
    {
        // Kuhaa ang tenant ID gikan sa session ug pangitaa ang tenant.
        $tenantId = session('tenant_id');
        $tenant = tenantModel::find($tenantId);

        // Kuhaa ang available dormitories uban sa ilang amenities ug rules.
        $dorms = dormModel::with(['amenities', 'rulesAndPolicy'])
            ->where('availability', 'Available')
            ->get();

        // Tigumon ang amenities gikan sa tanang dormitories ug tangtangon ang duplicates.
        $amenitiesArray = $dorms->flatMap(fn($dorm) => $dorm->amenities)
            ->unique('id')
            ->values()
            ->toArray();

        // Tigumon ang rules gikan sa tanang dormitories ug tangtangon ang duplicates.
        $rulesarray = $dorms->flatMap(fn($dorm) => $dorm->rulesAndPolicy)
            ->unique('id')
            ->values()
            ->toArray();

        // Kuhaa ang tanang rooms uban sa ilang related features.
        $room = roomModel::with('features')
            ->get();

        // Tigumon ang room features ug tangtangon ang magkaparehas nga records.
        $featureArray = $room->flatMap(fn($room) => $room->features)
            ->unique('id')
            ->values()
            ->toArray();

        // Ibalik ang tenant ug tanang data nga kinahanglan sa personalization form.
        return response()->json([
            'tenant' => $tenant,
            'amenitiesArray' => $amenitiesArray,
            'rulesarray' => $rulesarray,
            'featureArray' => $featureArray,
            'dorms' => $dorms,
            'room' => $room
        ]);
    }

    // I-save ang personal dormitory preferences nga gisubmit sa tenant.
    public function updateSubmitPersonalized(Request $request)
    {
        // I-validate ang mga input gikan sa personalization form.
        $request->validate([
            'preferredPrice' => 'required|numeric|min:0',
            'preferredLocation' => 'required|string|max:255',
            'preferredAmenities' => 'nullable|array',
            'preferredFeature' => 'nullable|array',
            'preferredRules' => 'nullable|array',
        ], [
            // Custom error messages kung dili valid ang gipasa nga data.
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

        // Kuhaa ang tenant ID sa session ug pangitaa ang tenant record.
        $tenantId = session('tenant_id');
        $tenant = tenantModel::find($tenantId);

        // Ibalik ang error kung wala makit-i ang tenant.
        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }

        // Andamon ang bag-ong preferences nga i-save sa tenant record.
        $updateData = [
            'isPersonalized' => true,
            'preferred_room_price' => $request->input('preferredPrice'),
            'preferred_location' => $request->input('preferredLocation'),
            'preferred_amenities' => $request->input('preferredAmenities', []),
            'preferred_features' => $request->input('preferredFeature', []),
            'preferred_rules' => $request->input('preferredRules', []),
        ];

        // I-update ang tenant preferences sa database.
        $tenant->update($updateData);

        // Ibalik ang updated tenant information ug success message.
        return response()->json([
            'tenant' => $tenant,
            'message' => 'Preferences saved successfully'
        ]);
    }
}
