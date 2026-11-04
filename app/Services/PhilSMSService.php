<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;
class PhilSMSService
{
    public function sendSMS(string $phone, string $message): array
    {
        // Normalize Philippine mobile number
        $phone = preg_replace('/\D/', '', $phone);

        if (str_starts_with($phone, '09')) {
            $phone = '63' . substr($phone, 1);
        }

        try {
            $response = Http::withToken(config('services.philsms.token'))
                ->acceptJson()
                ->post(config('services.philsms.url') . '/sms/send', [
                    'recipient' => $phone,
                    'sender_id' => config('services.philsms.sender_id'),
                    'type' => 'plain',
                    'message' => $message,
                ]);

            return [
                'success' => $response->successful(),
                'http_status' => $response->status(),
                'response' => $response->json(),
            ];

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

}
