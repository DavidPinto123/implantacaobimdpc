<?php

namespace App\Services;

use App\Models\RelatorioVisitaTecnica;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class VisitaTecnicaPdfService
{
    public function getViewData(RelatorioVisitaTecnica $record): array
    {
        $record->loadMissing(['marca', 'projeto']);

        [$totalSim, $totalNao, $totalNa, $totalItens] = $this->calculateTotals($record);
        [$comentarios, $anexos, $videos] = $this->calculateComplements($record);

        return [
            'record' => $record,
            'marca' => $record->marca,
            'projeto' => $record->projeto,
            'totalSim' => $totalSim,
            'totalNao' => $totalNao,
            'totalNa' => $totalNa,
            'totalItens' => $totalItens,
            'comentarios' => $comentarios,
            'alertas' => 0,
            'graficos' => 0,
            'anexos' => $anexos,
            'videos' => $videos,
        ];
    }

    public function makePdf(RelatorioVisitaTecnica $record)
    {
        ini_set('memory_limit', '1024M');
        // ini_set('max_execution_time', '180');

        return Pdf::loadView(
            'invoices.pdfVisitaTecnica',
            $this->getViewData($record)
        )
            ->setPaper('a4')
            ->setWarnings(false)
            ->setOption('isRemoteEnabled', true);
    }

    public function hasValidStoredPdf(RelatorioVisitaTecnica $record): bool
    {
        if (empty($record->pdf_path) || empty($record->pdf_generated_at)) {
            return false;
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk((string) config('filesystems.media_disk', 'r2'));

        if (! $disk->exists($record->pdf_path)) {
            return false;
        }

        if ($record->updated_at === null) {
            return true;
        }

        return $record->pdf_generated_at >= $record->updated_at;
    }

    public function isGenerating(RelatorioVisitaTecnica $record): bool
    {
        if (empty($record->pdf_generating_at)) {
            return false;
        }

        return now()->diffInMinutes($record->pdf_generating_at) < 15;
    }

    public function markAsGenerating(RelatorioVisitaTecnica $record): void
    {
        $record->forceFill([
            'pdf_generating_at' => now(),
        ])->saveQuietly();
    }

    public function clearGeneratingFlag(RelatorioVisitaTecnica $record): void
    {
        $record->forceFill([
            'pdf_generating_at' => null,
        ])->saveQuietly();
    }

    public function invalidatePdf(RelatorioVisitaTecnica $record): void
    {
        $record->forceFill([
            'pdf_generated_at' => null,
        ])->saveQuietly();
    }

    public function generateAndStorePdf(RelatorioVisitaTecnica $record): string
    {
        $pdf = $this->makePdf($record);

        $nomeArquivo = 'Relatorio-Visita-Tecnica-'.$record->numero_relatorio_vt.'.pdf';
        $filename = 'relatorios-vt/'.$record->numero_relatorio_vt.'/pdf/'.$nomeArquivo;

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk((string) config('filesystems.media_disk', 'r2'));

        $disk->put($filename, $pdf->output());

        $record->forceFill([
            'pdf_path' => $filename,
            'pdf_generated_at' => now(),
            'pdf_generating_at' => null,
        ])->saveQuietly();

        return $filename;
    }

    private function getAreas(): array
    {
        return [
            '1 - Elétrica/Telefonia/Internet' => [
                'entrada_de_energia',
                'energia_carga_superior_150',
                'energia_provisoria',
                'spda',
                'telegonia_dg',
            ],
            '2 - Estrutura/Cobertura/Acústica' => [
                'cobertura_isolamento',
                'cobertura_vao_1_5',
                'estrutura_fachada',
                'permitidas_furacoes_laje',
                'sobrecarga_minima_laje',
                'sobrecarga_minima_laje_teto',
                'local_tomada_ar_externo_exaustao',
                'alvenaria_periferia_existente',
                'reboco_interno_externo_existente',
                'estanqueidade',
            ],
            '3 - Área técnica' => [
                'necessario_estrutura_auxiliar',
                'area_tecnica_externa_existente',
                'prever_acustica_condensadores',
                'prever_protecao_condensadores',
            ],
            '4 - Hidráulica/Esgoto/Gás' => [
                'reservatorio_agua_existente',
                'reservatorio_incendio_existente',
                'ponto_esgoto_existente_shell',
                'rede_gas_disponivel',
                'medidor_agua_instalado_ligado',
            ],
            '5 - Arquitetura/Civil' => [
                'planta_demarcacao_area',
                'pd_acima_livre',
                'necessario_elevador_plataforma',
                'piso_acabamento_polido',
                'necessario_pelicula_fachada',
                'prever_marquise',
                'prever_porta_enrolar',
                'necessario_porta_enrolar',
                'caixilhos_vidros_existentes',
                'prever_impermeabilizacao',
            ],
            '6 - Comentários Adicionais' => [
                'observacoes_gerais',
            ],
        ];
    }

    private function calculateTotals(RelatorioVisitaTecnica $record): array
    {
        $totalSim = 0;
        $totalNao = 0;
        $totalNa = 0;
        $totalItens = 0;

        foreach ($this->getAreas() as $campos) {
            foreach ($campos as $campo) {
                $valor = $this->normalizeAnswer($record->{$campo} ?? null);

                if ($valor === 1) {
                    $totalSim++;
                } elseif ($valor === 0) {
                    $totalNao++;
                } else {
                    $totalNa++;
                }

                $totalItens++;
            }
        }

        return [$totalSim, $totalNao, $totalNa, $totalItens];
    }

    private function calculateComplements(RelatorioVisitaTecnica $record): array
    {
        $comentarios = 0;
        $anexos = 0;
        $videos = 0;

        foreach ($record->getAttributes() as $campo => $valor) {
            if ($valor === null || $valor === '') {
                continue;
            }

            if (str_starts_with($campo, 'descricao_')) {
                $comentarios++;
            }

            if (str_starts_with($campo, 'foto_')) {
                $anexos += $this->countFiles($valor);
            }

            if ($campo === 'link_drive_fotos_e_videos' && ! empty($valor)) {
                $videos++;
            }
        }

        return [$comentarios, $anexos, $videos];
    }

    private function countFiles(mixed $value): int
    {
        if (is_array($value)) {
            return count($value);
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                return count($decoded);
            }
        }

        return 0;
    }

    private function normalizeAnswer(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value === true) {
            return 1;
        }

        if ($value === false) {
            return 0;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        if (is_string($value)) {
            return 1;
        }

        return null;
    }
}
