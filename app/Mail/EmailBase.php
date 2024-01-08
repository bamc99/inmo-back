<?php

namespace App\Mail;

use App\Mail\EmailParams;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailBase extends Mailable
{
    use Queueable, SerializesModels;

    protected $htmlFile;
    protected $data;
    protected $fromEmail;
    protected $fromName;
    protected $subjectText;

    /**
     * Create a new message instance.
     */
    public function __construct(EmailParams $params)
    {
        $this->htmlFile = $params->htmlFile;
        $this->data = $params->data;
        $this->fromEmail = $params->fromEmail;
        $this->fromName = $params->fromName;
        $this->subjectText = $params->subjectText;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Mail Base',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: $this->htmlFile,
            with: $this->data,
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

}
