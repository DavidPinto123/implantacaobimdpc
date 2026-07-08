<?php

namespace App\Mail;

use App\Models\Ata;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AtaEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Ata $ata) {}

    public function envelope(): Envelope
    {
        $projeto = $this->ata->projeto?->nome ?? 'Sem projeto';
        $data    = $this->ata->data_reuniao->format('d/m/Y');

        return new Envelope(
            subject: "Ata de Reunião – {$this->ata->titulo} | {$projeto} | {$data}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ata',
            with: ['ata' => $this->ata],
        );
    }
}
