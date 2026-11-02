<?php

namespace App\Http\Controllers;
use App\Services\PhilSMSService;

use Illuminate\Http\Request;

class SMSController extends Controller
{
    public function send(Request $request, PhilSMSService $sms)
    {
        $validated = $request->validate([
            'phone' => ['required', 'regex:/^639\d{9}$/'],
            'message' => 'required|string|max:1000',
        ]);

        try {
            $result = $sms->sendSMS(
                $validated['phone'],
                $validated['message']
            );

            return response()->json([
                'success' => true,
                'message' => 'SMS request submitted.',
                'data' => $result,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send SMS.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
