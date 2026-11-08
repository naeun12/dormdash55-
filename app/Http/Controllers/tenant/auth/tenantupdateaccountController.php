<?php

namespace App\Http\Controllers\tenant\auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\notificationModel;
use App\Models\tenant\tenantModel;

class tenantupdateaccountController extends Controller
{
    // Ipakita ang update account page sa tenant.
    public function tenantaccountUpdate($tenant_id)
    {
        // Kuhaon ang tenant ID gikan sa active session.
        $sessionTenant_id = session('tenant_id');

        // Kuhaon ang pinakabag-o nga lima ka notifications sa tenant.
        $notifications = notificationModel::where('receiverID', $sessionTenant_id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Ihap ang wala pa mabasang notifications.
        $unreadCount = notificationModel::where('receiverID', $tenant_id)
            ->where('isRead', false)
            ->count();

        // Siguraduhon nga naka-login ang user isip tenant.
        if (!$sessionTenant_id) {
            return redirect()->route('tenant-login')->with('error', 'Please log in as a landlord.');
        }

        // Siguraduhon nga ang tenant nga gi-access parehas sa naka-login nga tenant.
        if ($tenant_id !== $sessionTenant_id) {
            return redirect()->route('tenant-login')->with('error', 'Unauthorized access.');
        }

        // Pangitaon ang tenant record gamit ang iyang ID.
        $tenant = tenantModel::find($tenant_id);

        // Mobalik sa login page kung walay tenant nga nakit-an.
        if (!$tenant) {
            return redirect()->route('tenant-login')->with('error', 'Landlord not found.');
        }

        // I-load ang update account page uban sa tenant details ug notifications.
        return view('tenant.auth.tenantupdateaccount', [
            'title' => 'Tenant update account  - DormDash',
            'tenant_id' => $tenant,
            'cssPath' => asset('css/tenantpage/auth/dormitorymap.css'),
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
            'demo' => $sessionTenant_id,
            'tenant_id' => $tenant_id,
        ]);
    }

    // Kuhaon ang tenant information para magamit sa frontend.
    public function getTenantData($tenant_id)
    {
        // I-check kung adunay tenant ID nga nadawat.
        if (!$tenant_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Landlord ID not found.'
            ], 400);
        }

        // Pangitaon ang tenant record base sa iyang ID.
        $tenant = tenantModel::find($tenant_id);

        // Ibalik ang tenant information ug profile picture isip JSON.
        return response()->json([
            'status' => 'success',
            'tenant' => $tenant,
            'tenantProfile' => $tenant->profile_pic_url
        ]);
    }

    // I-update ang personal information ug profile picture sa tenant.
    public function updatetenantAccount(Request $request, $tenant_id)
    {
        // Pangitaon ang tenant record nga i-update.
        $tenant = tenantModel::find($tenant_id);

        // I-validate ang mga impormasyon nga gisubmit sa tenant.
        $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'gender' => 'required|string',
            'profilePicUrl' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            // Custom error messages kung naay kulang nga required fields.
            'firstname.required' => 'Please enter the firstname.',
            'lastname.required' => 'Please enter the lastname.',
            'gender.required' => 'Please select the gender.',
        ]);

        // I-set ang default profile picture path isip null.
        $profilePicUrl = null;

        // Susiha kung adunay bag-ong profile picture nga gi-upload.
        if ($request->hasFile('profilePicUrl')) {

            // Kuhaon ang uploaded image gikan sa request.
            $image = $request->file('profilePicUrl');

            // Maghimo og unique filename gamit ang current timestamp ug file extension.
            $imageName = time() . '.' . $image->getClientOriginalExtension();

            // I-save ang image sulod sa storage/app/public/uploads/profilePic.
            $image->storeAs('public/uploads/profilePic', $imageName);

            // Path nga i-store sa database nga walay /storage prefix.
            $profilePicUrl = 'uploads/profilePic/' . $imageName;
        } else {
            // Walay bag-ong image nga gi-upload.
            $profilePicUrl = null;
        }

        // I-update ang firstname, lastname ug gender sa tenant.
        $tenant->firstname = $request->firstname;
        $tenant->lastname = $request->lastname;
        $tenant->gender = $request->gender;

        // Gamita ang bag-ong profile picture kung naa; kung wala, i-retain ang existing.
        $tenant->profilePicUrl = $profilePicUrl ?? $tenant->profilePicUrl;

        // I-save ang updated tenant information sa database.
        $tenant->save();

        // Ibalik ang success response ug updated tenant information.
        return response()->json([
            'status' => 'success',
            'message' => 'Your account updated successfully.',
            'tenant' => $tenant
        ]);
    }
}
