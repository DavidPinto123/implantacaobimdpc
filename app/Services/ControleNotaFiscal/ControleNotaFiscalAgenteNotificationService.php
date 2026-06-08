<?php

namespace App\Services\ControleNotaFiscal;

use App\Enums\StatusControleNotaFiscalNota;
use App\Filament\Pages\AprovacaoNotasFiscaisPage;
use App\Filament\Pages\ConstrutoraControlesNotaFiscalPage;
use App\Mail\EnviarPdfMail;
use App\Models\Construtora;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalNota;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class ControleNotaFiscalAgenteNotificationService
{
    public function notificarNotaImportada(ControleNotaFiscalNota $nota): void
    {
        $nota->loadMissing([
            'autorizacaoServico.construtora.users',
            'autorizacaoServico.controleNotaFiscalItem.controleNotaFiscal',
            'asa.elaboracaoAditivo.construtora.users',
            'asa.controleNotaFiscalAuxiliar.controleNotaFiscal',
            'importadoPor',
        ]);

        $controle = $this->controleDaNota($nota);
        $destinatarios = $this->destinatariosAprovacaoNotaImportada($nota);

        if ($destinatarios->isEmpty()) {
            return;
        }

        $url = AprovacaoNotasFiscaisPage::getUrl([
            'controle' => $controle?->id,
        ]);
        $numeroNota = (string) ($nota->numero_nf ?: $nota->id);
        $assunto = 'Nova nota fiscal para aprovação '.$numeroNota;
        $corpo = 'A nota fiscal '.$numeroNota.' foi importada e aguarda aprovação.';

        $this->notificarUsuarios(
            destinatarios: $destinatarios,
            titulo: 'Nova nota fiscal para aprovação',
            corpo: $corpo,
            url: $url,
            assuntoEmail: $assunto,
        );
    }

    public function notificarNotaDecidida(ControleNotaFiscalNota $nota): void
    {
        $nota->loadMissing(['importadoPor']);

        $destinatario = $nota->importadoPor;

        if (! $destinatario instanceof User) {
            return;
        }

        $numeroNota = (string) ($nota->numero_nf ?: $nota->id);
        $foiAprovada = $nota->status === StatusControleNotaFiscalNota::APROVADO->value;
        $titulo = $foiAprovada ? 'Nota fiscal aprovada' : 'Nota fiscal reprovada';
        $assunto = $titulo.' '.$numeroNota;
        $corpo = 'A nota fiscal '.$numeroNota.' foi '.($foiAprovada ? 'aprovada' : 'reprovada').'.';

        $this->notificarUsuarios(
            destinatarios: collect([$destinatario]),
            titulo: $titulo,
            corpo: $corpo,
            url: ConstrutoraControlesNotaFiscalPage::getUrl(),
            assuntoEmail: $assunto,
        );
    }

    /**
     * @return Collection<int, User>
     */
    protected function destinatariosAprovacaoNotaImportada(ControleNotaFiscalNota $nota): Collection
    {
        return $this->fornecedorDaNota($nota)?->users
            ->filter(fn (User $user): bool => $user->is_active)
            ->unique('id')
            ->values()
            ?? collect();
    }

    protected function controleDaNota(ControleNotaFiscalNota $nota): ?ControleNotaFiscal
    {
        return $nota->autorizacaoServico?->controleNotaFiscalItem?->controleNotaFiscal
            ?? $nota->asa?->controleNotaFiscalAuxiliar?->controleNotaFiscal;
    }

    protected function fornecedorDaNota(ControleNotaFiscalNota $nota): ?Construtora
    {
        if ($nota->autorizacaoServico) {
            return $nota->autorizacaoServico->construtora;
        }

        if ($nota->asa?->elaboracaoAditivo?->construtora) {
            return $nota->asa->elaboracaoAditivo->construtora;
        }

        $nomeFornecedor = trim((string) (
            $nota->asa?->solicitante
            ?: $nota->asa?->controleNotaFiscalAuxiliar?->empresa
        ));

        if ($nomeFornecedor === '') {
            return null;
        }

        return Construtora::query()
            ->with('users')
            ->where('nome', $nomeFornecedor)
            ->first();
    }

    /**
     * @param  Collection<int, User>  $destinatarios
     */
    protected function notificarUsuarios(Collection $destinatarios, string $titulo, string $corpo, string $url, string $assuntoEmail): void
    {
        Notification::make()
            ->title($titulo)
            ->body($corpo)
            ->icon('heroicon-o-arrow-right-circle')
            ->iconColor('primary')
            ->actions([
                Action::make('abrir')
                    ->label('Abrir')
                    ->url($url),
            ])
            ->sendToDatabase($destinatarios);

        Mail::to($destinatarios->pluck('email')->filter()->values()->all())
            ->send(new EnviarPdfMail(
                assunto: $assuntoEmail,
                mensagemEmail: '<p>'.e($corpo).'</p><p><a href="'.e($url).'">Abrir próxima ação</a></p>',
                pdfBinary: '',
                nomeArquivo: '',
            ));
    }
}
