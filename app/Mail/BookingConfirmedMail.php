<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Appointment $appointment)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your booking is confirmed — '.$this->appointment->tenant->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.booking-confirmed',
            text: 'mail.booking-confirmed-text',
        );
    }
}
