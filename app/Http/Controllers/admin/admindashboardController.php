<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\tenant\tenantModel;
use App\Models\landlord\landlordModel;
use App\Models\landlord\paymentVerifiedModel;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class admindashboardController extends Controller
{
    // Ipakita ang admin dashboard page ug ipasa ang admin ID.
    public function adminDashboardIndex($admin_id)
    {
        return view('admin.auth.admindashboard', [
            'title' => 'Admin - Dashboard',
            'admin_id' => $admin_id,
        ]);
    }

    // Kuhaon ang total tenants, landlords, ug successful payment collection.
    public function getTotals()
    {
        $totals = [
            // Ihap sa tanan registered tenants.
            'totalTenants' => tenantModel::count(),

            // Ihap sa tanan registered landlords.
            'totalLandlords' => landlordModel::count(),

            // Kwentahon ang total amount sa successful payments.
            'totalCollection' => paymentVerifiedModel::where('status', 'success')->sum('amount'),
        ];

        // Ibalik ang dashboard totals isip JSON response.
        return response()->json($totals);
    }

    // Kuhaon ang total successful subscriptions ug total collection.
    public function getSubscribersTotal()
    {
        // Ihap sa tanan successful subscription payments.
        $totalSubscribers = DB::table('landlordVerifiedPayment')
            ->where('status', 'success')
            ->count();

        // Kwentahon ang total collection gikan sa successful payments.
        $totalCollection = DB::table('landlordVerifiedPayment')
            ->where('status', 'success')
            ->sum('amount');

        // Ibalik ang subscription totals isip JSON response.
        return response()->json([
            'totalSubscribers' => $totalSubscribers,
            'totalCollection' => $totalCollection,
        ]);
    }

    // Mag-generate og PDF report sa successful subscriptions kada bulan.
    public function generateSubscriptionReport()
    {
        // Kuhaon ang monthly subscription count ug total payment amount.
        $subscriptions = DB::table('landlordVerifiedPayment')
            ->selectRaw("DATE_FORMAT(created_at, '%M %Y') as month, COUNT(*) as count, SUM(amount) as total_amount")
            ->where('status', 'success')
            ->groupByRaw("DATE_FORMAT(created_at, '%M %Y')")
            ->orderByRaw("MIN(created_at)")
            ->get();

        // Kwentahon ang grand total income gikan sa tanang monthly records.
        $totalIncome = $subscriptions->sum('total_amount');

        // Kuhaon ang file path sa logo nga gamiton sa PDF report.
        $logoPath = public_path('images/Logo/logo.png');

        // I-load ang subscription report view ug ipasa ang required data.
        $pdf = Pdf::loadView(
            'admin.auth.reports.subscription-report',
            compact('subscriptions', 'logoPath', 'totalIncome')
        );

        // I-display ang generated PDF sa browser.
        return $pdf->stream("subscription_report.pdf");
    }
}
