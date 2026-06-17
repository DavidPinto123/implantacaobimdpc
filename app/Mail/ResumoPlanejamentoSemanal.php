<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ResumoPlanejamentoSemanal extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $gerente,
        public Collection $projetos,
        public array $semanaAnterior,
        public array $semanaAtual,
        public string $labelSemanaAnterior,
        public string $labelSemanaAtual,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Resumo Semanal de Planejamentos — {$this->labelSemanaAtual}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.resumo-planejamento-semanal',
        );
    }
}
