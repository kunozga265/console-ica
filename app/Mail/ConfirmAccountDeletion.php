<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sends the one-time link that confirms an account deletion request. */
class ConfirmAccountDeletion extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $name, public string $url, public int $minutes) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Confirm deleting your ICA App account');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.confirm-account-deletion');
    }
}
