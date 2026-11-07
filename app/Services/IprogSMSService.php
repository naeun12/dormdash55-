<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IprogSMSService
{
    public function sendSMS(string $phoneNumber, string $message): bool
    {
        try {
            $apiToken = config('services.iprog.token');
            $senderName = config('services.iprog.sender_name');
            $apiUrl = config('services.iprog.url');

            if (!$apiToken || !$apiUrl) {
                Log::error('iProG SMS configuration is missing.');
                return false;
            }

            // Convert Philippine mobile number to 09XXXXXXXXX format
            $phoneNumber = preg_replace('/\D/', '', $phoneNumber);

            if (str_starts_with($phoneNumber, '63')) {
                $phoneNumber = '0' . substr($phoneNumber, 2);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiToken,
                'Accept' => 'application/json',
            ])->post($apiUrl, [
                'api_token' => $apiToken,
                'message' => $message,
                'phone_number' => $phoneNumber,
                'sender_name' => $senderName,
            ]);

            if ($response->successful()) {
                Log::info('iProG SMS sent successfully.', [
                    'phone' => $phoneNumber,
                    'response' => $response->json(),
                ]);

                return true;
            }

            Log::error('iProG SMS failed.', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;

        } catch (\Throwable $e) {
            Log::error('iProG SMS exception: ' . $e->getMessage());
            return false;
        }
    }
}
