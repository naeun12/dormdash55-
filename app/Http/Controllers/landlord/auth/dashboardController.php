<?php
namespace App\Http\Controllers\landlord\auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\landlord\landlordModel;
use App\Models\landlord\roomModel;
use App\Models\landlord\bookingModel;
use App\Models\tenant\approvetenantsModel;
use App\Models\tenant\reservationModel;
use App\Models\notificationModel;
use Carbon\Carbon;

use App\Models\landlord\dormModel;
use Barryvdh\DomPDF\Facade\Pdf;

class dashboardController extends Controller
{
    // Ipakita ang dashboard page sa naka-login nga landlord.
    public function landlordDashboard($landlord_id)
    {
        // Kuhaon ang landlord ID gikan sa active session.
        $sessionLandlordId = session('landlord_id');

        // Kuhaon ang pinakabag-ong lima ka notifications sa landlord.
        $notifications = notificationModel::where('receiverID', $sessionLandlordId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Siguraduhon nga naka-login ang landlord.
        if (!$sessionLandlordId) {
            return redirect()->route('loginLandlord')->with('error', 'Please log in as a landlord.');
        }

        // Siguraduhon nga ang landlord ID sa URL parehas sa naka-login nga landlord.
        if ($landlord_id !== $sessionLandlordId) {
            return redirect()->route('loginLandlord')->with('error', 'Unauthorized access.');
        }

        // Pangitaon ang landlord record gamit ang iyang ID.
        $landlord = landlordModel::find($landlord_id);

        // Ibalik sa login page kung walay landlord record nga nakit-an.
        if (!$landlord) {
            return redirect()->route('loginLandlord')->with('error', 'Landlord not found.');
        }

        // Ihap-on ang wala pa mabasang notifications sa landlord.
        $unreadCount = notificationModel::where('receiverID', $landlord_id)
            ->where('isRead', false)
            ->count();

        // I-load ang dashboard view uban sa landlord ug notification information.
        return view('landlord.auth.dashboard', [
            'title' => 'Landlord - Dashboard',
            'headerName' => 'Dashboard',
            'color' => 'primary',
            'landlord_id' => $landlord_id,
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    // Kuhaon ang landlord information base sa iyang ID.
    public function getLandlord(Request $request, $landlord_id)
    {
        // Kuhaon ang date gikan sa URL query parameter.
        $dateParam = $request->query('date');

        // Gamiton ang gipiling date o ang current date kung walay gipasa.
        $date = $dateParam ? \Carbon\Carbon::parse($dateParam) : \Carbon\Carbon::today();

        // Pangitaon ang landlord record sa database.
        $landlord = landlordModel::find($landlord_id);

        // Ibalik ang error kung walay landlord nga nakit-an.
        if (!$landlord) {
            return response()->json([
                'status' => 'error',
                'message' => 'Landlord not found.'
            ], 404);
        }

        // Ibalik ang landlord information isip JSON response.
        return response()->json([
            'status' => 'success',
            'landlord' => $landlord
        ]);
    }

    // Kuhaon ang total number sa tenants sa landlord.
    public function getTotalTenants(Request $request, $landlord_id)
    {
        // Kuhaon ang date ug optional dorm filter gikan sa request.
        $dateParam = $request->query('date');
        $dormID = $request->query('dorm_id'); // Optional nga dorm filter.

        // Gamiton ang gipiling date o current date kung walay gipasa.
        $date = $dateParam ? \Carbon\Carbon::parse($dateParam) : \Carbon\Carbon::today();

        // Ihap-on ang approved tenants nga sakop sa landlord.
        $totalTenants = approvetenantsModel::whereHas('room', function ($query) use ($landlord_id, $dormID) {

            // I-filter ang rooms base sa landlord ID.
            $query->where('fklandlordID', $landlord_id);

            // Kung adunay gipiling dorm, i-apil lamang ang rooms niini.
            if ($dormID) {
                $query->where('fkdormID', $dormID);
            }
        })

        // Dili iapil ang tenants nga moved out ug pending pa.
        ->where('status', '<>', 'moved_out')
        ->where('status', '<>', 'pending')

        // Iapil lamang ang tenant records nga na-create sa gipiling date o mas sayo pa.
        ->whereDate('created_at', '<=', $date)

        // Ihap-on ang total nga records nga nakasunod sa filters.
        ->count();

        // Ibalik ang total number sa tenants.
        return response()->json([
            'status' => 'success',
            'total_tenants' => $totalTenants
        ]);
    }

    // Kuhaon ang ihap sa available beds o rooms sa landlord.
    public function availableBeds(Request $request, $landlord_id)
    {
        // Kuhaon ang gipiling date ug optional dorm ID.
        $dateParam = $request->query('date');
        $dormID = $request->query('dorm_id'); // Optional nga dorm filter.

        // Gamiton ang gipiling date o current date.
        $date = $dateParam ? \Carbon\Carbon::parse($dateParam) : \Carbon\Carbon::today();

        // Kuhaon ang unang adlaw ug kataposang adlaw sa gipiling bulan.
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        // Kuhaon ang rooms sa landlord nga ang availability kay Available.
        $roomsQuery = roomModel::where('fklandlordID', $landlord_id)
            ->where('availability', 'Available');

        // Kung adunay gipiling dorm, i-filter ang rooms base sa dorm ID.
        if ($dormID) {
            $roomsQuery->where('fkdormID', $dormID);
        }

        // Pangitaon ang room IDs nga adunay approved tenant booking
        // nga nag-overlap sa gipiling bulan.
        $bookedRoomIds = approvetenantsModel::whereHas('room', function ($query) use ($landlord_id, $dormID) {

            // Siguraduhon nga ang room iya sa landlord.
            $query->where('fklandlordID', $landlord_id);

            // I-filter usab base sa dorm kung adunay gipiling dorm.
            if ($dormID) {
                $query->where('fkdormID', $dormID);
            }
        })
        ->where(function ($query) use ($startOfMonth, $endOfMonth) {
            $query->where(function ($q) use ($startOfMonth, $endOfMonth) {

                // Iapil ang booking nga nagsugod sa wala pa o sulod sa bulan
                // ug natapos sa sulod o human sa gipiling bulan.
                $q->where('moveInDate', '<=', $endOfMonth)
                    ->where('moveOutDate', '>=', $startOfMonth);
            });
        })

        // Kuhaon ang room IDs ug himoon nga array.
        ->pluck('fkroomID')
        ->toArray();

        // Ihap-on ang available rooms nga wala naapil sa booked room IDs.
        $availableRoomsCount = $roomsQuery
            ->whereNotIn('roomID', $bookedRoomIds)
            ->count();

        // Ibalik ang available beds ug debug information.
        return response()->json([
            'status' => 'success',
            'available_beds' => $availableRoomsCount,
            'debug' => [
                'date' => $date,
                'month_start' => $startOfMonth,
                'month_end' => $endOfMonth,
                'bookedRoomIds' => $bookedRoomIds,
                'allAvailableRooms' => $roomsQuery->pluck('roomID')->toArray(),
            ]
        ]);
    }

    // Kuhaon ang reservation list sa landlord.
    public function getReservationList(Request $request, $landlord_id)
    {
        // Kuhaon ang date ug optional dorm ID gikan sa request.
        $dateParam = $request->query('date');
        $dormID = $request->query('dorm_id'); // Optional nga dorm filter.

        // Gamiton ang gipiling date o current date.
        $date = $dateParam ? \Carbon\Carbon::parse($dateParam) : \Carbon\Carbon::today();

        // Kuhaon ang reservations ug i-load daan ang related room information.
        $reservations = reservationModel::with(['room'])

            // Iapil lamang ang reservations nga ang room iya sa landlord.
            ->whereHas('room', function ($query) use ($landlord_id, $dormID) {
                $query->where('fklandlordID', $landlord_id);

                // I-filter base sa dorm ID kung adunay gipili.
                if ($dormID) {
                    $query->where('fkdormID', $dormID);
                }
            })

            // Iapil lamang ang reservations nga na-create sa gipiling date o mas sayo pa.
            ->whereDate('created_at', '<=', $date)

            // Ipakita una ang pinakabag-ong reservation.
            ->orderBy('created_at', 'desc')
            ->get();

        // Ibalik ang reservation list isip JSON response.
        return response()->json([
            'status' => 'success',
            'reservations' => $reservations
        ]);
    }

    // Kuhaon ang booking list sa landlord.
    public function getBookingList(Request $request, $landlord_id)
    {
        // Kuhaon ang date ug optional dorm ID gikan sa request.
        $dateParam = $request->query('date');
        $dormID = $request->query('dorm_id'); // Optional nga dorm filter.

        // Gamiton ang gipiling date o current date.
        $date = $dateParam ? \Carbon\Carbon::parse($dateParam) : \Carbon\Carbon::today();

        // Kuhaon ang bookings ug i-load daan ang related room information.
        $bookings = bookingModel::with('room')

            // Iapil lamang ang bookings nga ang room iya sa landlord.
            ->whereHas('room', function ($query) use ($landlord_id, $dormID) {
                $query->where('fklandlordID', $landlord_id);

                // I-filter ang bookings base sa dorm kung adunay gipili.
                if ($dormID) {
                    $query->where('fkdormID', $dormID);
                }
            })

            // I-sort ang bookings gikan sa pinakabag-o.
            ->orderBy('created_at', 'desc')
            ->get();

        // Ibalik ang booking list isip JSON response.
        return response()->json([
            'status' => 'success',
            'bookings' => $bookings
        ]);
    }

    // Kuhaon ang dorm IDs ug dorm names nga iya sa landlord.
    public function getDormID($landlord_id)
    {
        // Pangitaon ang mga dorm base sa landlord ID.
        // Dorm ID ug dorm name ra ang kuhaon.
        $dorms = dormModel::where('fklandlordID', $landlord_id)
            ->get(['dormID', 'dormName']);

        // Ibalik ang dorm information ngadto sa frontend.
        return response()->json([
            'status' => 'success',
            'dorms' => $dorms
        ]);
    }

    // Kwentahon ang room profits base sa occupied rooms.
    public function getRoomProfits(Request $request, $landlord_id)
    {
        // Kuhaon ang date ug optional dorm ID gikan sa request.
        $dateParam = $request->query('date');
        $dormID = $request->query('dorm_id'); // Optional nga dorm filter.

        // Gamiton ang gipiling date o current date.
        $date = $dateParam ? \Carbon\Carbon::parse($dateParam) : \Carbon\Carbon::today();

        // Kuhaon ang rooms nga iya sa landlord ug na-create sa gipiling date o mas sayo pa.
        $query = roomModel::where('fklandlordID', $landlord_id)
            ->whereDate('created_at', '<=', $date);

        // Kung adunay gipiling dorm, i-filter ang room records base niini.
        if ($dormID) {
            $query->where('fkdormID', $dormID);
        }

        // Kuhaon ang tanang rooms nga nakasunod sa filters.
        $rooms = $query->get();

        // I-group ang rooms base sa room number aron malikayan ang duplicate entries.
        // Kwentahon ang total price sa occupied rooms sulod sa matag group.
        $roomsData = $rooms->groupBy('roomNumber')->map(function ($groupedRooms, $roomNumber) {

            // I-total ang presyo sa occupied rooms.
            $totalPrice = $groupedRooms->where('availability', 'Occupied')->sum('price');

            // Ibalik ang summarized information sa matag room number.
            return [
                'roomNumber' => $roomNumber,
                'profit' => $totalPrice,
                'availability' => $groupedRooms->first()->availability,
                'dormName' => $groupedRooms->first()->dorm->dormName ?? 'N/A',
                'created_at' => $groupedRooms->first()->created_at,
            ];
        })

        // I-sort ang rooms gikan sa pinakadako nga profit.
        ->sortByDesc('profit')
        ->values();

        // Kwentahon ang total profit sa tanang room groups.
        $totalProfit = $roomsData->sum('profit');

        // Ibalik ang total profit ug room profit breakdown.
        return response()->json([
            'status' => 'success',
            'total_profit' => $totalProfit,
            'data' => $roomsData
        ]);
    }

    // Kuhaon ang gender distribution sa mga tenants sa landlord.
    public function getGenderDistribution(Request $request, $landlord_id)
    {
        // Kuhaon ang date ug optional dorm ID gikan sa request.
        $dateParam = $request->query('date');
        $dormID = $request->query('dorm_id'); // Optional nga dorm filter.

        // Gamiton ang gipiling date o current date.
        $date = $dateParam ? \Carbon\Carbon::parse($dateParam) : \Carbon\Carbon::today();

        // Kuhaon ang approved tenants nga sakop sa landlord.
        // I-load usab ang ilang room information.
        $tenants = \App\Models\tenant\approvetenantsModel::with('room')

            // I-filter ang tenants base sa landlord ug optional dorm ID.
            ->whereHas('room', function ($q) use ($landlord_id, $dormID) {
                $q->where('fklandlordID', $landlord_id);

                if ($dormID) {
                    $q->where('fkdormID', $dormID);
                }
            })

            // Iapil lamang ang tenants nga ang move-in date kay parehas o mas sayo sa gipiling date.
            ->whereDate('moveInDate', '<=', $date)
            ->get();

        // I-group ang tenants base sa ilang gender ug ihap-on ang matag grupo.
        $genderCounts = $tenants->groupBy('gender')
            ->map(fn($g, $gender) => [
                'gender' => $gender,
                'count' => $g->count()
            ])
            ->values()
            ->toArray();

        // Ihap-on ang total number sa tenants.
        $total = $tenants->count();

        // Ibalik ang total tenants ug gender distribution.
        return response()->json([
            'status' => 'success',
            'total' => $total,
            'data' => $genderCounts
        ]);
    }

    // Mag-generate og kompleto nga landlord report ug i-export kini isip PDF.
    public function generateFullReport($landlordID, Request $request)
    {
        // Kuhaon ang optional report date gikan sa request.
        $selectedDate = $request->query('date');

        // Kuhaon ang approved reservations sa landlord.
        // I-load usab ang related room, dorm ug payment information.
        $reservations = reservationModel::with(['room.dorm', 'payment'])
            ->where('status', 'approved')
            ->whereHas('room', fn($q) => $q->where('fklandlordID', $landlordID))

            // Kung adunay gipiling date, iapil ang records nga na-create sa maong date o mas sayo pa.
            ->when($selectedDate, fn($q) => $q->whereDate('created_at', '<=', $selectedDate))
            ->get();

        // Kuhaon ang approved bookings sa landlord.
        // I-load usab ang related room, dorm ug payment information.
        $bookings = bookingModel::with(['room.dorm', 'payment'])
            ->where('status', 'approved')
            ->whereHas('room', fn($q) => $q->where('fklandlordID', $landlordID))

            // I-apply ang date filter kung adunay gipiling report date.
            ->when($selectedDate, fn($q) => $q->whereDate('created_at', '<=', $selectedDate))
            ->get();

        // Kwentahon ang total payment amount sa matag booking.
        $bookings->each(fn($b) => $b->total_amount = $b->payment->sum('amount'));

        // Kuhaon ang pinakabag-ong approved payment sa matag reservation.
        $reservations->each(function ($r) {

            // Pangitaon ang latest payment base sa created_at.
            $latestPayment = $r->payment()
                ->orderByDesc('created_at')
                ->first();

            // Ibutang ang payment amount isip total amount.
            // Gamiton ang zero kung walay payment record.
            $r->total_amount = $latestPayment->amount ?? 0;
        });

        // I-combine ang payments gikan sa reservations ug bookings.
        // Gamiton ang unique ID aron malikayan ang doble nga pag-ihap sa payment.
        $allPayments = $reservations->flatMap(fn($r) => $r->payment)
            ->merge($bookings->flatMap(fn($b) => $b->payment))
            ->unique('id');

        // Kwentahon ang total income gikan sa unique payments.
        $totalIncome = $allPayments->sum('amount');

        // Kuhaon ang file path sa logo nga gamiton sa PDF report.
        $logoPath = public_path('images/Logo/logo.png');

        // Andamon ang tanang data nga ipasa ngadto sa PDF view.
        $reportData = [
            'reservations' => $reservations,
            'bookings' => $bookings,
            'totalIncome' => $totalIncome,
            'landlordID' => $landlordID,

            // Kuhaon ang current date ug time base sa Philippine timezone.
            'reportDate' => Carbon::now('Asia/Manila')->format('F d, Y h:i A'),

            'logoPath' => $logoPath,
        ];

        // I-load ang full report Blade view ug himoon kini nga PDF.
        $pdf = PDF::loadView('landlord.reports.full-report', $reportData);

        // Ipakita ang PDF report sa browser imbes nga i-download diretso.
        return $pdf->stream("landlord-full-report-{$landlordID}.pdf");
    }
}
