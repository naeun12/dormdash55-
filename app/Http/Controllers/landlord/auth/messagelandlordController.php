<?php
namespace App\Http\Controllers\landlord\auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\landlord\landlordModel;
use App\Models\otpModel;
use App\Jobs\DeleteExpiredOtpsJob;
use App\Services\IprogSMSService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\tenantEmailOtp;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
class landlordaccountprocessController extends Controller
{
public function landlordRegister()
    {
        return view('landlord.accountprocess.register', ['title' => 'Landlord - Register']);
    }
     public function landlordLogin()
    {
        return view('landlord.accountprocess.login', ['title' => 'Landlord - login']);
    }


    public function personalDetails(Request $request)
    {
        try {
            $validated = $request->validate([
               'firstname' => 'required|string|regex:/^[A-Za-z]+( [A-Za-z]+)*$/',
                'lastname' => 'required|string|regex:/^[A-Za-z]+( [A-Za-z]+)*$/',
                'email' => 'required|email|unique:landlords,email',
                'phonenumber' => 'required|string|unique:landlords,phonenumber|regex:/^\+?[0-9]{10,15}$/|size:11',
                'password' => 'required|string|min:8|confirmed',
                'profilePic' => 'required|image|mimes:jpg,png,jpeg|max:2048',
                'gender' => 'required|string',
            ], [
                'firstname.required' => 'Please enter your first name.',
                'lastname.required' => 'Please enter your last name.',
                'email.required' => 'Please enter your email.',
                'email.unique' => 'This email is already taken',
                'phonenumber.required' => 'Please enter your phone number.',
                'phonenumber.unique' => 'This phone number is already used',
                'phonenumber.regex' => 'Please enter a valid 11-digit phone number.',
                'phonenumber.size' => 'Phone number must be exactly 11 digits',
                'password.required' => 'Please enter your password.',
                'password.confirmed' => 'Password confirmation does not match',
                'profilePic.required' => 'Please upload your profile picture',
                'profilePic.image' => 'The profile picture must be an image',
                'profilePic.mimes' => 'Allowed image types: jpg, jpeg, png',
                'profilePic.max' => 'Image size must not exceed 2MB',
                'gender' => 'Please enter your gender.',
            ]);
           
                return response()->json([
                    'status' => 'success',
                    'message' => 'Validation passed and image uploaded successfully.'
                ], 200);
       
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation errors occurred.',
                'errors' => $e->errors(),
            ], 422);


        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


   
public function IdentityVerifaction(Request $request)
{
    // Validate before entering try-catch.
    $validated = $request->validate([
        'governmentIdPic' => 'required|image|mimes:jpg,png,jpeg,webp|max:2048',
    ], [
        'governmentIdPic.required' => 'Please upload a government ID picture.',
        'governmentIdPic.image' => 'The file must be an image.',
        'governmentIdPic.mimes' => 'Only JPG, PNG, JPEG, and WebP images are allowed.',
        'governmentIdPic.max' => 'The image may not be greater than 2MB.',
    ]);


    try {
        $image = $request->file('governmentIdPic');


        // Python FastAPI endpoint
        $pythonApiUrl = env(
            'ID_AI_API_URL',
            'https://dormdashai-production.up.railway.app/analyze'
        );


        // Send image to Python
        $response = Http::timeout(90)
            ->attach(
                'file',
                file_get_contents($image->getRealPath()),
                $image->getClientOriginalName()
            )
            ->post($pythonApiUrl);  


        // Python API error
        if (!$response->successful()) {
       


            return response()->json([
                'status' => 'error',
                'message' => 'Unable to analyze the ID image. Please try again.',
            ], 502);
        }


        $aiResult = $response->json();


        // Check if response is valid JSON
        if (!is_array($aiResult)) {
            return response()->json([
                'status' => 'error',
                'message' => 'The AI service returned an invalid response.',
            ], 502);
        }


        // REJECT: Not a Philippine government ID
        if (($aiResult['verification_status'] ?? null) === 'invalid_id') {
            return response()->json([
                'status' => 'error',
                'message' => $aiResult['message']
                    ?? 'The uploaded image is not a valid Philippine government ID.',
                'analysis' => $aiResult['analysis'] ?? null,
                'verification_status' => 'invalid_id',
                'recommended_action' => 'reject',
            ], 422);
        }


        // Check AI success
        if (($aiResult['success'] ?? false) !== true) {
            return response()->json([
                'status' => 'error',
                'message' => $aiResult['message'] ?? 'AI analysis failed.',
            ], 502);
        }


        // ACCEPT: Philippine ID detected, manual review required
        return response()->json([
            'status' => 'success',
            'message' => $aiResult['message']
                ?? 'ID image analysis completed.',
            'analysis' => $aiResult['analysis'] ?? null,
            'verification_status' => 'pending',
            'recommended_action' => 'manual_review',
        ], 200);


    } catch (\Illuminate\Http\Client\ConnectionException $e) {




        return response()->json([
            'status' => 'error',
            'message' => 'The AI service is unavailable. Please try again later.',
        ], 503);


    } catch (\Exception $e) {




        return response()->json([
            'status' => 'error',
            'message' => 'An unexpected error occurred. Please try again.',
        ], 500);
    }
}




