<?php

namespace App\Domain\Mail\Mail;

use App\Models\Registration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TemplateBasedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $renderedSubject,
        public string $renderedHtmlBody,
        public string $renderedTextBody,
        public Registration $registration,
        public array $variables = []
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->renderedSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.template-based',
            text: 'emails.template-based-text',
            with: [
                'renderedHtmlBody' => $this->renderedHtmlBody,
                'renderedTextBody' => $this->renderedTextBody,
                'registration' => $this->registration,
                'variables' => $this->variables,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
