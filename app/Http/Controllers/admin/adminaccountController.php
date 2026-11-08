<?php
namespace App\Http\Controllers\admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\admin\adminModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class adminaccountController extends Controller
{
    // Maghimo og default admin account para magamit sa pag-login.
    public function createAdmin(Request $request)
    {
        // I-save ang admin details sa database ug i-encrypt ang password.
        $admin = AdminModel::create([
            'name' => 'Spongebob',
            'username' => 'admin123',
            'password' => Hash::make('admin123'),
        ]);

        // Ibalik ang success response ug admin details.
        return response()->json([
            'status' => 'success',
            'message' => 'Admin created successfully',
            'admin' => $admin
        ]);
    }

    // Ipakita ang admin login page.
    public function adminIndex()
    {
        return view('admin.adminlogin', [
            'title' => 'Admin - login'
        ]);
    }

    // Proseso sa pag-login sa admin account.
    public function adminLogin(Request $request)
    {
        try {
            // Siguraduhon nga naay username ug password nga gipasa.
            $request->validate([
                'username' => 'required',
                'password' => 'required',
            ]);

            // Pangitaon ang admin account gamit ang username.
            $user = adminModel::where('username', $request->username)->first();

            // I-check kung naa ang account ug sakto ba ang password.
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'Invalid credentials.'
                ], 401);
            }

            // I-login ang admin gamit ang admin authentication guard.
            Auth::guard('admin')->login($user);

            // Ibutang ang admin information sa session para magamit sa ubang pages.
            session([
                'admin_logged_in' => true,
                'admin_id' => $user->admin_id,
                'admin_name' => $user->name,
            ]);

            // Ibalik ang successful login response ug admin information.
            return response()->json([
                'message' => 'Login successful',
                'status' => 'success',
                'admin' => [
                    'id' => $user->admin_id,
                    'name' => $user->name,
                ],
            ]);
        }

        // Ibalik ang validation errors kung naay kulang nga input.
        catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->errors(),
            ], 422);

        // Ibalik ang error message kung naay problema sa proseso sa pag-login.
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // Proseso sa pag-logout sa admin account.
    public function logoutlandlord(Request $request)
    {
        // I-logout ang currently authenticated account.
        Auth::logout();

        // I-clear ang current session ug maghimo og bag-ong CSRF token.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // I-redirect ang admin balik sa login page gamit ang route name.
        return redirect()->route('admin.login');
    }
}
