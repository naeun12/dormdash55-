<?php

namespace App\Http\Controllers\landlord\auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\landlord\landlordModel;
use App\Models\otpModel;
use App\Jobs\DeleteExpiredOtpsJob;
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
    // Ipakita ang registration page para sa landlord.
    public function landlordRegister()
    {
        return view('landlord.accountprocess.register', ['title' => 'Landlord - Register']);
    }

    // Ipakita ang login page para sa landlord.
    public function landlordLogin()
    {
        return view('landlord.accountprocess.login', ['title' => 'Landlord - login']);
    }

    // I-validate ang personal information nga gisubmit sa landlord.
    public function personalDetails(Request $request)
    {
        try {
            // Susiha kung kompleto ug sakto ang format sa tanang input.
            $validated = $request->validate([
                'firstname' => 'required|string|regex:/^[A-Za-z]+( [A-Za-z]+)*$/',
                'lastname' => 'required|string|regex:/^[A-Za-z]+( [A-Za-z]+)*$/',
                'email' => 'required|email|unique:landlords,email',
                'phonenumber' => 'required|string|unique:landlords,phonenumber|regex:/^\+?[0-9]{10,15}$/|size:11',
                'password' => 'required|string|min:8|confirmed',
                'profilePic' => 'required|image|mimes:jpg,png,jpeg|max:2048',
                'gender' => 'required|string',
            ], [
                // Custom error messages nga ipakita kung dili makapasar sa validation.
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

            // Ibalik ang success response kung nakapasar ang tanang input.
            return response()->json([
                'status' => 'success',
                'message' => 'Validation passed and image uploaded successfully.'
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Ibalik ang validation errors aron mahibal-an sa frontend ang sayop nga fields.
            return response()->json([
                'status' => 'error',
                'message' => 'Validation errors occurred.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            // Ibalik ang server error kung adunay wala damhang problema.
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Ipa-analyze sa AI service ang government ID nga gi-upload sa landlord.
    public function IdentityVerifaction(Request $request)
    {
        // I-validate una ang government ID image sa dili pa ipadayon ang proseso.
        $validated = $request->validate([
            'governmentIdPic' => 'required|image|mimes:jpg,png,jpeg,webp|max:2048',
        ], [
            'governmentIdPic.required' => 'Please upload a government ID picture.',
            'governmentIdPic.image' => 'The file must be an image.',
            'governmentIdPic.mimes' => 'Only JPG, PNG, JPEG, and WebP images are allowed.',
            'governmentIdPic.max' => 'The image may not be greater than 2MB.',
        ]);

        try {
            // Kuhaa ang uploaded image gikan sa request.
            $image = $request->file('governmentIdPic');

            // Python FastAPI endpoint nga modawat ug mo-analyze sa government ID.
            $pythonApiUrl = env(
                'ID_AI_API_URL',
                'https://dormdashai-production.up.railway.app/analyze'
            );

            // Ipadala ang image ngadto sa Python AI API gamit ang HTTP request.
            $response = Http::timeout(90)
                ->attach(
                    'file',
                    file_get_contents($image->getRealPath()),
                    $image->getClientOriginalName()
                )
                ->post($pythonApiUrl);

            // Susiha kung unsuccessful ang tubag gikan sa Python API.
            if (!$response->successful()) {

                return response()->json([
                    'status' => 'error',
                    'message' => 'Unable to analyze the ID image. Please try again.',
                ], 502);
            }

            // Kuhaa ang JSON response nga gibalik sa AI service.
            $aiResult = $response->json();

            // Siguroha nga valid array ang response gikan sa AI.
            if (!is_array($aiResult)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The AI service returned an invalid response.',
                ], 502);
            }

            // I-reject ang image kung dili kini Philippine government ID.
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

            // Susiha kung successful ba ang AI analysis.
            if (($aiResult['success'] ?? false) !== true) {
                return response()->json([
                    'status' => 'error',
                    'message' => $aiResult['message'] ?? 'AI analysis failed.',
                ], 502);
            }

            // Dawaton ang detected Philippine ID apan ipaagi gihapon sa manual review.
            return response()->json([
                'status' => 'success',
                'message' => $aiResult['message']
                    ?? 'ID image analysis completed.',
                'analysis' => $aiResult['analysis'] ?? null,
                'verification_status' => 'pending',
                'recommended_action' => 'manual_review',
            ], 200);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {

            // Ibalik ang service unavailable kung dili makakonekta sa AI API.
            return response()->json([
                'status' => 'error',
                'message' => 'The AI service is unavailable. Please try again later.',
            ], 503);

        } catch (\Exception $e) {

            // Ibalik ang general server error kung adunay problema sa proseso.
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred. Please try again.',
            ], 500);
        }
    }

    // I-validate ang business permit ug ipadala ang OTP ngadto sa landlord's email.
    public function businessPermitValidation(Request $request)
    {
        try {
            // Susiha ang business permit image ug email address.
            $validated = $request->validate([
                'businessPermitPic' => 'required|image|mimes:jpg,png,jpeg|max:2048',
                'email' => 'required|email|unique:landlords,email',
            ], [
                'businessPermitPic.required' => 'Please upload a business permit image.',
                'businessPermitPic.image' => 'The file must be an image.',
                'businessPermitPic.mimes' => 'Only JPG, PNG, and JPEG images are allowed.',
                'businessPermitPic.max' => 'The image may not be greater than 2MB.',
            ]);

            // Mag-generate ug random nga 6-digit OTP.
            $otpCode = rand(100000, 999999);
            // $otpCode = '123456';

            // Ibutang ang expiration sa OTP ngadto sa usa ka minuto.
            $expiresAt = now()->addMinutes(1);

            // Ipadala ang OTP ngadto sa email gamit ang tenantEmailOtp mail class.
            Mail::to($validated['email'])->send(new tenantEmailOtp($otpCode));

            // I-save ang OTP, email, expiration ug role sa database.
            otpModel::create([
                'email' => $validated['email'],
                'otpCode' => $otpCode,
                'otpExpiresAt' => $expiresAt,
                'role' => 'landlord',
            ]);

            // Kwentaha ang nahibiling oras sa OTP expiration.
            $delay = $expiresAt->diffInSeconds(now());

            // I-schedule ang job aron tangtangon ang expired OTP.
            DeleteExpiredOtpsJob::dispatch()->delay($delay);

            // Ibalik ang success response ug expiration timer ngadto sa frontend.
            return response()->json([
                'status' => 'success',
                'message' => 'OTP has been sent to your Gmail.',
                'timer' => $expiresAt,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Ibalik ang validation errors kung dili valid ang input.
            return response()->json([
                'status' => 'error',
                'message' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            // Ibalik ang server error kung mapakyas ang OTP sending o saving.
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // I-register ang landlord human makompleto ang personal ug verification requirements.
    public function RegisterLandlord(Request $request)
    {
        try
        {
            // I-validate ang tanang impormasyon ug documents nga gikinahanglan sa registration.
            $validated = $request->validate([
                'firstname' => 'required|string',
                'lastname' => 'required|string',
                'email' => 'required|email|unique:landlords,email',
                'phonenumber' => 'required|string|unique:landlords,phonenumber|regex:/^\+?[0-9]{10,15}$/|size:11',
                'password' => 'required|string|min:8|confirmed',
                'gender' => 'required|string',
                'profilePic' => 'required|image|mimes:jpg,png,jpeg|max:2048',
                'businessPermitPic' => 'required|image|mimes:jpg,png,jpeg|max:2048',
                'governmentIdPic' => 'required|image|mimes:jpg,png,jpeg|max:2048',
                'codeotp' => 'required|digits:6',
            ]);

            // I-initialize ang mga variable nga gamiton sa pag-store sa image paths.
            $profilePicPath = null;
            $businessPermitPath = null;
            $governmentIdPath = null;

            // I-save ang landlord profile picture sa public storage.
            if ($request->hasFile('profilePic')) {
                $image = $request->file('profilePic');
                $imageName = time() . '_profile.' . $image->getClientOriginalExtension();
                $imagePath = $image->storeAs('public/uploads/profilePic', $imageName);
                $profilePicPath = 'storage/uploads/profilePic/' . $imageName;
            }

            // I-save ang business permit image sa public storage.
            if ($request->hasFile('businessPermitPic')) {
                $image = $request->file('businessPermitPic');
                $imageName = time() . '_permit.' . $image->getClientOriginalExtension();
                $imagePath = $image->storeAs('public/uploads/businessPermitPic', $imageName);
                $businessPermitPath = 'storage/uploads/businessPermitPic/' . $imageName;
            }

            // I-save ang government ID image sa public storage.
            if ($request->hasFile('governmentIdPic')) {
                $image = $request->file('governmentIdPic');
                $imageName = time() . '_govid.' . $image->getClientOriginalExtension();
                $imagePath = $image->storeAs('public/uploads/governmentIdPic', $imageName);
                $governmentIdPath = 'storage/uploads/governmentIdPic/' . $imageName;
            }

            // Mag-generate ug unique UUID para mahimong landlord ID.
            $landlordID = Str::uuid();

            // Pangitaa ang OTP nga motakdo sa email, code ug wala pa ma-expire.
            $verifyOtp = otpModel::where('email', $validated['email'])
                ->where('otpCode', $validated['codeotp'])
                ->where('otpExpiresAt', '>', now())
                ->first();

            // Susiha kung valid ba ang OTP sa wala pa i-save ang landlord account.
            if (!$verifyOtp)
            {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid OTP Submission. Please try again.',
                    'data' => null
                ], 400);
            }

            // I-save ang landlord information ug uploaded document paths sa database.
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

            // Tangtangon ang gigamit nga OTP human malampuson ang registration.
            $verifyOtp->where('email', $validated['email'])->forceDelete();

            // Ibalik ang success response kung nahimo na ang landlord account.
            return response()->json([
                'status' => 'success',
                'message' => 'Welcome aboard! DormDash is happy to have you.',
            ], 200);

        }
        catch(\Illuminate\Validation\ValidationException $e)
        {
            // Ibalik ang validation errors kung adunay kulang o invalid nga input.
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            // Ibalik ang general error kung mapakyas ang registration process.
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Magpadala ug bag-ong OTP ngadto sa email sa landlord.
    public function resendOtp(Request $request)
    {
        // I-validate ang email address nga gipasa gikan sa frontend.
        $validated = $request->validate([
            'email' => 'required|email',
        ],
        [
            'email.exists' => 'No OTP record found for this email.',
        ]);

        // Pangitaa ang OTP record base sa email address.
        $otpRecord = otpModel::where('email', $validated['email']);

        // Susiha kung adunay OTP record nga makit-an.
        if (!$otpRecord)
        {
            return response()->json([
                'message' => 'No OTP record found for this email',
            ]);
        }

        // Mag-generate ug bag-ong 6-digit OTP.
        $newOtp = rand(100000, 999999);

        // Ibutang ang expiration sa bag-ong OTP ngadto sa usa ka minuto.
        $newOtpExpiresAt = now()->addMinutes(1);

        // I-update ang OTP code ug expiration sa database.
        $otpRecord->update([
            'otpCode' => $newOtp,
            'otpExpiresAt' => $newOtpExpiresAt,
        ]);

        // Ipadala ang bag-ong OTP ngadto sa email sa landlord.
        Mail::to($validated['email'])->send(new tenantEmailOtp($newOtp));

        // Ibalik ang success response ug bag-ong expiration timer.
        return response()->json([
            'status' => 'success',
            'message' => 'A new OTP has been sent to your email.',
            'timer' => $newOtpExpiresAt
        ], 200);
    }

    // Iproseso ang landlord login gamit ang email ug password.
    public function loginLandlord(Request $request)
    {
        try {
            // I-validate ang login credentials nga gisubmit sa user.
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            // Pangitaa ang landlord account gamit ang email address.
            $user = landlordModel::where('email', $request->email)->first();

            // Susiha kung adunay account ug motakdo ba ang password sa hashed password.
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json(['message' => 'Invalid credentials.'], 401);
            }

            // Dili tugotan ang login kung deactivated ang landlord account.
            if ($user->is_deactivated == 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your account has been deactivated. Please contact support.'
                ], 403);
            }

            // I-authenticate ang landlord gamit ang landlord authentication guard.
            Auth::guard('landlord')->login($user);

            // Ibutang ang landlord information sa session aron magamit sa ubang pages.
            session([
                'landlord_logged_in' => true,
                'landlord_id' => $user->landlordID,
                'landlord_firstname' => $user->firstname,
                'landlord_lastname' => $user->lastname,
                'landlord_avatar' => $user->profilePicUrl
            ]);

            // Ibalik ang login success response ug basic landlord information.
            return response()->json([
                'message' => 'Login successful',
                'status' => 'success',
                'landlord' => [
                    'id' => $user->landlordID,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                ],
            ]);
        }
        catch(\Illuminate\Validation\ValidationException $e)
        {
            // Ibalik ang validation errors kung dili sakto ang login input.
            return response()->json([
                'status' => 'error',
                'errors' => $e->errors(),
            ], 422);
        }
        catch (\Exception $e) {
            // Ibalik ang server error kung adunay problema sa login process.
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // I-logout ang landlord ug tangtangon ang iyang active session.
    public function logoutlandlord(Request $request)
    {
        // I-logout ang authenticated user.
        Auth::logout();

        // I-invalidate ang current session aron dili na magamit pag-usab.
        $request->session()->invalidate();

        // Mag-generate ug bag-ong CSRF token para sa sunod nga session.
        $request->session()->regenerateToken();

        // I-redirect ang user balik sa landlord login page.
        return redirect('/landlordLogin');
    }
}
