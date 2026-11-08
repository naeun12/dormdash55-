<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\otpModel;
use Illuminate\Foundation\Bus\Dispatchable;

class DeleteExpiredOtpsJob implements ShouldQueue
{
    // Gamita ang Queueable trait aron mahimong queueable ang job.
    use Queueable;

    // Constructor nga gamiton sa paghimo og bag-ong job instance.
    public function __construct()
    {
        //
    }

    // I-execute ang job aron tangtangon ang mga expired OTP records.
    public function handle(): void
    {
        // Kuhaon ang current date ug time.
        $now = now();

        // Pangitaon ug permanenteng tangtangon ang mga OTP nga:
        // - Dili null ang expiration date.
        // - Expired na o parehas na sa current date ug time.
        $expiredCount = otpModel::whereNotNull('otpExpiresAt')
            ->where('otpExpiresAt', '<=', $now)
            ->forceDelete();
    }
}

