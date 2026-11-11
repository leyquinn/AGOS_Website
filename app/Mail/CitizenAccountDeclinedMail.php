<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CitizenAccountDeclinedMail extends Mailable
{
    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Update on your AGOS citizen registration');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.citizen-account-declined');
    }
}
