<?php

namespace App\Mail;

use App\Models\AutorizacaoServico;
use App\Models\User;
use App\Services\AutorizacaoServicoPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AutorizacaoServicoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AutorizacaoServico $autorizacaoServico,
        public string $pdfBinary,
        public string $nomeArquivo,
        public ?User $remetente = null,
        public array $anexos = [],
    ) {
        $this->autorizacaoServico->loadMissing(['obra.projeto.responsavelEng', 'construtora', 'asEscopo', 'createdBy']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Autorização de Serviço '.$this->autorizacaoServico->numero_as,
        );
    }

    public function content(): Content
    {
        $gestor = app(AutorizacaoServicoPdfService::class)->gestorDaObra($this->autorizacaoServico);

        return new Content(
            view: 'emails.autorizacao-servico',
            with: [
                'autorizacaoServico' => $this->autorizacaoServico,
                'gestor' => $gestor,
                'emailGestor' => $gestor?->email,
                'remetente' => $this->remetente,
            ],
        );
    }

    public function attachments(): array
    {
        $attachments = [
            Attachment::fromData(
                fn (): string => $this->pdfBinary,
                $this->nomeArquivo,
            )->withMime('application/pdf'),
        ];

        foreach ($this->anexos as $anexo) {
            if (! is_array($anexo) || blank($anexo['conteudo'] ?? null) || blank($anexo['nome'] ?? null)) {
                continue;
            }

            $attachments[] = Attachment::fromData(
                fn () => (string) $anexo['conteudo'],
                (string) $anexo['nome'],
            )->withMime((string) ($anexo['mime'] ?? 'application/octet-stream'));
        }

        return $attachments;
    }
}
