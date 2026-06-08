<?php

namespace App\Services;

use App\Enums\AsStatus;
use App\Enums\StatusControleNotaFiscalNota;
use App\Exports\ElaboracaoAditivoPlanilhaExport;
use App\Filament\Pages\ConstrutoraControlesNotaFiscalPage;
use App\Mail\EnviarPdfMail;
use App\Models\Asa;
use App\Models\ControleNotaFiscalAuxiliar;
use App\Models\ElaboracaoAditivo;
use App\Models\User;
use DomainException;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel;

class AsaFluxoService
{
    public function __construct(
        protected AsaPdfService $pdfService,
        protected AutorizacaoServicoFluxoService $asFluxoService,
    ) {}

    /**
     * @param  array<string, mixed>  $datas
     * @param  array<int, array<string, mixed>>  $parcelamento
     */
    public function gerarPdf(Asa $asa, User $user, array $datas, array $parcelamento): Asa
    {
        $this->validarParaGeracao($asa);

        return DB::transaction(function () use ($asa, $user, $datas, $parcelamento): Asa {
            $desconto = isset($datas['as_desconto'])
                ? max(round((float) $datas['as_desconto'], 2), 0.0)
                : (float) ($asa->as_desconto ?? 0);

            $asa->forceFill(array_filter([
                'as_data_inicio' => $datas['as_data_inicio'] ?? null,
                'as_data_termino' => $datas['as_data_termino'] ?? null,
                'as_data_entrega' => $datas['as_data_entrega'] ?? null,
                'as_desconto' => $desconto,
                'as_descricao_pdf' => filled($datas['as_descricao_pdf'] ?? null)
                    ? trim((string) $datas['as_descricao_pdf'])
                    : null,
                'as_itens_descricao_pdf' => $this->normalizarItensDescricao((array) ($datas['as_itens_descricao_pdf'] ?? [])),
                'as_anexos' => array_values(array_filter((array) ($datas['as_anexos'] ?? []))),
            ], fn ($v) => $v !== null || array_key_exists(array_search($v, $datas, true) ?: '', $datas)))->saveQuietly();

            if ($parcelamento !== []) {
                $valorLiquido = max(round((float) $asa->valor_total - $desconto, 2), 0.0);
                $asa->forceFill([
                    'as_parcelamento' => $this->normalizarParcelamento($parcelamento, $valorLiquido),
                ])->saveQuietly();
            }

            $this->pdfService->generateAndStorePdf($asa);

            $asa->forceFill([
                'status' => AsStatus::CRIADA,
                'as_criada_por_id' => $user->id,
                'as_criada_em' => now(),
            ])->saveQuietly();

            $aditivo = $asa->elaboracaoAditivo;
            if ($aditivo && $aditivo->status_fluxo !== 'aprovado') {
                $aditivo->update([
                    'status_fluxo' => 'aprovado',
                    'aprovado_orcamento_por_id' => $user->id,
                    'aprovado_orcamento_em' => now(),
                    'justificativa_reprovacao_orcamento' => null,
                ]);
            }

            Log::info('PDF da ASA gerado.', [
                'asa_id' => $asa->id,
                'numero_asa' => $asa->numero_asa,
                'criado_por' => $user->id,
            ]);

            return $asa->refresh();
        });
    }

