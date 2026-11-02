<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\admin\adminModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;


class adminaccountController extends Controller
{


public function createAdmin(Request $request)
{
   

    $admin = AdminModel::create([
        'name' => 'Cassandra',
        'username' => 'admin',
        'password' => Hash::make('admin123'),
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Admin created successfully',
        'admin' => $admin
    ]);
}

     public function adminIndex()
    {
        return view('admin.adminlogin', ['title' => 'Admin - login']);
    }
  public function adminLogin(Request $request)
{
  
    try{
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);
    
        $user = adminModel::where('username', $request->username)->first();
    
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }
    Auth::guard('admin')->login($user);

    session([
        'admin_logged_in' => true,
        'admin_id' => $user->admin_id,
        'admin_name' => $user->name,
    ]);
    
        return response()->json([
            'message' => 'Login successful',
            'status' => 'success',
            'admin' => [
                'id' => $user->admin_id,
                'name' =>$user->name,
            ],
    
        ]);
    }
    catch(\Illuminate\Validation\ValidationException $e)
    {
        return response()->json([
            'status' => 'error',
            'message' => $e->errors(),
        ], 422);

    } catch (\Exception $e) {
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

    return redirect()->route('admin.login'); // mas maayo route name gamiton kaysa hardcoded path
}

}
