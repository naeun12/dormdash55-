<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use SendGrid\Mail\Mail;

class tenantEmailOtp extends Mailable
{
    // Gamita ang Queueable ug SerializesModels para sa email processing.
    use Queueable, SerializesModels;

    // Variable nga mag-store sa OTP code nga ipadala sa email.
    public $otp;

    // I-initialize ang OTP nga ipasa gikan sa controller.
    public function __construct($otp)
    {
        $this->otp = $otp;
    }

    // I-set ang subject o title sa OTP email.
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Email Otp',
        );
    }

    // I-specify ang Blade view nga gamiton isip sulod sa OTP email.
    public function content(): Content
    {
        return new Content(
            view: 'mail.welcome',
        );
    }

    // I-return ang mga attachment nga i-apil sa email.
    public function attachments(): array
    {
        return [

        ];
    }
}