    /**
     * @param  array<int, string>  $destinatarios
     * @param  array<int, string>  $copias
     * @param  array<int, string>  $copiasOcultas
     */
    public function enviar(
        Asa $asa,
        User $user,
        array $destinatarios,
        array $copias,
        array $copiasOcultas,
        string $modoExcel,
    ): Asa {
        $this->validarParaEnvio($asa, $destinatarios, $copias, $copiasOcultas);

        $auxiliar = $asa->controle_nota_fiscal_auxiliar_id
            ? ControleNotaFiscalAuxiliar::query()
                ->with('controleNotaFiscal.obra')
                ->find($asa->controle_nota_fiscal_auxiliar_id)
            : null;

        if ($auxiliar instanceof ControleNotaFiscalAuxiliar && $auxiliar->liberado_para_fornecedor_at !== null) {
            throw new DomainException('A ASA já foi enviada ao fornecedor.');
        }

        $pdfPath = $this->garantirPdf($asa);
        $disk = Storage::disk($this->pdfService->diskName());
        $pdfBinary = (string) $disk->get($pdfPath);

        $destinatarios = $this->asFluxoService->normalizarEmails($destinatarios);
        $copias = $this->asFluxoService->normalizarEmails($copias);
        $copiasOcultas = $this->asFluxoService->normalizarEmails($copiasOcultas);

        $unidade = $auxiliar?->controleNotaFiscal?->obra?->unidade ?? '-';
        $numeroAsa = (string) $asa->numero_asa;
        $escopo = $auxiliar ? (string) ($auxiliar->escopo ?? '') : (string) ($asa->descricao ?? '');
        $nomeRemetente = $user->name;
        $emailRemetente = $user->email;

        $anexosExcel = $modoExcel !== 'sem_excel'
            ? $this->anexosExcelAditivo($asa, $modoExcel)
            : [];

        $mensagem = '<p>Prezados,</p>'
            .'<p>A Autorização de Serviço <strong>'.e($numeroAsa ?: '-').'</strong> foi liberada.</p>'
            .'<p><strong>Unidade:</strong> '.e($unidade).'<br>'
            .'<strong>Fornecedor:</strong> '.e((string) ($asa->solicitante ?? '')).'<br>'
            .'<strong>Escopo:</strong> '.e($escopo ?: '-').'</p>'
            .'<p>Fica autorizada a emissão da Nota Fiscal.</p>'
            .'<p>Este e-mail foi enviado por '.e($nomeRemetente ?? 'Gestão Smart').'.</p>';

        Log::info('Enviando AS (adicional) por e-mail.', [
            'asa_id' => $asa->id,
            'numero_asa' => $numeroAsa,
            'destinatarios' => $destinatarios,
        ]);

        Mail::to($destinatarios)->cc($copias)->bcc($copiasOcultas)->send(new EnviarPdfMail(
            assunto: 'AS liberada '.$numeroAsa,
            mensagemEmail: $mensagem,
            pdfBinary: $pdfBinary,
            nomeArquivo: $this->pdfService->nomeArquivo($asa),
            nomeRemetente: $nomeRemetente,
            emailRemetente: $emailRemetente,
            anexos: $anexosExcel,
        ));

        return DB::transaction(function () use ($asa, $user, $auxiliar, $destinatarios): Asa {
            $liberadoEm = now();

            if ($auxiliar instanceof ControleNotaFiscalAuxiliar) {
                $auxiliar->forceFill(['liberado_para_fornecedor_at' => $liberadoEm])->save();

                $this->notificarFornecedor($auxiliar, $asa, $destinatarios);
            }

            $asa->forceFill([
                'status' => AsStatus::ENVIADA,
                'as_enviada_por_id' => $user->id,
                'as_enviada_em' => $liberadoEm,
            ])->saveQuietly();

            return $asa->refresh();
        });
    }