    public function businessPermitValidation(Request $request)
    {
        try {
            $validated = $request->validate([
                'businessPermitPic' => 'required|image|mimes:jpg,png,jpeg|max:2048',
                'email' => 'required|email|unique:landlords,email',
            ], [
                'businessPermitPic.required' => 'Please upload a business permit image.',
                'businessPermitPic.image' => 'The file must be an image.',
                'businessPermitPic.mimes' => 'Only JPG, PNG, and JPEG images are allowed.',
                'businessPermitPic.max' => 'The image may not be greater than 2MB.',
            ]);


            $otpCode = rand(100000, 999999);
                        // $otpCode = '123456';


            $expiresAt = now()->addMinutes(1);


             Mail::to($validated['email'])->send(new tenantEmailOtp($otpCode));
             otpModel::create([
                'email' => $validated['email'],
                'otpCode' => $otpCode,
                'otpExpiresAt' => $expiresAt,
                'role' => 'landlord',
            ]);
           


            $delay = $expiresAt->diffInSeconds(now());
            DeleteExpiredOtpsJob::dispatch()->delay($delay);


            return response()->json([
                'status' => 'success',
                'message' => 'OTP has been sent to your Gmail.',
                'timer' => $expiresAt,
            ], 200);


        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->errors(),
            ], 422);


        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function RegisterLandlord(Request $request)
    {
        try
        {
            $validated = $request->validate([
                'firstname' => 'required|string',
                'lastname' => 'required|string',
                'email' => 'required|email|unique:landlords,email',
                'phonenumber' => 'required|string|unique:landlords,phonenumber|regex:/^\+?[0-9]{10,15}$/|size:11',
                'password' => 'required|string|min:8|confirmed',
                'gender' =>   'required|string',
                'profilePic' => 'required|image|mimes:jpg,png,jpeg|max:2048',
                'businessPermitPic' => 'required|image|mimes:jpg,png,jpeg|max:2048',
                'governmentIdPic' => 'required|image|mimes:jpg,png,jpeg|max:2048',
                'codeotp' => 'required|digits:6',
            ]);
            $profilePicPath = null;
            $businessPermitPath = null;
            $governmentIdPath = null;
           if ($request->hasFile('profilePic')) {
            $image = $request->file('profilePic');
            $imageName = time() . '_profile.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('public/uploads/profilePic', $imageName);
            $profilePicPath = 'storage/uploads/profilePic/' . $imageName;
          }


            if ($request->hasFile('businessPermitPic')) {
                $image = $request->file('businessPermitPic');
                $imageName = time() . '_permit.' . $image->getClientOriginalExtension();
                $imagePath = $image->storeAs('public/uploads/businessPermitPic', $imageName);
                $businessPermitPath = 'storage/uploads/businessPermitPic/' . $imageName;
            }


            if ($request->hasFile('governmentIdPic')) {
                $image = $request->file('governmentIdPic');
                $imageName = time() . '_govid.' . $image->getClientOriginalExtension();
                $imagePath = $image->storeAs('public/uploads/governmentIdPic', $imageName);
                $governmentIdPath = 'storage/uploads/governmentIdPic/' . $imageName;
            }
              $landlordID = Str::uuid();
               $verifyOtp = otpModel::where('email',$validated['email'])
            ->where('otpCode',$validated['codeotp'])
            ->where('otpExpiresAt', '>', now())
            ->first();
            // Store the tenant data
            if(!$verifyOtp)
            {
                  return response()->json([
                'status' => 'error',
                'message' => 'Invalid OTP Submission. Please try again.',
                'data' => null


            ],400);
        }
            $create = landlordModel::create([
                'landlordID' => $landlordID,
                'firstname' => $validated['firstname'],
                'lastname' => $validated['lastname'],
                'email' => $validated['email'],
                'phoneNumber' => $validated['phonenumber'],
                'gender' => $validated['gender'],
                'password' => bcrypt($validated['password']),
                'profilePicUrl' => $profilePicPath,
                'govermentID' => $governmentIdPath,
                'businessPermit' => $businessPermitPath,
                ]);
                 $verifyOtp->where('email',$validated['email'])->forceDelete();
                 $message = "Welcome to DormDash, {$create->firstname}! Your landlord account has been successfully registered. You can now log in to your DormDash account.";
        try {
            app(IprogSMSService::class)->sendSMS(
                $create->phoneNumber,
                $message
            );
        } catch (\Exception $smsError) {
            Log::error('Landlord registration SMS failed: ' . $smsError->getMessage());
        }
        try {
            Mail::raw($message, function ($mail) use ($create) {
                $mail->to($create->email)
                    ->subject('Welcome to DormDash - Landlord Registration Successful');
            });
        } catch (\Exception $mailError) {
            Log::error('Landlord registration email failed: ' . $mailError->getMessage());
        }
                return response()->json([
                    'status' => 'success',
                    'message' => 'Welcome aboard! DormDash is happy to have you.',
                ],200);
           


        }
        catch(\Illuminate\Validation\ValidationException $e)
        {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);


        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function resendOtp(Request $request)
{
    $validated = $request->validate([
         'email' => 'required|email',
    ],
    [  
        'email.exists' => 'No OTP record found for this email.',
    ]);


         $otpRecord = otpModel::where('email',$validated['email']);
    if(!$otpRecord)
    {
        return response()->json([
            'message' => 'No OTP record found for this email',
        ]);
    }
                $newOtp = rand(100000, 999999);
                $newOtpExpiresAt = now()->addMinutes(1);
                   $otpRecord->update([
            'otpCode' => $newOtp,
            'otpExpiresAt' => $newOtpExpiresAt,
        ]);
                Mail::to($validated['email'])->send(new tenantEmailOtp($newOtp));
                 return response()->json([
            'status' => 'success',
            'message' => 'A new OTP has been sent to your email.',
            'timer' => $newOtpExpiresAt
        ], 200);


   
   


}
public function loginLandlord(Request $request)
{
    try{
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
   
        $user = landlordModel::where('email', $request->email)->first();
   
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }
          if ($user->is_deactivated == 1) {
            return response()->json([
                'status' => 'error',
                'message' => 'Your account has been deactivated. Please contact support.'
            ], 403);
        }
    Auth::guard('landlord')->login($user);


    session([
        'landlord_logged_in' => true,
        'landlord_id' => $user->landlordID,
        'landlord_firstname' => $user->firstname,
        'landlord_lastname' => $user->lastname,
        'landlord_avatar' => $user->profilePicUrl
    ]);
   
        return response()->json([
            'message' => 'Login successful',
            'status' => 'success',
            'landlord' => [
                'id' => $user->landlordID,
                'firstname' =>$user->firstname,
                'lastname' =>$user->lastname,
            ],
   
        ]);
    }
   catch(\Illuminate\Validation\ValidationException $e)
{
    return response()->json([
        'status' => 'error',
        'errors' => $e->errors(),  // use 'errors' key instead of 'message'
    ], 422);
}
 catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
   
}
public function logoutlandlord(Request $request)
{
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/landlordLogin');
}




}



