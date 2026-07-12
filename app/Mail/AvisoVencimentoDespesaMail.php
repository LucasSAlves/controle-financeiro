<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class AvisoVencimentoDespesaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Collection $despesas
    ) {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Aviso de vencimento de despesa'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.aviso-vencimento-despesa'
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