    public function cancelar(Asa $asa, string $motivo, User $user): Asa
    {
        return DB::transaction(function () use ($asa, $motivo, $user): Asa {
            if ($asa->status === AsStatus::CANCELADA) {
                return $asa->refresh();
            }

            $temNotaAprovada = $asa->notasFiscais()
                ->where('controle_nota_fiscal_notas.status', StatusControleNotaFiscalNota::APROVADO->value)
                ->exists();

            if ($temNotaAprovada && ! $this->asFluxoService->podeCancelarComNotaAprovada($user)) {
                throw new DomainException('Não é possível cancelar uma ASA com nota fiscal aprovada.');
            }

            $asa->forceFill([
                'status' => AsStatus::CANCELADA,
                'as_cancelada_em' => now(),
                'as_cancelada_por_id' => $user->id,
                'as_motivo_cancelamento' => $motivo,
            ])->save();

            $aditivo = $asa->elaboracaoAditivo;
            if ($aditivo && $aditivo->status_fluxo !== 'cancelado') {
                $aditivo->update(['status_fluxo' => 'cancelado']);
            }

            Log::info('ASA cancelada.', [
                'asa_id' => $asa->id,
                'numero_asa' => $asa->numero_asa,
                'cancelada_por' => $user->id,
                'motivo' => $motivo,
            ]);

            return $asa->refresh();
        });
    }

    /**
     * @param  array<int, string>  $destinatarios
     * @param  array<int, string>  $copias
     * @param  array<int, string>  $copiasOcultas
     */
    protected function validarParaEnvio(Asa $asa, array $destinatarios, array $copias, array $copiasOcultas): void
    {
        if ($asa->status === AsStatus::ENVIADA) {
            throw new DomainException('A ASA já foi enviada.');
        }

        if (! in_array($asa->status, [AsStatus::CRIADA, AsStatus::APROVADO], true)) {
            throw new DomainException('A AS só pode ser enviada após o PDF ser gerado.');
        }

        $emails = [...$destinatarios, ...$copias, ...$copiasOcultas];
        if ($emails === []) {
            throw new DomainException('Informe ao menos um e-mail válido para enviar a AS.');
        }
    }

    protected function validarParaGeracao(Asa $asa): void
    {
        if ($asa->status === AsStatus::ENVIADA) {
            throw new DomainException('Não é possível gerar PDF de uma AS já enviada.');
        }

        if (blank($asa->numero_asa)) {
            throw new DomainException('Informe o número da ASA antes de gerar o PDF.');
        }

        if ((float) $asa->valor_total <= 0) {
            throw new DomainException('Informe o valor total antes de gerar a AS.');
        }
    }

    protected function garantirPdf(Asa $asa): string
    {
        $path = (string) ($asa->as_pdf ?? '');

        if ($path !== '' && Storage::disk($this->pdfService->diskName())->exists($path)) {
            return $path;
        }

        return $this->pdfService->generateAndStorePdf($asa);
    }

    /**
     * @param  array<int, string>  $emailsParaNotificacao
     */
    protected function notificarFornecedor(ControleNotaFiscalAuxiliar $auxiliar, Asa $asa, array $emailsParaNotificacao): void
    {
        $emails = $this->asFluxoService->normalizarEmails($emailsParaNotificacao);

        if ($emails === []) {
            return;
        }

        $destinatarios = User::query()
            ->where('is_active', true)
            ->whereIn('email', $emails)
            ->get();

        if ($destinatarios->isEmpty()) {
            return;
        }

        $unidade = $auxiliar->controleNotaFiscal?->obra?->unidade ?? '-';
        $numeroAsa = (string) ($auxiliar->numero_as ?? $asa->numero_asa ?? '');
        $escopo = (string) ($auxiliar->escopo ?? '');

        Notification::make()
            ->title('Item liberado para fornecedor')
            ->body(
                'Foi liberado o item '.($numeroAsa !== '' ? $numeroAsa.' - ' : '').$escopo.
                ' da unidade '.$unidade.'. Fica autorizado a emissão da Nota Fiscal.'
            )
            ->icon('heroicon-o-check-circle')
            ->iconColor('success')
            ->actions([
                Action::make('abrir')
                    ->label('Importar nota fiscal')
                    ->url(ConstrutoraControlesNotaFiscalPage::getUrl()),
            ])
            ->sendToDatabase($destinatarios);
    }

