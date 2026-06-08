<?php

namespace App\Jobs;

use App\Models\Cidade;
use App\Models\ImportacaoLog;
use App\Models\ImportacaoStaging;
use App\Models\Obras;
use App\Models\Projeto;
use App\Models\User;
use App\Services\SpreadsheetParserService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessObraImportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [30, 60];

    public int $timeout = 600;

    public function __construct(
        public int $importacaoLogId,
        public int $userId,
    ) {}

    public function handle(SpreadsheetParserService $parser): void
    {
        $log = ImportacaoLog::findOrFail($this->importacaoLogId);
        $log->update(['status' => 'processando', 'iniciado_em' => now()]);

        $config = $log->mapeamento_usado;
        $projetosCriar = $config['projetos_criar'] ?? [];

        $errosProjetos = $this->criarProjetosAprovados($projetosCriar);

        $retryDados = $config['retry_dados'] ?? null;

        if ($retryDados) {
            $rows = collect($retryDados);
        } else {
            $columnMapping = $config['columns'] ?? $config;
            $valueMapping = $config['values'] ?? [];
            $headerRow = $config['headerRow'] ?? null;
            $sheet = $config['sheet'] ?? 0;
            $colMap = $config['columnMap'] ?? [];

            $ext = pathinfo($log->arquivo_path, PATHINFO_EXTENSION);
            $tempPath = tempnam(sys_get_temp_dir(), 'import_').'.'.$ext;
            file_put_contents($tempPath, Storage::disk((string) config('filesystems.media_disk', 'r2'))->get($log->arquivo_path));

            $rows = $parser->parseRows(
                $tempPath,
                $sheet,
                $columnMapping,
                $headerRow,
                $valueMapping,
                $colMap,
            );

            @unlink($tempPath);
        }

        $resolucoes = $config['resolucoes'] ?? [];
        $obrasIgnoradas = $config['obras_ignoradas'] ?? [];

        $erros = $errosProjetos;
        $criadas = 0;
        $atualizadas = 0;
        $ignoradas = 0;

        $log->update(['total_linhas' => $rows->count()]);

        foreach ($rows as $index => $row) {
            try {
                $codigo = $row['codigo'] ?? null;

                $dados = $parser->splitRowData($row);

                $stagingData = [
                    'importacao_log_id' => $log->id,
                    'linha_planilha' => $index + 2,
                    'codigo' => $codigo,
                    'dados' => $dados,
                ];

                if ($codigo && in_array($codigo, $obrasIgnoradas)) {
                    $stagingData['acao'] = 'ignorar';
                    ImportacaoStaging::create($stagingData);
                    $ignoradas++;

                    continue;
                }

                if (empty($dados['obra']['projeto_id']) && ! empty($codigo)) {
                    $projeto = Projeto::where('codigo', $codigo)->first();
                    if ($projeto) {
                        $dados['obra']['projeto_id'] = $projeto->id;
                        $row['projeto_id'] = $projeto->id;
                        $stagingData['dados'] = $dados;
                    }
                }

                if (! empty($codigo)) {
                    $existing = Obras::where('codigo', $codigo)->first();
                    if ($existing) {
                        $obraAtualizar = collect($dados['obra'] ?? [])->except('status')->toArray();
                        $projetoAtualizar = $dados['projeto'] ?? [];

                        if (isset($resolucoes[$codigo])) {
                            foreach ($resolucoes[$codigo] as $campo => $decisao) {
                                if ($decisao === 'manter') {
                                    unset($obraAtualizar[$campo]);
                                    unset($projetoAtualizar[$campo]);
                                }
                            }
                        }

                        $stagingData['dados'] = ['obra' => $obraAtualizar, 'projeto' => $projetoAtualizar];
                        $stagingData['acao'] = 'atualizar';
                        $stagingData['obra_existente_id'] = $existing->id;
                        $atualizadas++;
                    } else {
                        if (empty($row['projeto_id'])) {
                            $stagingData['acao'] = 'erro';
                            $stagingData['erro'] = [
                                'msg' => "Projeto nao encontrado para codigo '{$codigo}'",
                                'tipo' => 'projeto_nao_encontrado',
                            ];
                            $stagingData['dados'] = $dados;
                            ImportacaoStaging::create($stagingData);
                            $erros[] = [
                                'linha' => $index + 2,
                                'msg' => "Projeto nao encontrado para codigo '{$codigo}'",
                                'tipo' => 'projeto_nao_encontrado',
                                'codigo' => $codigo,
                                'dados' => $row,
                            ];

                            continue;
                        }
                        $stagingData['acao'] = 'criar';
                        $criadas++;
                    }
                } else {
                    $stagingData['acao'] = 'criar';
                    $criadas++;
                }

                ImportacaoStaging::create($stagingData);
            } catch (\Exception $e) {
                $erros[] = [
                    'linha' => $index + 2,
                    'msg' => Str($e->getMessage())->limit(200),
                    'tipo' => 'outro',
                ];
            }

            if (($index + 1) % 10 === 0) {
                $log->update([
                    'linhas_criadas' => $criadas,
                    'linhas_atualizadas' => $atualizadas,
                    'linhas_erro' => count($erros),
                ]);
            }
        }

        $log->update([
            'status' => 'staged',
            'linhas_criadas' => $criadas,
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

    private function criarProjetosAprovados(array $projetos): array
    {
        $erros = [];

        foreach ($projetos as $dados) {
            $codigo = $dados['codigo'] ?? '?';

            $existente = Projeto::where('codigo', $codigo)->first();
            if ($existente) {
                $camposAtualizaveis = ['marca', 'endereco'];
                $atualizacoes = collect($dados)
                    ->only($camposAtualizaveis)
                    ->filter(fn ($v) => $v !== null && $v !== '')
                    ->toArray();

                if (! empty($atualizacoes)) {
                    $existente->update($atualizacoes);
                }

                continue;
            }

            $estadoId = $dados['estado_id'] ?? null;
            $cidadeId = $dados['cidade_id'] ?? null;
            $paisId = $dados['pais_id'] ?? null;
            $etapaId = $dados['etapa_id'] ?? null;

            if (! $cidadeId && $estadoId && ! empty($dados['cidade_nome'])) {
                $cidade = Cidade::firstOrCreate(
                    ['estado_id' => $estadoId, 'nome' => trim($dados['cidade_nome'])],
                );
                $cidadeId = $cidade->id;
            }

            $faltantes = [];
            if (! $estadoId) {
                $faltantes[] = 'estado';
            }
            if (! $cidadeId) {
                $faltantes[] = 'cidade';
            }
            if (! $paisId) {
                $faltantes[] = 'pais';
            }
            if (! $etapaId) {
                $faltantes[] = 'etapa';
            }

            if (! empty($faltantes)) {
                $erros[] = [
                    'linha' => 0,
                    'msg' => "Projeto '{$codigo}' nao criado: faltam ".implode(', ', $faltantes),
                    'tipo' => 'projeto_nao_criado',
                    'codigo' => $codigo,
                    'dados_projeto' => $dados,
                ];

                continue;
            }

            Projeto::create([
                'codigo' => $codigo,
                'nome' => $dados['nome'],
                'sigla' => $dados['sigla'],
                'marca' => $dados['marca'],
                'user_id' => $this->userId,
                'etapa_id' => $etapaId,
                'estado_id' => $estadoId,
                'cidade_id' => $cidadeId,
                'pais_id' => $paisId,
            ]);
        }

        return $erros;
    }

    private function notifyStaged(ImportacaoLog $log): void
    {
        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        $body = "{$log->linhas_criadas} para criar, {$log->linhas_atualizadas} para atualizar";
        if ($log->linhas_erro > 0) {
            $body .= ", {$log->linhas_erro} com erro";
        }

        Notification::make()
            ->title('Dados preparados para revisao')
            ->body($body)
            ->info()
            ->sendToDatabase($user);
    }

    private function notifyFailure(): void
    {
        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        Notification::make()
            ->title('Falha na importacao')
            ->body('Ocorreu um erro ao processar a planilha. Verifique o log de importacao.')
            ->danger()
            ->sendToDatabase($user);
    }
}
