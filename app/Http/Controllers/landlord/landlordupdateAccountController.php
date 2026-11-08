<?php

namespace App\Http\Controllers\landlord;

use App\Http\Controllers\Controller;
use App\Models\landlord\landlordModel;
use Illuminate\Http\Request;
use App\Models\notificationModel;

class landlordupdateAccountController extends Controller
{
    // Ipakita ang landlord account update page.
    public function updateAccountIndex(Request $request, $landlord_id)
    {
        // Pangitaon ang landlord record gamit ang iyang ID.
        $landlord = landlordModel::find($landlord_id);

        // Kung walay makita nga landlord, ibalik sa previous page nga adunay error message.
        if (!$landlord) {
            return redirect()->back()->with('error', 'Landlord not found.');
        }

        // Kuhaon ang landlord ID gikan sa current session.
        $sessionLandlordId = session('landlord_id');

        // Kuhaon ang latest 5 notifications para sa logged-in landlord.
        $notifications = notificationModel::where('receiverID', $sessionLandlordId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Ihap-on ang unread notifications sa landlord.
        $unreadCount = notificationModel::where('receiverID', $landlord_id)
            ->where('isRead', false)
            ->count();

        // I-load ang account update view ug ipasa ang required data.
        return view('landlord.auth.landlordaccountupdate', [
            "title" => "Landlord - Update Account",
            'headerName' => 'Update Account',
            'landlord' => $landlord,
            'landlord_id' => $sessionLandlordId,
            'id' => $sessionLandlordId,
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    // Kuhaon ang landlord information para sa account update form.
    public function getlandlordData($landlord_id)
    {
        // Susihon kung adunay valid landlord ID nga gipasa.
        if (!$landlord_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Landlord ID not found.'
            ], 400);
        }

        // Pangitaon ang landlord record gamit ang iyang ID.
        $landlord = landlordModel::find($landlord_id);

        // Ibalik ang landlord information ug profile picture URL isip JSON response.
        return response()->json([
            'status' => 'success',
            'landlord' => $landlord,
            'landlordProfile' => $landlord->profilePicUrl
        ]);
    }

    // I-update ang basic account information ug profile picture sa landlord.
    public function updatelandlordAccount(Request $request, $landlord_id)
    {
        // Pangitaon ang landlord record gamit ang iyang ID.
        $landlord = landlordModel::find($landlord_id);

        // Kung walay makita nga landlord, ibalik ang 404 error response.
        if (!$landlord) {
            return response()->json([
                'status' => 'error',
                'message' => 'Landlord not found.'
            ], 404);
        }

        // I-validate ang personal information ug uploaded profile picture.
        $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'gender' => 'nullable|string',
            'profilePicUrl' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'firstname.required' => 'Please enter the firstname.',
            'lastname.required' => 'Please enter the lastname.',
        ]);

        // I-update ang basic personal information sa landlord.
        $landlord->firstname = $request->firstname;
        $landlord->lastname = $request->lastname;
        $landlord->gender = $request->gender;

        // I-update lamang ang profile picture kung adunay bag-ong image nga gi-upload.
        if ($request->hasFile('profilePicUrl')) {
            // Kuhaon ang uploaded image file.
            $image = $request->file('profilePicUrl');

            // Maghimo og unique filename gamit ang current timestamp.
            $imageName = time() . '_profile.' . $image->getClientOriginalExtension();

            // I-save ang image sulod sa public storage profile picture folder.
            $image->storeAs('public/uploads/profilePic', $imageName);

            // I-update ang profile picture path sa landlord record.
            $landlord->profilePicUrl = 'storage/uploads/profilePic/' . $imageName;
        }

        // I-save ang updated landlord information sa database.
        $landlord->save();

        // Ibalik ang success response ug updated landlord information.
        return response()->json([
            'status' => 'success',
            'message' => 'Landlord account updated successfully.',
            'landlord' => $landlord
        ]);
    }

    // I-update ang business permit ug government ID sa landlord.
    public function updateDocuments(Request $request, $landlord_id)
    {
        // Pangitaon ang landlord record; mo-return og 404 kung wala kini makita.
        $landlord = landlordModel::findOrFail($landlord_id);

        // I-validate ang uploaded business permit ug government ID.
        $request->validate([
            'businessPermit' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'governmentID' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // I-update ang business permit kung adunay bag-ong file nga gi-upload.
        if ($request->hasFile('businessPermit')) {
            // Kuhaon ang uploaded business permit file.
            $file = $request->file('businessPermit');

            // Maghimo og filename gamit ang current timestamp.
            $filename = time() . '_business_permit.' . $file->getClientOriginalExtension();

            // I-save ang file sulod sa public uploads/landlords folder.
            $file->move(public_path('uploads/landlords'), $filename);

            // I-update ang business permit file path sa database.
            $landlord->businessPermit = 'uploads/landlords/' . $filename;
        }

        // I-update ang government ID kung adunay bag-ong file nga gi-upload.
        if ($request->hasFile('governmentID')) {
            // Kuhaon ang uploaded government ID file.
            $file = $request->file('governmentID');

            // Maghimo og filename gamit ang current timestamp.
            $filename = time() . '_government_id.' . $file->getClientOriginalExtension();

            // I-save ang file sulod sa public uploads/landlords folder.
            $file->move(public_path('uploads/landlords'), $filename);

            // I-update ang government ID file path sa database.
            $landlord->govermentID = 'uploads/landlords/' . $filename;
        }

        // I-save ang updated document paths sa landlord record.
        $landlord->save();

        // Ibalik ang success response ug updated landlord information.
        return response()->json([
            'status' => 'success',
            'message' => 'Documents updated successfully.',
            'landlord' => $landlord
        ]);
    }
}
