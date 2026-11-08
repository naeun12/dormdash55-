<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class userEmail extends Mailable
{
    // Gamita ang Queueable ug SerializesModels para sa email processing.
    use Queueable, SerializesModels;

    // I-declare ang variables nga gamiton sa email template.
    public $tenantName;
    public $messageBody;

    // I-initialize ang pangalan ug mensahe nga ipasa gikan sa controller.
    public function __construct($tenantName, $messageBody)
    {
        $this->tenantName = $tenantName;
        $this->messageBody = $messageBody;
    }

    // I-set ang subject o title sa email.
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'DormDash  Reminder'
        );
    }

    // I-specify ang Blade view nga gamiton isip sulod sa email.
    public function content(): Content
    {
        return new Content(
            view: 'admin.auth.email.tenantemail',
        );
    }

    // I-return ang mga attachment nga i-apil sa email.
    public function attachments(): array
    {
        return [];
    }
}

