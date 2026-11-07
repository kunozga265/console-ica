<?php

namespace App\Mail;

use App\Models\TitheReceipt;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** A member's tithe receipt, with the PDF attached (App\Support\Receipts::deliver). */
class TitheReceiptIssued extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public TitheReceipt $receipt, private string $pdf) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Your tithe receipt {$this->receipt->number}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tithe-receipt', with: [
            'firstName' => trim($this->receipt->member?->first_name) ?: 'friend',
            'amount'    => 'MWK '.number_format($this->receipt->amount, 2),
            'siteUrl'   => url('/'),
        ]);
    }

    public function attachments(): array
    {
        return [Attachment::fromData(fn () => $this->pdf, "{$this->receipt->number}.pdf")->withMime('application/pdf')];
    }
}