    /**
     * @return array<int, array{conteudo: string, nome: string, mime: string}>
     */
    protected function anexosExcelAditivo(Asa $asa, string $modo): array
    {
        $aditivo = $asa->elaboracaoAditivo;

        if (! $aditivo) {
            return [];
        }

        $paths = array_values(array_filter((array) ($aditivo->anexos ?? [])));

        if ($paths === [] && $modo === 'gerar') {
            return $this->gerarPlanilhaAditivo($aditivo);
        }

        $disk = Storage::disk($this->pdfService->diskName());

        return collect($paths)
            ->filter(fn (mixed $path): bool => is_string($path) && $path !== '' && $disk->exists($path))
            ->map(fn (string $path): array => [
                'conteudo' => (string) $disk->get($path),
                'nome' => basename($path),
                'mime' => $disk->mimeType($path) ?: 'application/octet-stream',
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{conteudo: string, nome: string, mime: string}>
     */
    protected function gerarPlanilhaAditivo(ElaboracaoAditivo $aditivo): array
    {
        try {
            $export = new ElaboracaoAditivoPlanilhaExport($aditivo->id);
            $conteudo = \Maatwebsite\Excel\Facades\Excel::raw($export, Excel::XLSX);

            return [[
                'conteudo' => $conteudo,
                'nome' => 'planilha-asa-'.($aditivo->id).'.xlsx',
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $itens
     * @return array<int, array{descricao_tipo: string, descricao: ?string, descricao_arquivo: array<int, string>}>
     */
    public function normalizarItensDescricao(array $itens): array
    {
        return collect($itens)
            ->filter(fn (mixed $item): bool => is_array($item))
            ->take(1)
            ->map(function (array $item): array {
                $arquivos = array_values(array_filter((array) ($item['descricao_arquivo'] ?? [])));
                $descricao = filled($item['descricao'] ?? null) ? trim((string) $item['descricao']) : null;
                $tipo = in_array((string) ($item['descricao_tipo'] ?? ''), ['texto', 'arquivo'], true)
                    ? (string) $item['descricao_tipo']
                    : ($arquivos === [] ? 'texto' : 'arquivo');

                return [
                    'descricao_tipo' => $tipo,
                    'descricao' => $descricao,
                    'descricao_arquivo' => $arquivos,
                ];
            })
            ->filter(fn (array $item): bool => filled($item['descricao']) || $item['descricao_arquivo'] !== [])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $parcelamento
     * @return array<int, array{parcela: string, percentual: float, valor: float, observacao: string}>
     */
    public function normalizarParcelamento(array $parcelamento, float $total): array
    {
        $parcelas = collect($parcelamento)
            ->filter(fn (mixed $p): bool => is_array($p))
            ->map(fn (array $p, int $i): array => [
                'parcela' => filled($p['parcela'] ?? null)
                    ? (string) $p['parcela']
                    : 'Parcela '.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'percentual' => round((float) ($p['percentual'] ?? 0), 2),
                'valor' => round((float) ($p['valor'] ?? 0), 2),
                'observacao' => (string) ($p['observacao'] ?? ''),
            ])
            ->filter(fn (array $p): bool => $p['valor'] > 0 || $p['percentual'] > 0)
            ->values();

        if ($parcelas->isEmpty()) {
            if (round($total, 2) === 0.0) {
                return [['parcela' => 'Parcela 01', 'percentual' => 0.0, 'valor' => 0.0, 'observacao' => '']];
            }

            throw new DomainException('Informe ao menos uma parcela para gerar o PDF.');
        }

        $somaPercentual = round((float) $parcelas->sum('percentual'), 2);
        if (abs($somaPercentual - 100.0) > 0.01) {
            throw new DomainException('A soma dos percentuais do parcelamento deve ser 100%.');
        }

        $soma = round((float) $parcelas->sum('valor'), 2);
        if (abs($soma - round($total, 2)) > 0.01) {
            throw new DomainException('A soma das parcelas deve ser igual ao valor líquido da AS.');
        }

        return $parcelas->all();
    }
}
