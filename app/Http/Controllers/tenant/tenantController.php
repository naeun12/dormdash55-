<?php
namespace App\Http\Controllers\tenant;
use App\Models\tenant\tenantModel;
use App\Models\otpModel;
use App\Jobs\DeleteExpiredOtpsJob;
use App\Services\IprogSMSService;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\tenantEmailOtp;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;


   use Illuminate\Support\Facades\Storage;


use Carbon\Carbon;


class tenantController extends Controller
{
    public function login()
    {
        $title = 'Login - DormDash';
        return view('tenant.accountprocess.login', compact('title'));


    }


    public function register()
    {
        $title = 'Register - DormDash';
        return view('tenant.accountprocess.register', compact('title'));
    }
    public function registerTenant(Request $request)
    {
        try {
            $validated = $request->validate([
                'firstname' => 'required|string|max:55',
                'lastname' => 'required|string|max:55',
                'password' => 'required|string|min:8|confirmed',
                'email' => 'required|email|unique:tenants,email',
                'phonenumber' => 'required|string|unique:tenants,phonenumber|regex:/^\+?[0-9]{10,15}$/',
                'gender' => 'required|in:Male,Female',
                'city' => 'required|string|max:255',
                'province' => 'required|string|max:255',
                'region' => 'required|string|max:255',
                'postalcode' => 'required|string|max:4|regex:/^[a-zA-Z0-9\s\-]+$/',
                'currentaddress' => 'required|string|max:255',
                'codeotp' => 'required|digits:6',
                'profile_pic' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            ]);
            if ($request->hasFile('profile_pic')) {
            $image = $request->file('profile_pic');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/uploads/profilePic', $imageName);
            $profilePicUrl = 'uploads/profilePic/' . $imageName;
        } else {
            $profilePicUrl = null;
             }
            $passwordHash = Hash::make($request->input('password'));
            $tenantId = Str::uuid();
            $verifyOtp = otpModel::where('email',$validated['email'])
            ->where('otpCode',$validated['codeotp'])
            ->where('otpExpiresAt', '>', now())
            ->first();
            if(!$verifyOtp)
            {
                  return response()->json([
                'status' => 'error',
                'message' => 'Invalid OTP Submission. Please try again.',
                'data' => null
            ],400);
            }
             $tenant = tenantModel::create([
                'tenantID' => $tenantId,
                'firstname' => $validated['firstname'],
                'lastname' => $validated['lastname'],
                'email' => $validated['email'],
                'password' => $passwordHash,
                'phoneNumber' => $validated['phonenumber'],
                'gender' => $validated['gender'],
                'region' => $validated['region'],
                'city' => $validated['city'],
                'province' => $validated['province'],
                'postalCode' => $validated['postalcode'],
                'currentAddress' => $validated['currentaddress'],
                'profilePicUrl' => $profilePicUrl,
            ]);
            $verifyOtp->where('email',$validated['email'])->forceDelete();
            $message = " Welcome to DormDash, {$tenant->firstname}! Your tenant account has been successfully registered. You can now log in to your DormDash account.";
            try{
                app(IprogSMSService::class)->sendSMS($tenant->phoneNumber, $message);
            }
            catch (\Exception $smsError) {
                Log::error('Tenant registration SMS failed: ' . $smsError->getMessage());
            }
             try {
                Mail::raw($message, function ($mail) use ($tenant) {
                    $mail->to($tenant->email)
                        ->subject('Welcome to DormDash - Registration Successful');
                });
            } catch (\Exception $mailError) {
                Log::error('Tenant registration email failed: ' . $mailError->getMessage());
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Registered Successfully!',
                'data' => [
                  'tenant' => $tenant
                ]
            ],200);
        } catch (\Illuminate\Validation\ValidationException $e) {
       
    } catch (\Exception $e) {
        return response()->json([
            'errors' => 'An error occurred during registration.',
            'message' => $e->getMessage()
        ], 500);
    }
    }
       public function SendOtp(Request $request)
    {
        $validated = $request->validate([
        'email' => 'required|email|unique:tenants,email',
        'phonenumber' => 'required|string|unique:tenants,phonenumber|regex:/^\+?[0-9]{10,15}$/|max:11|min:11',
        'postalcode' => 'required|string|max:4|regex:/^[a-zA-Z0-9\s\-]+$/',
        'password' => 'required|string|min:8|confirmed',
    ], [
       
         'email.required' => 'The email field is required.',
        'email.email' => 'Please enter a valid email address.',
        'email.unique' => 'This email is already registered.',


        'phonenumber.required' => 'The phone number field is required.',
        'phonenumber.regex' => 'Please enter a valid phone number (e.g., +1234567890).',
        'phonenumber.max' => 'Please enter a valid phone number (e.g., +1234567890).',
        'phonenumber.min' => 'Please enter a valid phone number (e.g., +1234567890).',
        'phonenumber.unique' => 'This phone number is already registered.',


        'postalcode.required' => 'The postal code field is required.',
        'postalcode.max' => 'The postal code cannot exceed 4 characters.',
        'postalcode.regex' => 'Please enter a valid postal code (e.g., alphanumeric with spaces or hyphens).',


        'password.required' => 'The password field is required.',
        'password.min' => 'The password must be at least 8 characters.',
        'password.confirmed' => 'The password confirmation does not match.',
       


    ]);
    try {
        $otpCode = rand(100000, 999999);
        Mail::to($validated['email'])->send(new tenantEmailOtp($otpCode));
        $expiresAt = now()->addMinutes(1);
        otpModel::create([
            'email' => $validated['email'],
            'otpCode' => $otpCode,
            'otpExpiresAt' => $expiresAt,
        ]);
        return response()->json([
            'status' => 'success',
            'message' => 'Check Your Gmail To Verify Your Account',
            'timer' => $expiresAt,
        ], 200);
        $delay = $expiresAt->diffInSeconds(now());
        DeleteExpiredOtpsJob::dispatch()->delay($delay);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'An unexpected error occurred. Please try again.',
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
    try{
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
        catch(\Exception $ex)
    {  
        return response()->json([
            'message' => 'An unexpected error occurred. Please try again.',
        ], 500);
   
}
}
public function loginTenant(Request $request)
{
    try
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'Please enter email address',
            'email.email' => 'Please enter a valid email address',
            'password.required' => 'Please enter your password',
        ]);
   
        $user = tenantModel::where('email', $request->email)->first();
   
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }
          if ($user->is_deactivated == 1) {
            return response()->json([
                'status' => 'error',
                'message' => 'Your account has been deactivated. Please contact support.'
            ], 403);
        }
    Auth::guard('tenant')->login($user);


    session([
        'tenant_logged_in' => true,
        'tenant_id' => $user->tenantID,
        'tenant_firstname' => $user->firstname,
        'tenant_lastname' => $user->lastname,
        'profilePicUrl' => $user->profilePicUrl
    ]);
   
        return response()->json([
            'message' => 'Login successful',
            'status' => 'success',
            'tenant' => [
                'id' => $user->tenantID,
                'firstname' =>$user->firstname,
                'lastname' =>$user->lastname,
            ],
   
        ]);
    }
    catch(\Illuminate\Validation\ValidationException $e)
    {


    }
   
}
public function logout(Request $request)
{
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/tenantLogin');
}




   


}









