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
    // Ipakita ang dormitory listing page para sa naka-login nga tenant.
    public function dormitoriesListing($tenant_id)
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

        // I-load ang dormitory page uban sa gikinahanglang data.
        return view('tenant.auth.dormitories', [
            'title' => 'Dormitories  - DormDash',
            'tenant_id',
            $tenant,
            'cssPath' => asset('css/tenantpage/auth/dormitory.css'),
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    // Kuhaa ang mga dormitory nga naa sa Surigao.
    public function Listdorms()
    {
        // I-load apil ang amenities ug images sa matag dormitory.
        $dorms = dormModel::with(['amenities', 'images'])
            ->where('address', 'LIKE', '%Surigao%')
            ->paginate(10);

        // Ibalik ang dormitory records ug total count isip JSON.
        return response()->json([
            'status' => 'success',
            'dorms' => $dorms,
            'total_dorms' => $dorms->total(),
            'surigao_dorms' => $dorms->total(),
        ]);
    }

    // Kuhaa ang dormitories base sa city nga gipili sa user.
    public function selectedCityDorms(Request $request)
    {
        // Default nga city kay Surigao kung walay gipasa nga city.
        $city = $request->input('city', 'Surigao');

        // Pangitaa ang dormitories nga ang address adunay city nga gipili.
        $dorms = dormModel::with(['amenities', 'images'])
            ->where('address', 'LIKE', '%' . $city . '%')
            ->paginate(10);

        // Ibalik ang resulta ug total number sa dormitories.
        return response()->json([
            'status' => 'success',
            'total_dorms' => $dorms->total(),
            'dorms' => $dorms,
        ]);
    }

    // Kuhaa ang tanang available amenities gikan sa database.
    public function fetchAmenities()
    {
        // Basaha ang tanang records sa amenities table.
        $amenities = DB::table('amenities')->get();

        // Ibalik ang amenities isip JSON response.
        return response()->json([
            'status' => 'success',
            'amenities' => $amenities,
        ]);
    }

    // I-filter ang dormitories base sa amenities nga gipili sa user.
    public function getAmenities(Request $request)
    {
        // Kuhaa ang list sa amenity IDs gikan sa frontend.
        $amenities = $request->input('amenities', []);

        // Sugdi ang dormitory query nga apil ang amenities ug images.
        $dormsQuery = dormModel::with(['amenities', 'images']);

        // I-apply ang filter kung adunay gipiling amenities.
        if (!empty($amenities)) {
            $dormsQuery->whereHas('amenities', function ($q) use ($amenities) {
                // Siguraduhon nga naa ang tanang gipiling amenity IDs.
                $q->whereIn('amenities.id', $amenities);
            }, '=', count($amenities));
        }

        // Kuhaa ang resulta gamit ang pagination.
        $dorms = $dormsQuery->paginate(10);

        // Ibalik ang filtered dormitories.
        return response()->json([
            'status' => 'success',
            'dorms' => $dorms,
        ]);
    }

    // I-filter ang dormitories base sa minimum rating nga gipili.
    public function getRate(Request $request)
    {
        // Kuhaa ang rating value gikan sa frontend.
        $rate = $request->input('rating');

        // Sugdi ang query nga apil ang images ug reviews.
        $dormsQuery = dormModel::with(['images', 'reviews']);

        // I-filter ang dormitories kung valid ang gipasa nga rating.
        if ($rate !== null && is_numeric($rate)) {
            $dormsQuery->whereHas('reviews', fn($q) => $q->where('rating', '>=', $rate));
        }

        // Kuhaa ang dormitories ug kalkulaha ang average rating sa matag usa.
        $dorms = $dormsQuery->paginate(10)->through(function ($dorm) {
            $avgRating = $dorm->reviews->avg('rating') ?? 0;

            // I-convert ang average rating ngadto sa percentage.
            $dorm->rating_percentage = round(($avgRating / 5) * 100, 2);

            // Tangtanga ang reviews sa response kay percentage na lang ang kinahanglan.
            unset($dorm->reviews);

            return $dorm;
        });

        // Ibalik ang dormitories uban sa ilang rating percentage.
        return response()->json([
            'status' => 'success',
            'dorms' => $dorms,
        ]);
    }

    // I-sort ang dormitories base sa petsa nga gi-update.
    public function getSortByDate(Request $request)
    {
        // Kuhaa ang sorting option gikan sa frontend.
        $sortBy = $request->input('sortBy');

        // Sugdi ang query nga apil ang dormitory images.
        $dormsQuery = dormModel::with('images');

        // Pinakabag-o una kung new-old ang gipili.
        if ($sortBy === 'new-old') {
            $dormsQuery->orderBy('updated_at', 'desc');
        } elseif ($sortBy === 'old-new') {
            // Pinakaraan una kung old-new ang gipili.
            $dormsQuery->orderBy('updated_at', 'asc');
        }

        // Kuhaa ang sorted dormitories nga adunay pagination.
        $dorms = $dormsQuery->paginate(10);

        // Ibalik ang resulta ug total count sa current page.
        return response()->json([
            'status' => 'success',
            'total_dorms' => $dorms->count(),
            'dorms' => $dorms,
        ]);
    }

    // I-filter ang dormitories base sa availability sa ilang rooms.
    public function getAvailability(Request $request)
    {
        // Kuhaa ang availability nga gipili sa user.
        $availability = $request->input('availability');

        // Sugdi ang query nga apil ang rooms ug images.
        $dormsQuery = dormModel::with(['rooms', 'images']);

        // I-apply ang filter kung dili "all" ang gipili.
        if ($availability && $availability !== 'all') {
            $dormsQuery->whereHas('rooms', function ($q) use ($availability) {
                // I-compare ang availability nga dili sensitive sa uppercase/lowercase.
                $q->whereRaw('LOWER(availability) = ?', [strtolower($availability)]);
            });
        }

        // Kuhaa ang filtered dormitories.
        $dorms = $dormsQuery->paginate(10);

        // Ibalik ang resulta isip JSON.
        return response()->json([
            'status' => 'success',
            'dorms' => $dorms,
        ]);
    }

    // Limpyohan ang text aron sayon ikumpara ang location keywords.
    protected function normalize($text)
    {
        // Himoon nga lowercase ug tangtangon ang spaces ug special characters.
        return preg_replace('/[^a-z0-9]+/', '', strtolower($text));
    }

    // Pangitaa ang dormitories base sa location nga gisulod sa user.
    public function searchLocations(Request $request)
    {
        try {
            // Siguraduhon nga adunay location nga gipasa.
            $validated = $request->validate([
                'location' => 'required|string',
            ]);

            // Limpyohan ug himoon nga lowercase ang location keyword.
            $keyword = strtolower(trim($validated['location']));
            $keywordNormalized = $this->normalize($keyword);

            // Sugdi ang database query ug i-join ang dorm images table.
            $query = DB::table('dorms as d')
                ->leftJoin('dormimages as i', 'i.fkdormID', '=', 'd.dormID')
                ->select('d.*', 'i.mainImage')
                ->whereNotNull('d.latitude')
                ->whereNotNull('d.longitude');

            // Kuhaa una ang records nga adunay valid coordinates.
            $dormsPaginated = $query->paginate(10);

            // I-filter ang resulta base sa normalized address.
            $filteredCollection = $dormsPaginated->getCollection()->filter(function ($dorm) use ($keywordNormalized) {
                $addressNormalized = preg_replace('/[^a-z0-9]+/', '', strtolower($dorm->address));

                // I-check kung naa ba ang gipangita nga location sa address.
                return strpos($addressNormalized, $keywordNormalized) !== false;
            });

            // Ibalik ang filtered records sa paginator aron magpabilin ang pagination data.
            $dormsPaginated->setCollection($filteredCollection->values());

            // Ibalik ang location-based recommendations.
            return response()->json([
                'status' => 'success',
                'recommendations' => $dormsPaginated,
            ]);

        } catch (\Exception $e) {
            // Ibalik ang error details kung napakyas ang location search.
            return response()->json([
                'status' => 'error',
                'message' => 'Laravel controller error',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    // Mag-recommend og dormitories base sa price range nga gipili sa user.
    public function priceRecommendations(Request $request)
    {
        try {
            // Kuhaa ang minimum ug maximum price; gamiton ang default kung walay input.
            $minPrice = floatval($request->input('min_price', 0));
            $maxPrice = floatval($request->input('max_price', 999999));

            // Palapdon gamay ang price range para makahatag og mas daghang options.
            $adjustedMin = $minPrice * 0.8;
            $adjustedMax = $maxPrice * 1.2;

            // Pangitaa ang available rooms nga naa sa adjusted price range.
            $results = \App\Models\landlord\roomModel::with(['dorm.images'])
                ->whereBetween('price', [$adjustedMin, $adjustedMax])
                ->whereRaw('LOWER(availability) = ?', ['available'])
                ->orderBy('price', 'asc')
                ->paginate(10)
                ->through(function ($room) {
                    // I-format ang room ug dormitory information para sa frontend.
                    return [
                        'dormID' => $room->dorm->dormID ?? null,
                        'dormavailability' => $room->dorm->availability,
                        'dormName' => $room->dorm->dormName ?? null,
                        'occupancyType' => $room->dorm->occupancyType ?? null,
                        'address' => $room->dorm->address ?? null,
                        'latitude' => $room->dorm->latitude ?? null,
                        'longitude' => $room->dorm->longitude ?? null,
                        'price' => $room->price,
                        'genderPreference' => $room->genderPreference,
                        'roomType' => $room->roomType,
                        'furnishing_status' => $room->furnishing_status,
                        'availability' => $room->availability,
                        'mainImage' => $room->dorm->images->mainImage ?? null,
                    ];
                });

            // Ibalik ang price-based recommendations.
            return response()->json([
                'status' => 'success',
                'data' => $results,
            ]);

        } catch (\Exception $e) {
            // Ibalik ang error kung napakyas ang price recommendation.
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Mag-recommend og dormitories base sa occupancy type.
    public function genderRecommendations(Request $request)
    {
        try {
            // Siguraduhon nga adunay occupancy type nga gipasa.
            $validated = $request->validate([
                'occupancy_type' => 'required|string',
            ]);

            // Limpyohan ug himoon nga lowercase ang gipiling occupancy type.
            $inputType = strtolower(trim($validated['occupancy_type']));

            // Sugdi ang query ug i-join ang dorm images.
            $query = DB::table('dorms as d')
                ->leftJoin('dormimages as i', 'i.fkdormID', '=', 'd.dormID')
                ->select('d.*', 'i.mainImage');

            // I-apply ang occupancy filter depende sa gipili sa user.
            if ($inputType != 'all') {
                if ($inputType == 'male') {
                    $query->whereRaw("LOWER(occupancyType) = 'male only'");
                } elseif ($inputType == 'female') {
                    $query->whereRaw("LOWER(occupancyType) = 'female only'");
                } elseif ($inputType == 'mixed') {
                    $query->whereRaw("LOWER(occupancyType) LIKE '%mixed%'");
                }
            }

            // Kuhaa ang resulta nga adunay pagination.
            $dorms = $query->paginate(10);

            // Ibalik ang filtered recommendations ug pagination information.
            return response()->json([
                'status' => 'success',
                'recommendations' => $dorms,
            ]);

        } catch (\Exception $e) {
            // Ibalik ang error details kung napakyas ang filtering.
            return response()->json([
                'status' => 'error',
                'message' => 'Laravel error',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    // Kuhaa ang dormitories nga adunay pinakadaghan nga views.
    public function mostWatchedDorm($id)
    {
        // I-load ang dormitories ug images, unya i-sort base sa views.
        $dorm = dormModel::with('images')->orderBy('views', 'desc')->get();

        // Ibalik ang list sa most-watched dormitories.
        return response()->json([
            'message' => 'Most watched dorm retrieved successfully.',
            'dorm' => $dorm
        ]);
    }

    // Pangitaa ang dormitories gamit ang lain-laing filters.
    public function searchDormsWithFilters(array $filters)
    {
        // Sugdi ang dormitory query.
        $query = dormModel::query();

        // I-filter base sa location kung adunay gipasa.
        if (!empty($filters['location'])) {
            $query->where('address', 'like', '%' . $filters['location'] . '%');
        }

        // I-filter base sa room type o maximum price kung gipili.
        if (!empty($filters['type']) || !empty($filters['max_price'])) {
            $query->whereHas('rooms', function ($q) use ($filters) {
                // I-check kung parehas ang room type.
                if (!empty($filters['type'])) {
                    $q->where('roomType', $filters['type']);
                }

                // I-check kung dili molapas sa maximum price.
                if (!empty($filters['max_price'])) {
                    $q->where('price', '<=', $filters['max_price']);
                }
            });
        }

        // Kuhaa ang dormitories uban sa ilang related rooms.
        $dorms = $query->with('rooms')->get();

        // Ibalik ang resulta sa filtering.
        return $dorms;
    }

    // Kuhaa ang AI-generated dormitory recommendations base sa pangutana sa tenant.
    public function getQuestionRecommendations(Request $request)
    {
        // I-validate ang pangutana ug limitahan kini sa 1000 characters.
        $request->validate([
            'question' => 'required|string|max:1000',
        ]);

        try {
            // Kuhaa ug limpyohan ang pangutana gikan sa frontend.
            $question = trim($request->input('question'));

            // URL sa Python AI service nga nagproseso sa dormitory questions.
            $apiUrl = 'https://pythonaideploy-production.up.railway.app/ask-ai/dormitories';

            // Ipadala ang pangutana ngadto sa Python AI API isip JSON.
            $response = Http::timeout(60)
                ->acceptJson()
                ->asJson()
                ->post($apiUrl, [
                    'question' => $question,
                ]);

            // I-check kung napakyas ang request ngadto sa AI service.
            if ($response->failed()) {
                // I-save sa Laravel log ang status ug response body para debugging.
                Log::error('DormDash AI API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                // Ibalik ang error response kung unavailable ang AI service.
                return response()->json([
                    'message' => 'AI service is currently unavailable.',
                    'recommendations' => [],
                ], 502);
            }

            // I-convert ang API response ngadto sa PHP array.
            $data = $response->json() ?? [];

            // Kuhaa ang AI message o gamita ang default message kung walay sulod.
            $aiRaw = $data['message'] ?? 'Here are your dormitory recommendations.';

            // Kuhaa ang recommendations gikan sa AI response.
            $aiRecommendations = $data['recommendations'] ?? [];

            // Gamita ang result field kung walay recommendations field.
            if (empty($aiRecommendations)) {
                $aiRecommendations = $data['result'] ?? [];
            }

            // Kuhaa ang JSON data kung ang AI response naa sulod sa Markdown code block.
            if (empty($aiRecommendations)) {
                if (preg_match('/```json\s*(.*?)\s*```/s', $aiRaw, $matches)) {
                    $decoded = json_decode(trim($matches[1]), true);

                    // Gamita ang decoded data kung valid nga array.
                    if (is_array($decoded)) {
                        $aiRecommendations = $decoded;
                    }
                }
            }

            // Andamon ang normalized recommendations para parehas ang format sa frontend.
            $recommendations = [];

            if (is_array($aiRecommendations)) {
                // I-process ang matag dormitory recommendation gikan sa AI.
                foreach ($aiRecommendations as $dorm) {
                    // Laktawan ang data nga dili array.
                    if (!is_array($dorm)) {
                        continue;
                    }

                    $rooms = [];

                    // I-process ang mga rooms sa matag dormitory.
                    foreach (($dorm['rooms'] ?? []) as $room) {
                        // Laktawan ang room data nga dili array.
                        if (!is_array($room)) {
                            continue;
                        }

                        // Kuhaa ang room features.
                        $features = $room['features'] ?? [];

                        // Kung string ang features, bahin-bahinon kini gamit ang comma.
                        if (is_string($features)) {
                            $features = array_filter(
                                array_map('trim', explode(',', $features))
                            );
                        }

                        // I-format ang room information ug gamiton ang default values kung kulang.
                        $rooms[] = [
                            'roomNumber' => $room['roomNumber'] ?? 'N/A',
                            'type' => $room['type'] ?? $room['roomType'] ?? 'Standard',
                            'price' => $room['price'] ?? 'Contact landlord',
                            'availability' => $room['availability'] ?? 'Unknown',
                            'features' => $features,
                        ];
                    }

                    // I-format ang dormitory information uban sa processed rooms.
                    $recommendations[] = [
                        'dormID' => $dorm['dormID'] ?? null,
                        'dormName' => $dorm['dormName'] ?? 'Unnamed Dorm',
                        'address' => $dorm['address'] ?? 'No address provided',
                        'occupancyType' => $dorm['occupancyType'] ?? 'Mixed',
                        'rooms' => $rooms,
                    ];
                }
            }

            // Fallback kung walay valid recommendations nga nakuha gikan sa AI.
            if (empty($recommendations)) {
                // Kuhaa ang actual Surigao dormitories gikan sa database.
                $dorms = dormModel::with(['rooms', 'images', 'amenities'])
                    ->where('address', 'LIKE', '%Surigao%')
                    ->get();

                // I-convert ang database records ngadto sa recommendation format.
                foreach ($dorms as $dorm) {
                    $rooms = [];

                    // I-process ang rooms sa matag dormitory.
                    foreach ($dorm->rooms as $room) {
                        // Kuhaa ang room features.
                        $features = $room->features ?? [];

                        // I-convert ang features ngadto sa array kung string kini.
                        if (is_string($features)) {
                            $features = array_filter(
                                array_map('trim', explode(',', $features))
                            );
                        }

                        // I-format ang room details.
                        $rooms[] = [
                            'roomNumber' => $room->roomNumber ?? 'N/A',
                            'type' => $room->roomType ?? 'Standard',
                            'price' => $room->price ?? 'Contact landlord',
                            'availability' => $room->availability ?? 'Unknown',
                            'features' => $features,
                        ];
                    }

                    // Idugang ang dormitory ug room information sa recommendations.
                    $recommendations[] = [
                        'dormID' => $dorm->dormID,
                        'dormName' => $dorm->dormName,
                        'address' => $dorm->address,
                        'occupancyType' => $dorm->occupancyType,
                        'rooms' => $rooms,
                    ];
                }
            }

            // Ibalik ang AI message ug final recommendations ngadto sa frontend.
            return response()->json([
                'message' => $aiRaw,
                'recommendations' => $recommendations,
            ]);

        } catch (\Throwable $e) {
            // I-save ang error ug stack trace para mas sayon ma-debug.
            Log::error('DormDash AI Recommendation Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Ibalik ang error response kung adunay problema sa AI recommendation process.
            return response()->json([
                'message' => 'Naa’y error sa pagkuha og AI recommendations.',
                'recommendations' => [],
            ], 500);
        }
    }
}
