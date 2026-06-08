<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EnviarPdfMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $assunto,
        public string $mensagemEmail,
        public string $pdfBinary,
        public string $nomeArquivo = 'documento.pdf',
        public ?string $fotoPerfilBinaria = null,
        public ?string $fotoPerfilNome = 'carimbo.png',
        public ?string $fotoPerfilMime = 'image/png',
        public array $anexosLinks = [],
        public array $anexos = [],
        public ?string $nomeRemetente = null,
        public ?string $emailRemetente = null,
        public ?string $cargoRemetente = null,
        public ?string $empresaRemetente = null,
        public ?string $telefoneRemetente = null,
        public ?string $enderecoRemetente = null,
        public ?string $linkRemetente = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->assunto,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.enviar-pdf',
            with: [
                'mensagemEmail' => $this->mensagemEmail,
                'fotoPerfilBinaria' => $this->fotoPerfilBinaria,
                'fotoPerfilNome' => $this->fotoPerfilNome,
                'fotoPerfilMime' => $this->fotoPerfilMime,
                'anexosLinks' => $this->anexosLinks,
                'nomeRemetente' => $this->nomeRemetente ?? null,
                'emailRemetente' => $this->emailRemetente ?? null,
                'cargoRemetente' => $this->cargoRemetente ?? null,
                'empresaRemetente' => $this->empresaRemetente ?? null,
                'telefoneRemetente' => $this->telefoneRemetente ?? null,
                'enderecoRemetente' => $this->enderecoRemetente ?? null,
                'linkRemetente' => $this->linkRemetente ?? null,
            ],
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        if (filled($this->pdfBinary) && filled($this->nomeArquivo)) {
            $attachments[] = Attachment::fromData(
                fn () => $this->pdfBinary,
                $this->nomeArquivo
            )->withMime('application/pdf');
        }

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
