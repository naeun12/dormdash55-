<?php
namespace App\Http\Controllers\tenant\auth\nav\nextpayment;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\landlord\bookingModel;
use App\Models\landlord\roomModel;
use App\Models\tenant\reservationModel;
use App\Models\tenant\tenantModel;
use App\Models\tenant\bookingpaymentModel;
use App\Models\tenant\reservationpaymentModel;
use App\Models\tenant\approvetenantsModel;
use App\Models\tenant\approvepaymentModel;
use App\Models\notificationModel;
use Carbon\Carbon;

class nextpaymentController extends Controller
{
    // Ipakita ang payment history page sa tenant.
    public function nextpaymentIndex($tenant_id)
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

        // Pangitaon ang tenant record sa database.
        $tenant = tenantModel::find($tenant_id);

        // Ibalik sa login page kung walay nakit-an nga tenant.
        if (!$tenant) {
            return redirect()->route('tenant-login')->with('error', 'Landlord not found.');
        }

        // Title nga gamiton sa payment history page.
        $title = 'Tenant Payments History - DormDash';

        // I-load ang payment history view uban sa gikinahanglang data.
        return view('tenant.auth.nav.nextpayment.nextpaymentdue', [
            'title' => $title,
            'tenant_id' => $tenant_id,
            'cssPath' => asset('css/tenantpage/auth/roomdetails.css'),
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    // Kuhaon ang payment history sa tenant base sa payment method ug date filter.
    public function paymentHistory(Request $request, $tenant_id)
    {
        // Kuhaon ang payment method ug petsa gikan sa request query.
        $method = $request->query('paymentMethod'); // Pananglitan: 'gcash'
        $chooseDate = $request->query('chooseDate'); // Format: 'YYYY-MM-DD'

        // Kuhaon ang tanang booking records nga iya sa tenant.
        $booking = bookingModel::where('fktenantID', $tenant_id)->get();

        // Kuhaon ang payment records sa mga booking sa tenant.
        $bookingBills = bookingpaymentModel::with('booking.room')
            ->whereIn('fkbookingID', $booking->pluck('bookingID'))

            // I-filter ang payment base sa gipiling payment method kung naa.
            ->when($method, function ($query) use ($method) {
                return $query->where('paymentType', $method);
            })

            // I-filter ang payment base sa gipiling petsa kung naa.
            ->when($chooseDate, function ($query) use ($chooseDate) {
                return $query->whereDate('created_at', $chooseDate);
            })

            // Ipakita una ang pinakabag-ong payment records.
            ->orderBy('created_at', 'desc')
            ->get();

        // Kuhaon ang tanang reservation records nga iya sa tenant.
        $reservation = reservationModel::where('fktenantID', $tenant_id)->get();

        // Kuhaon ang payment records sa mga reservation sa tenant.
        $reservationBills = reservationpaymentModel::with('reservation.room')
            ->whereIn('reservationID', $reservation->pluck('reservationID'))

            // I-filter ang payment base sa gipiling payment method kung naa.
            ->when($method, function ($query) use ($method) {
                return $query->where('paymentType', $method);
            })

            // I-filter ang payment base sa gipiling petsa kung naa.
            ->when($chooseDate, function ($query) use ($chooseDate) {
                return $query->whereDate('created_at', $chooseDate);
            })

            // Ipakita una ang pinakabag-ong payment records.
            ->orderBy('created_at', 'desc')
            ->get();

        // Kuhaon ang tanang approved tenant records nga iya sa tenant.
        $approve = approvetenantsModel::where('fktenantID', $tenant_id)->get();

        // Kuhaon ang payment records sa mga approved tenant.
        $approvetenantBills = approvepaymentModel::with('approvedTenant.room')
            ->whereIn('fkapprovedID', $approve->pluck('approvedID'))

            // I-filter ang payment base sa gipiling payment method kung naa.
            ->when($method, function ($query) use ($method) {
                return $query->where('paymentType', $method);
            })

            // I-filter ang payment base sa gipiling petsa kung naa.
            ->when($chooseDate, function ($query) use ($chooseDate) {
                return $query->whereDate('created_at', $chooseDate);
            })

            // Ipakita una ang pinakabag-ong payment records.
            ->orderBy('created_at', 'desc')
            ->get();

        // Ibalik ang tulo ka klase sa payment history isip JSON response.
        return response()->json([
            'booking_bills' => $bookingBills,
            'reservation_bills' => $reservationBills,
            'approve_bill' => $approvetenantBills
        ]);
    }

    // Kwentahon ang total payment sa tenant base sa payment method ug petsa.
    public function getTotalAmount(Request $request, $tenantID)
    {
        // Kuhaon ang payment method ug petsa gikan sa URL query parameters.
        $paymentMethod = $request->query('method');
        $chooseDate = $request->query('chooseDate'); // Gikan sa Axios request.

        // Kwentahon ang total amount sa tanang booking payments sa tenant.
        $bookingTotal = bookingpaymentModel::whereHas('booking', function ($q) use ($tenantID) {
            // Siguraduhon nga ang booking iya sa tenant nga gipangita.
            $q->where('fktenantID', $tenantID);
        })

        // I-apil lang ang payment nga parehas sa gipiling payment method kung naa.
        ->when($paymentMethod, function ($query) use ($paymentMethod) {
            return $query->where('paymentType', $paymentMethod);
        })

        // I-apil lang ang payment nga parehas sa gipiling petsa kung naa.
        ->when($chooseDate, function ($query) use ($chooseDate) {
            return $query->whereDate('created_at', $chooseDate);
        })

        // I-total ang amount sa tanang nakuhang booking payments.
        ->sum('amount');

        // Kwentahon ang total amount sa tanang reservation payments sa tenant.
        $reservationTotal = reservationpaymentModel::whereHas('reservation', function ($q) use ($tenantID) {
            // Siguraduhon nga ang reservation iya sa tenant nga gipangita.
            $q->where('fktenantID', $tenantID);
        })

        // I-apil lang ang payment nga parehas sa gipiling payment method kung naa.
        ->when($paymentMethod, function ($query) use ($paymentMethod) {
            return $query->where('paymentType', $paymentMethod);
        })

        // I-apil lang ang payment nga parehas sa gipiling petsa kung naa.
        ->when($chooseDate, function ($query) use ($chooseDate) {
            return $query->whereDate('created_at', $chooseDate);
        })

        // I-total ang amount sa tanang nakuhang reservation payments.
        ->sum('amount');

        // Kwentahon ang total amount sa tanang approved tenant payments.
        $approve = approvepaymentModel::whereHas('approvedTenant', function ($q) use ($tenantID) {
            // Siguraduhon nga ang approved tenant record iya sa tenant nga gipangita.
            $q->where('fktenantID', $tenantID);
        })

        // I-apil lang ang payment nga parehas sa gipiling payment method kung naa.
        ->when($paymentMethod, function ($query) use ($paymentMethod) {
            return $query->where('paymentType', $paymentMethod);
        })

        // I-apil lang ang payment nga parehas sa gipiling petsa kung naa.
        ->when($chooseDate, function ($query) use ($chooseDate) {
            return $query->whereDate('created_at', $chooseDate);
        })

        // I-total ang amount sa tanang nakuhang approved payments.
        ->sum('amount');

        // I-add ang total sa booking, reservation, ug approved payments.
        $total = $bookingTotal + $reservationTotal + $approve;

        // Ibalik ang matag total ug ang combined total isip JSON response.
        return response()->json([
            'bookingTotal' => $bookingTotal,
            'reservationTotal' => $reservationTotal,
            'approveTotal' => $approve,
            'totalAmount' => $total,
            'totalDue' => 10000,
        ]);
    }
}

