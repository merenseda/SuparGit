<?php

namespace App\Mail;

use App\Models\PushLog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PushNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PushLog $pushLog) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🚀 [GitSüpar] ' . $this->pushLog->user->name . ' → ' . $this->pushLog->repo_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.push-notification',
        );
    }
}
