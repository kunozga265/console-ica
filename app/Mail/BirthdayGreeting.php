<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** The church's birthday wishes, sent by `birthdays:send` at 7am on the day. */
class BirthdayGreeting extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $firstName) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Happy birthday, {$this->firstName}! 🎉");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.birthday-greeting', with: ['siteUrl' => url('/')]);
    }
}
