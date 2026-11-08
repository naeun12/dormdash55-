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
    // Ipakita ang login page sa tenant.
    public function login()
    {
        $title = 'Login - DormDash';

        // I-load ang tenant login page uban sa title.
        return view('tenant.accountprocess.login', compact('title'));
    }

    // Ipakita ang registration page sa tenant.
    public function register()
    {
        $title = 'Register - DormDash';

        // I-load ang tenant registration page uban sa title.
        return view('tenant.accountprocess.register', compact('title'));
    }

    // Proseso sa pagrehistro og bag-ong tenant account.
    public function registerTenant(Request $request)
    {
        try {
            // I-validate ang tanang impormasyon nga gisubmit sa registration form.
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

            // Susiha kung adunay profile picture nga gi-upload sa tenant.
            if ($request->hasFile('profile_pic')) {
                // Kuhaon ang uploaded image gikan sa request.
                $image = $request->file('profile_pic');

                // Maghimo og filename gamit ang current timestamp ug file extension.
                $imageName = time() . '.' . $image->getClientOriginalExtension();

                // I-save ang profile picture sulod sa public storage folder.
                $image->storeAs('public/uploads/profilePic', $imageName);

                // Ibutang ang image path nga i-save sa database.
                $profilePicUrl = 'uploads/profilePic/' . $imageName;
            } else {
                // Walay profile picture nga gi-upload.
                $profilePicUrl = null;
            }

            // I-encrypt ang password aron dili plain text ang ma-save sa database.
            $passwordHash = Hash::make($request->input('password'));

            // Maghimo og unique UUID para sa tenant ID.
            $tenantId = Str::uuid();

            // I-verify kung sakto ug wala pa ma-expire ang OTP nga gisubmit.
            $verifyOtp = otpModel::where('email', $validated['email'])
                ->where('otpCode', $validated['codeotp'])
                ->where('otpExpiresAt', '>', now())
                ->first();

            // Hunongon ang registration kung invalid o expired ang OTP.
            if (!$verifyOtp) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid OTP Submission. Please try again.',
                    'data' => null
                ], 400);
            }

            // Maghimo og bag-ong tenant record gamit ang validated information.
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

            // Tangtangon ang OTP record human malampuson ang account registration.
            $verifyOtp->where('email', $validated['email'])->forceDelete();

            // Maghimo og welcome message para sa bag-ong tenant.
            $message = " Welcome to DormDash, {$tenant->firstname}! Your tenant account has been successfully registered. You can now log in to your DormDash account.";

            // Sulayi ang pagpadala og welcome SMS sa registered phone number.
            try {
                app(IprogSMSService::class)->sendSMS($tenant->phoneNumber, $message);
            } catch (\Exception $smsError) {
                // I-save sa Laravel log ang error kung mapakyas ang SMS.
                Log::error('Tenant registration SMS failed: ' . $smsError->getMessage());
            }

            // Sulayi ang pagpadala og welcome email sa registered email address.
            try {
                Mail::raw($message, function ($mail) use ($tenant) {
                    $mail->to($tenant->email)
                        ->subject('Welcome to DormDash - Registration Successful');
                });
            } catch (\Exception $mailError) {
                // I-save sa Laravel log ang error kung mapakyas ang email.
                Log::error('Tenant registration email failed: ' . $mailError->getMessage());
            }

            // Ibalik ang success response ug tenant information human sa registration.
            return response()->json([
                'status' => 'success',
                'message' => 'Registered Successfully!',
                'data' => [
                    'tenant' => $tenant
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {

            // Diri madawat ang validation exception kung adunay invalid nga input.

        } catch (\Exception $e) {
            // Ibalik ang error response kung adunay problema atol sa registration.
            return response()->json([
                'errors' => 'An error occurred during registration.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Mag-generate ug magpadala og OTP sa email para sa account verification.
    public function SendOtp(Request $request)
    {
        // I-validate ang email, phone number, postal code ug password.
        $validated = $request->validate([
            'email' => 'required|email|unique:tenants,email',
            'phonenumber' => 'required|string|unique:tenants,phonenumber|regex:/^\+?[0-9]{10,15}$/|max:11|min:11',
            'postalcode' => 'required|string|max:4|regex:/^[a-zA-Z0-9\s\-]+$/',
            'password' => 'required|string|min:8|confirmed',
        ], [
            // Custom error messages para sa email validation.
            'email.required' => 'The email field is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already registered.',

            // Custom error messages para sa phone number validation.
            'phonenumber.required' => 'The phone number field is required.',
            'phonenumber.regex' => 'Please enter a valid phone number (e.g., +1234567890).',
            'phonenumber.max' => 'Please enter a valid phone number (e.g., +1234567890).',
            'phonenumber.min' => 'Please enter a valid phone number (e.g., +1234567890).',
            'phonenumber.unique' => 'This phone number is already registered.',

            // Custom error messages para sa postal code validation.
            'postalcode.required' => 'The postal code field is required.',
            'postalcode.max' => 'The postal code cannot exceed 4 characters.',
            'postalcode.regex' => 'Please enter a valid postal code (e.g., alphanumeric with spaces or hyphens).',

            // Custom error messages para sa password validation.
            'password.required' => 'The password field is required.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
        ]);

        try {
            // Mag-generate og random nga unom ka digit nga OTP.
            $otpCode = rand(100000, 999999);

            // Ipadala ang OTP ngadto sa email gamit ang tenant email OTP template.
            Mail::to($validated['email'])->send(new tenantEmailOtp($otpCode));

            // Itakda nga usa ra ka minuto ang validity sa OTP.
            $expiresAt = now()->addMinutes(1);

            // I-save ang OTP ug expiration time sa database.
            otpModel::create([
                'email' => $validated['email'],
                'otpCode' => $otpCode,
                'otpExpiresAt' => $expiresAt,
            ]);

            // Ibalik ang success response ug timer para sa OTP verification.
            return response()->json([
                'status' => 'success',
                'message' => 'Check Your Gmail To Verify Your Account',
                'timer' => $expiresAt,
            ], 200);

            // Kwentahon ang nahibiling oras sa OTP ug i-schedule ang expired OTP cleanup job.
            $delay = $expiresAt->diffInSeconds(now());
            DeleteExpiredOtpsJob::dispatch()->delay($delay);

        } catch (\Exception $e) {
            // Ibalik ang error response kung mapakyas ang pagpadala o pag-save sa OTP.
            return response()->json([
                'message' => 'An unexpected error occurred. Please try again.',
            ], 500);
        }
    }

    // Mag-generate ug magpadala og bag-ong OTP kung gusto sa user nga mag-resend.
    public function resendOtp(Request $request)
    {
        // Siguraduhon nga valid ang email nga nadawat.
        $validated = $request->validate([
            'email' => 'required|email',
        ], [
            'email.exists' => 'No OTP record found for this email.',
        ]);

        try {
            // Pangitaon ang OTP record base sa email address.
            $otpRecord = otpModel::where('email', $validated['email']);

            // Susiha kung adunay OTP record para sa maong email.
            if (!$otpRecord) {
                return response()->json([
                    'message' => 'No OTP record found for this email',
                ]);
            }

            // Mag-generate og bag-ong unom ka digit nga OTP.
            $newOtp = rand(100000, 999999);

            // Itakda ang expiration time sa bag-ong OTP ngadto sa usa ka minuto.
            $newOtpExpiresAt = now()->addMinutes(1);

            // I-update ang existing OTP record gamit ang bag-ong OTP ug expiration.
            $otpRecord->update([
                'otpCode' => $newOtp,
                'otpExpiresAt' => $newOtpExpiresAt,
            ]);

            // Ipadala ang bag-ong OTP ngadto sa email sa user.
            Mail::to($validated['email'])->send(new tenantEmailOtp($newOtp));

            // Ibalik ang success response ug bag-ong OTP timer.
            return response()->json([
                'status' => 'success',
                'message' => 'A new OTP has been sent to your email.',
                'timer' => $newOtpExpiresAt
            ], 200);

        } catch (\Exception $ex) {
            // Ibalik ang error response kung mapakyas ang pag-resend sa OTP.
            return response()->json([
                'message' => 'An unexpected error occurred. Please try again.',
            ], 500);
        }
    }

    // Proseso sa pag-login sa tenant account.
    public function loginTenant(Request $request)
    {
        try {
            // I-validate ang email ug password nga gisubmit sa login form.
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ], [
                'email.required' => 'Please enter email address',
                'email.email' => 'Please enter a valid email address',
                'password.required' => 'Please enter your password',
            ]);

            // Pangitaon ang tenant account base sa email address.
            $user = tenantModel::where('email', $request->email)->first();

            // I-check kung naa ang account ug sakto ba ang password.
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json(['message' => 'Invalid credentials.'], 401);
            }

            // Dili tugotan ang pag-login kung deactivated ang tenant account.
            if ($user->is_deactivated == 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your account has been deactivated. Please contact support.'
                ], 403);
            }

            // I-authenticate ang tenant gamit ang tenant authentication guard.
            Auth::guard('tenant')->login($user);

            // I-save ang tenant information sulod sa session para magamit sa ubang pages.
            session([
                'tenant_logged_in' => true,
                'tenant_id' => $user->tenantID,
                'tenant_firstname' => $user->firstname,
                'tenant_lastname' => $user->lastname,
                'profilePicUrl' => $user->profilePicUrl
            ]);

            // Ibalik ang success response ug basic tenant information.
            return response()->json([
                'message' => 'Login successful',
                'status' => 'success',
                'tenant' => [
                    'id' => $user->tenantID,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {

            // Diri madawat ang validation exception kung invalid ang login input.
        }
    }

    // Proseso sa pag-logout sa tenant account.
    public function logout(Request $request)
    {
        // I-logout ang authenticated user.
        Auth::logout();

        // I-invalidate ang current session aron mawala ang session data.
        $request->session()->invalidate();

        // Mag-generate og bag-ong CSRF token para sa security.
        $request->session()->regenerateToken();

        // I-redirect ang user balik sa tenant login page.
        return redirect('/tenantLogin');
    }
}