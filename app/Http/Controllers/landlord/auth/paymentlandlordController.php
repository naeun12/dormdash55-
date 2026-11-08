<?php
namespace App\Http\Controllers\landlord\auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\notificationModel;
use App\Models\landlord\landlordModel;
use App\Models\tenant\tenantModel;
use App\Models\landlord\paymentVerifiedModel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use App\Services\IprogSMSService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class paymentlandlordController extends Controller
{
    // I-display ang payment verification page sa landlord.
    public function paymentLandlordindex($landlord_id)
    {
        // Kuhaa ang landlord ID nga naa sa current session.
        $sessionLandlordId = session('landlord_id');

        // Kuhaa ang pinakabag-ong lima ka notifications sa landlord.
        $notifications = notificationModel::where('receiverID', $sessionLandlordId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // I-count ang unread notifications sa landlord.
        $unreadCount = notificationModel::where('receiverID', $landlord_id)
            ->where('isRead', false)
            ->count();

        // I-check kung naka-login ba ang landlord.
        if (!$sessionLandlordId) {
            return redirect()->route('loginLandlord')->with('error', 'Please log in as a landlord.');
        }

        // Siguraduhon nga ang gi-access nga landlord ID parehas sa session ID.
        if ($landlord_id !== $sessionLandlordId) {
            return redirect()->route('loginLandlord')->with('error', 'Unauthorized access.');
        }

        // Pangitaa ang landlord record gamit ang iyang ID.
        $landlord = landlordModel::find($landlord_id);

        // Kung walay makita nga landlord, ibalik sa login page.
        if (!$landlord) {
            return redirect()->route('loginLandlord')->with('error', 'Landlord not found.');
        }

        // I-load ang payment page uban sa landlord ug notification data.
        return view('landlord.auth.paymentLandlord', [
            "title" => "Landlord Payment Account",
            "headerName" => "Landlord Payment Account Verification",
            'landlord' => $landlord,
            'landlord_id' => $landlord_id,
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    // I-process ang payment verification request gamit ang PayMongo.
    public function verifyPaymentLandlord(Request $request)
    {
        // Kuhaa ang landlord ID gikan sa route parameter.
        $landlord_id = $request->route('landlord_id');

        // Kuhaa ang previous page URL.
        $paymentPageUrl = url()->previous();

        // I-validate ang email ug payment amount nga gisulod sa user.
        $request->validate([
            'paymentEmail' => 'required|email',
            'amount' => 'required|numeric|min:1',
        ]);

        try {
            // I-convert ang amount ngadto sa centavos kay mao kini ang format nga gikinahanglan sa PayMongo.
            $amountInCents = $request->amount * 100;

            // Magpadala og checkout session request ngadto sa PayMongo API.
            $response = Http::withBasicAuth(env('PAYMONGO_SECRET_KEY'), '')
                ->post('https://api.paymongo.com/v1/checkout_sessions', [
                    'data' => [
                        'attributes' => [
                            'line_items' => [[
                                'name' => 'DormDash Account Upgrade',
                                'quantity' => 1,
                                'currency' => 'PHP',
                                'amount' => $amountInCents,
                            ]],
                            // GCash ang payment method nga gamiton.
                            'payment_method_types' => ['gcash'],

                            // URL nga adtoan human sa successful o cancelled payment.
                            'success_url' => url('/landlord/payment-success'),
                            'cancel_url' => url('/landlord/payment-cancel'),

                            // Email address nga gamiton sa checkout.
                            'customer_email' => $request->paymentEmail,
                        ],
                    ],
                ]);

            // Kung napakyas ang PayMongo request, ibalik ang API error response.
            if ($response->failed()) {
                return response()->json(['error' => $response->json()], 400);
            }

            // Kuhaa ang checkout URL nga gigenerate sa PayMongo.
            $checkoutUrl = $response->json()['data']['attributes']['checkout_url'];

            // Ibalik ang checkout URL aron ma-redirect ang user sa payment page.
            return response()->json([
                'checkout_url' => $checkoutUrl
            ]);

        } catch (\Exception $e) {
            // Kung naay exception, ibalik ang error message ug HTTP 500 status.
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Tawagon human ma-redirect balik ang landlord gikan sa successful payment.
    public function paymentSuccess(Request $request)
    {
        // Kuhaa ang landlord ID gikan sa active session.
        $landlord_id = session('landlord_id');

        // I-check kung naa bay naka-login nga landlord.
        if (!$landlord_id) {
            return redirect()->route('payment.landlord')
                ->with('error', 'Unauthorized action. Please log in as a landlord.');
        }

        // Pangitaa ang landlord record sa database.
        $landlord = landlordModel::find($landlord_id);

        // Kung walay landlord record, ibalik sa payment page.
        if (!$landlord) {
            return redirect()->route('payment.landlord')
                ->with('error', 'Landlord account not found.');
        }

        // I-update ang landlord account isip verified.
        $landlord->isVerified = 1;
        $landlord->save();

        // I-save ang payment verification record sa database.
        paymentVerifiedModel::create([
            'fklandlordID' => $landlord_id,
            'email' => $landlord->email,
            'amount' => '500',
            'paymongo_id' => null,
            'status' => 'success',
            'paymentMethod' => 'gcash',
            'referenceNumber' => null,

            // I-save ang data nga nadawat gikan sa payment success request.
            'responsePayload' => json_encode($request->all()),
        ]);

        // Message nga gamiton sa SMS ug email notification.
        $message = "DormDash: Your ₱500 landlord verification payment was successful. Your landlord account is now verified.";

        // Maghimo og in-app notification para mahibaw-an sa landlord nga verified na ang account.
        $notifications = notificationModel::create([
            'senderID' => $landlord_id,
            'senderType' => 'landlord',
            'receiverID' => $landlord_id,
            'receiverType' => 'landlord',
            'title' => 'Landlord Account Verified',
            'message' => 'Your ₱500 verification payment was successful. Your landlord account is now verified.',
            'isRead' => false,
            'readAt' => null,
        ]);

        // I-broadcast ang notification aron ma-receive kini sa connected clients.
        broadcast(new \App\Events\NewNotificationEvent($notifications));

        // Ipadala ang SMS notification sa registered phone number sa landlord.
        try {
            // I-check kung naa bay registered phone number.
            if (!empty($landlord->phoneNumber)) {
                app(IprogSMSService::class)->sendSMS(
                    $landlord->phoneNumber,
                    $message
                );
            }
        } catch (\Throwable $e) {
            // I-record sa Laravel log kung napakyas ang SMS sending.
            Log::error('Landlord verification SMS failed: ' . $e->getMessage());
        }

        // Ipadala ang email notification sa registered email address sa landlord.
        try {
            // I-check kung naa bay registered email address.
            if (!empty($landlord->email)) {
                Mail::raw($message, function ($mail) use ($landlord) {
                    $mail->to($landlord->email)
                        ->subject('DormDash - Landlord Account Verified');
                });
            }
        } catch (\Throwable $e) {
            // I-record sa Laravel log kung napakyas ang email sending.
            Log::error('Landlord verification email failed: ' . $e->getMessage());
        }

        // Ibalik ang landlord sa payment page nga adunay successful status.
        return redirect()->route('payment.landlord', [
            'landlord_id' => $landlord_id,
            'status' => 'success'
        ]);
    }

    // Tawagon kung gikansela sa landlord ang payment process.
    public function paymentCancel(Request $request)
    {
        // Kuhaa ang landlord ID gikan sa current session.
        $landlord_id = session('landlord_id');

        // Ibalik ang landlord sa payment page nga adunay cancelled status.
        return redirect()->route('payment.landlord', [
            'landlord_id' => $landlord_id,
            'status' => 'cancelled'
        ]);
    }

    // Kuhaa ang landlord information ug i-return isip JSON response.
    public function getLandlordData(Request $request, $landlord_id)
    {
        // Pangitaa ang landlord gamit ang iyang ID.
        $landlord = landlordModel::find($landlord_id);

        // Kung walay makita nga landlord, ibalik ang 404 error.
        if (!$landlord) {
            return response()->json(['error' => 'Landlord not found'], 404);
        }

        // Ibalik ang landlord information isip JSON.
        return response()->json(['landlord' => $landlord]);
    }
}
