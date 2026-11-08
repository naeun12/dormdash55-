<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use SendGrid\Mail\Mail;

class TenantLandlordReminder extends Mailable
{
    // Gamita ang Queueable ug SerializesModels para sa email processing.
    use Queueable, SerializesModels;

    // Variables nga mag-store sa pangalan, mensahe, ug klase sa recipient.
    public $recipientName;
    public $messageBody;
    public $type; // 'tenant' or 'landlord'

    // I-initialize ang recipient details ug ang type sa reminder.
    public function __construct($recipientName, $messageBody, $type = 'tenant')
    {
        $this->recipientName = $recipientName;
        $this->messageBody = $messageBody;
        $this->type = $type;
    }

    // I-set ang email subject depende kung tenant ba o landlord ang recipient.
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->type === 'tenant' ? 'Tenant Reminder' : 'Landlord Reminder',
        );
    }

    // I-specify ang Blade view nga gamiton isip sulod sa reminder email.
    public function content(): Content
    {
        return new Content(
            view: 'mail.reminder'
        );
    }

    // I-return ang mga attachment nga i-apil sa email.
    public function attachments(): array
    {
        return [];
    }
}

