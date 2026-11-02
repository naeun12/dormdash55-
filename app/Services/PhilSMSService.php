<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;
class PhilSMSService
{
     public function sendSMS(string $phone, string $message): array
    {
        $response = Http::withToken(
            config('services.philsms.token')
        )
        ->acceptJson()
        ->post(
            config('services.philsms.url') . '/sms/send',
            [
                'recipient' => $phone,
                'sender_id' => config('services.philsms.sender_id'),
                'type' => 'plain',
                'message' => $message,
            ]
        );

        if (!$response->successful()) {
            throw new \Exception(
                'PhilSMS Error: ' . $response->body()
            );
        }

        return $response->json();
    }
}
