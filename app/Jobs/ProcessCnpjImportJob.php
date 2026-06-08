<?php

namespace App\Jobs;

use App\Models\ImportacaoLog;
use App\Models\ImportacaoStaging;
use App\Models\User;
use App\Services\CnpjSpreadsheetParserService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ProcessCnpjImportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [30, 60];

    public int $timeout = 600;

    public function __construct(
        public int $importacaoLogId,
        public int $userId,
    ) {}

    public function handle(CnpjSpreadsheetParserService $parser): void
    {
        $log = ImportacaoLog::findOrFail($this->importacaoLogId);
        $log->update(['status' => 'processando', 'iniciado_em' => now()]);

        $config = $log->mapeamento_usado;
        $rowOverrides = $config['row_overrides'] ?? [];

        $retryDados = $config['retry_dados'] ?? null;
        if ($retryDados) {
            $rows = $parser->prepareRetryRows($retryDados, $rowOverrides);
        } else {
            $columnMapping = $config['columns'] ?? $config;
            $valueMapping = $config['values'] ?? [];
            $headerRow = $config['headerRow'] ?? null;
            $sheet = $config['sheet'] ?? 0;
            $columnMap = $config['columnMap'] ?? [];

            $ext = pathinfo((string) $log->arquivo_path, PATHINFO_EXTENSION);
            $tempPath = tempnam(sys_get_temp_dir(), 'import_cnpj_').'.'.$ext;
            file_put_contents($tempPath, Storage::disk((string) config('filesystems.media_disk', 'r2'))->get((string) $log->arquivo_path));

            $rows = $parser->prepareRows(
                $tempPath,
                $sheet,
                $columnMapping,
                $headerRow,
                $valueMapping,
                $columnMap,
                $rowOverrides,
            );

            @unlink($tempPath);
        }

        $resolucoes = $config['resolucoes'] ?? [];
        $projetosIgnorados = $config['projetos_ignorados'] ?? [];
        $linhasIgnoradas = collect($config['linhas_ignoradas'] ?? [])
            ->map(fn (mixed $linha): int => (int) $linha)
            ->all();
        $rows = collect($parser->applyConflictResolutions($rows->all(), $resolucoes));

        $ignoradas = 0;
        $atualizadas = 0;
        $erros = [];

        $log->update(['total_linhas' => $rows->count()]);

        foreach ($rows as $index => $row) {
            try {
                $linhaPlanilha = (int) ($row['linha'] ?? ($index + 2));
                $codigo = $row['nova_sigla'] ?? $row['sigla_antiga'] ?? null;
                $conflictKey = filled($row['projeto_id'] ?? null)
                    ? 'projeto:'.$row['projeto_id']
                    : 'codigo:'.(filled($row['nova_sigla'] ?? null) ? $row['nova_sigla'] : ($row['sigla_antiga'] ?? 'sem-chave'));
                $stagingData = [
                    'importacao_log_id' => $log->id,
                    'linha_planilha' => $linhaPlanilha,
                    'codigo' => $codigo,
                    'dados' => $row,
                ];

                if (in_array($linhaPlanilha, $linhasIgnoradas, true)) {
                    $stagingData['acao'] = 'ignorar';
                    ImportacaoStaging::create($stagingData);
                    $ignoradas++;

                    continue;
                }

                if (in_array($conflictKey, $projetosIgnorados, true)) {
                    $stagingData['acao'] = 'ignorar';
                    ImportacaoStaging::create($stagingData);
                    $ignoradas++;

                    continue;
                }

                if (empty($row['projeto_id'])) {
                    $erro = [
                        'msg' => 'Projeto não encontrado para a linha preparada.',
                        'tipo' => $parser->shouldClassifyAsProjetoNaoCriado($row) ? 'projeto_nao_criado' : 'projeto_nao_encontrado',
                    ];

                    $stagingData['acao'] = 'erro';
                    $stagingData['erro'] = $erro;
                    ImportacaoStaging::create($stagingData);
                    $erros[] = [
                        'linha' => $row['linha'] ?? ($index + 2),
                        'codigo' => filled($row['nova_sigla'] ?? null) ? $row['nova_sigla'] : ($row['sigla_antiga'] ?? null),
                        'msg' => $erro['msg'],
                        'tipo' => $erro['tipo'],
                        'dados' => $row,
                    ];

                    continue;
                }

                $stagingData['acao'] = 'atualizar';
                ImportacaoStaging::create($stagingData);
                $atualizadas++;
            } catch (Throwable $exception) {
                $erro = [
                    'linha' => $row['linha'] ?? ($index + 2),
                    'codigo' => filled($row['nova_sigla'] ?? null) ? $row['nova_sigla'] : ($row['sigla_antiga'] ?? null),
                    'dados' => $row,
                    'msg' => Str::limit($exception->getMessage(), 200),
                    'tipo' => 'outro',
                ];

                $erros[] = $erro;

                ImportacaoStaging::create([
                    'importacao_log_id' => $log->id,
                    'linha_planilha' => $row['linha'] ?? ($index + 2),
                    'codigo' => filled($row['nova_sigla'] ?? null) ? $row['nova_sigla'] : ($row['sigla_antiga'] ?? null),
                    'acao' => 'erro',
                    'dados' => $row,
                    'erro' => $erro,
                ]);
            }

            if (($index + 1) % 10 === 0) {
                $log->update([
                    'linhas_criadas' => 0,
                    'linhas_atualizadas' => $atualizadas,
                    'linhas_erro' => count($erros),
                ]);
            }
        }

        $log->update([
            'status' => 'staged',
            'linhas_criadas' => 0,
            'linhas_atualizadas' => $atualizadas,
            'linhas_erro' => count($erros),
            'erros' => $erros ?: null,
            'finalizado_em' => now(),
        ]);

        $this->notifyStaged($log);
    }

    public function failed(Throwable $exception): void
    {
        $log = ImportacaoLog::find($this->importacaoLogId);
        $log?->update([
            'status' => 'erro',
            'finalizado_em' => now(),
            'erros' => [['linha' => 0, 'msg' => $exception->getMessage(), 'tipo' => 'fatal']],
        ]);

        $this->notifyFailure();
        report($exception);
    }

    private function notifyStaged(ImportacaoLog $log): void
    {
        $user = User::find($this->userId);

        if (! $user instanceof User) {
            return;
        }

        $body = "{$log->linhas_atualizadas} para atualizar";
        $ignorarCount = ImportacaoStaging::where('importacao_log_id', $log->id)
            ->where('acao', 'ignorar')
            ->count();

        if ($ignorarCount > 0) {
            $body .= ", {$ignorarCount} ignoradas";
        }

        if ($log->linhas_erro > 0) {
            $body .= ", {$log->linhas_erro} com erro";
        }

        Notification::make()
            ->title('Dados de CNPJ preparados para revisão')
            ->body($body)
            ->info()
            ->sendToDatabase($user);
    }

    private function notifyFailure(): void
    {
        $user = User::find($this->userId);

        if (! $user instanceof User) {
            return;
        }

        Notification::make()
            ->title('Falha na importação de CNPJs')
            ->body('Ocorreu um erro ao processar a planilha. Verifique o log de importação.')
            ->danger()
            ->sendToDatabase($user);
    }
}
