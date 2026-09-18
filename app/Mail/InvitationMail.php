<?php

namespace App\Mail;

use App\Models\UserInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly UserInvitation $invitation) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Undangan bergabung ke Yado');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.invitation');
    }
}
