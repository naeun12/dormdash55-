<?php
namespace App\Http\Controllers\tenant\auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\notificationModel;
use App\Models\landlord\dormModel;
use App\Models\tenant\tenantModel;
use App\Models\reviewandratingModel;

class reviewandfeedbackController extends Controller
{
    // Ipakita ang Reviews and Ratings page para sa tenant.
    public function reviewandrating($dormitory_id, $tenant_id)
    {
        // Kuhaon ang tenant ID nga naka-login gikan sa session.
        $sessionTenant_id = session('tenant_id');

        // Kuhaon ang pinakabag-o nga lima ka notifications sa tenant.
        $notifications = notificationModel::where('receiverID', $sessionTenant_id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Ihap-on ang wala pa mabasahing notifications sa tenant.
        $unreadCount = notificationModel::where('receiverID', $tenant_id)
            ->where('isRead', false)
            ->count();

        // Ibalik sa login page kung walay naka-login nga tenant.
        if (!$sessionTenant_id) {
            return redirect()->route('tenant-login')
                ->with('error', 'Please log in as a landlord.');
        }

        // Siguraduhon nga ang tenant ID sa URL parehas sa naka-login nga tenant.
        if ($tenant_id !== $sessionTenant_id) {
            return redirect()->route('tenant-login')
                ->with('error', 'Unauthorized access.');
        }

        // Pangitaon ang dormitory record base sa dormitory ID.
        $dorm = dormModel::find($dormitory_id);

        // Dugangan og usa ang views sa dormitory kung makita ang record.
        $dorm->increment('views');

        // Kuhaon ang tenant information gikan sa database.
        $tenant = tenantModel::find($tenant_id);

        // Ibalik sa login page kung wala makita ang tenant account.
        if (!$tenant) {
            return redirect()->route('tenant-login')
                ->with('error', 'Landlord not found.');
        }

        // I-load ang Reviews and Ratings page uban sa gikinahanglang data.
        return view('tenant.auth.ratingandreview', [
            'title' => 'Review and Rating - DormDash ',
            'dormitory_id' => $dormitory_id,
            'tenant_id' => $tenant_id,
            'tenant' => $tenant,
            'cssPath' => asset('css/tenantpage/auth/roomdetails.css'),
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    // Kuhaon ang tanang reviews ug ratings sa usa ka dormitory.
    public function fetchreviewandrating($dormitory_id)
    {
        // Kuhaon ang reviews apil ang tenant information gamit ang relationship.
        $reviews = reviewandratingModel::with('tenant')
            ->where('fkdormID', $dormitory_id)
            ->latest()
            ->get();

        // Kwentahon ang average rating sa tanang reviews.
        $averageRating = round($reviews->avg('rating') ?? 0, 1);

        // Ihap-on ang total number sa mga reviews.
        $totalReviewers = $reviews->count();

        // Sudlan sa star distribution gikan sa 5 stars hangtod 1 star.
        $starRatings = [];

        for ($i = 5; $i >= 1; $i--) {

            // Ihap-on kung pila ka reviews ang adunay parehas nga star rating.
            $count = $reviews->where('rating', $i)->count();

            // Kwentahon ang percentage sa matag star rating.
            $percentage = $totalReviewers > 0
                ? round(($count / $totalReviewers) * 100, 2)
                : 0;

            // Ibutang ang star rating, total count, ug percentage sa array.
            $starRatings[] = [
                'stars' => $i,
                'count' => $count,
                'percentage' => $percentage
            ];
        }

        // Ibalik ang reviews ug rating summary isip JSON response para sa Vue.
        return response()->json([
            'success' => true,
            'averageRating' => $averageRating,
            'totalReviewers' => $totalReviewers,
            'starRatings' => $starRatings,
            'reviews' => $reviews
        ]);
    }
}