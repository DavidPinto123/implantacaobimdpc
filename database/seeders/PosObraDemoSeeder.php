<?php

namespace Database\Seeders;

use App\Enums\PosObra\StatusPendencia;
use App\Enums\PosObra\UrgenciaPendencia;
use App\Models\Construtora;
use App\Models\Obras;
use App\Models\PosObra\AtualizacaoStatus;
use App\Models\PosObra\DisciplinaConfig;
use App\Models\PosObra\Pendencia;
use App\Models\Projeto;
use App\Models\User;
use Illuminate\Database\Seeder;

class PosObraDemoSeeder extends Seeder
{
    public function run(): void
    {
        $gestor = User::role('super_admin')->firstOrFail();
        $construtoras = Construtora::pluck('id')->toArray();
        $disciplinas = DisciplinaConfig::where('ativo', true)->pluck('id')->toArray();
        $projetos = Projeto::take(5)->pluck('id')->toArray();

        // ── Criar 3 obras inauguradas (Pós Obra) ──────────────────────────────
        $obrasDemo = [
            [
                'sigla' => 'POA-001',
                'unidade' => 'Smart Fit Paulista',
                'status' => 'Inaugurada',
                'inauguracao' => now()->subMonths(2),
                'uf' => 'SP',
                'cidade' => 'São Paulo',
            ],
            [
                'sigla' => 'POA-002',
                'unidade' => 'Smart Fit Lapa',
                'status' => 'Inaugurada',
                'inauguracao' => now()->subMonth(),
                'uf' => 'SP',
                'cidade' => 'São Paulo',
            ],
            [
                'sigla' => 'POA-003',
                'unidade' => 'Smart Fit Campinas Centro',
                'status' => 'Inaugurada',
                'inauguracao' => now()->subWeeks(2),
                'uf' => 'SP',
                'cidade' => 'Campinas',
            ],
        ];

        $obras = [];
        foreach ($obrasDemo as $i => $dados) {
            $projeto = Projeto::find($projetos[$i] ?? $projetos[0]);
            $obra = Obras::create(array_merge($dados, [
                'projeto_id' => $projeto->id,
                'percentual_obra_executado' => 100,
                'gestor_pos_obra' => $gestor->name,
            ]));
            $obras[] = $obra;
            $this->command->info("Obra criada: {$obra->sigla} — {$obra->unidade}");
        }

        // ── Pendências demo cobrindo todos os status e urgências ──────────────
        $pendenciasDemo = [
            // Obra 1 — mix de status
            [
                'obra' => $obras[0],
                'status' => StatusPendencia::REGISTRADA,
                'urgencia' => UrgenciaPendencia::P3,
                'descricao' => 'Infiltração no vestiário masculino — parede lateral.',
                'local' => 'Vestiário masculino',
                'disciplina' => 'Hidráulica',
                'impacto' => true,
                'construtora' => $construtoras[0] ?? null,
            ],
            [
                'obra' => $obras[0],
                'status' => StatusPendencia::NOTIFICADA_PRESTADORA,
                'urgencia' => UrgenciaPendencia::P2,
                'descricao' => 'Ar condicionado do salão principal não resfria adequadamente.',
                'local' => 'Salão de musculação',
                'disciplina' => 'Ar Condicionado',
                'impacto' => true,
                'construtora' => $construtoras[0] ?? null,
            ],
            [
                'obra' => $obras[0],
                'status' => StatusPendencia::PENDENTE_COM_PRAZO,
                'urgencia' => UrgenciaPendencia::P1,
                'descricao' => 'Pintura descascando na recepção.',
                'local' => 'Recepção',
                'disciplina' => 'Pintura',
                'impacto' => false,
                'data_termino' => now()->addDays(5),
                'construtora' => $construtoras[0] ?? null,
            ],
            [
                'obra' => $obras[0],
                'status' => StatusPendencia::EM_EXECUCAO,
                'urgencia' => UrgenciaPendencia::P2,
                'descricao' => 'Porta de entrada com fechadura danificada.',
                'local' => 'Entrada principal',
                'disciplina' => 'Porta',
                'impacto' => true,
                'data_inicio' => now()->subDays(3),
                'data_termino' => now()->addDays(2),
                'construtora' => $construtoras[1] ?? $construtoras[0] ?? null,
            ],
            [
                'obra' => $obras[0],
                'status' => StatusPendencia::AGUARDANDO_APROVACAO,
                'urgencia' => UrgenciaPendencia::P1,
                'descricao' => 'Piso solto na área de peso livre.',
                'local' => 'Área de peso livre',
                'disciplina' => 'Piso',
                'impacto' => false,
                'data_inicio' => now()->subDays(7),
                'data_termino' => now()->subDays(1),
                'construtora' => $construtoras[0] ?? null,
            ],
            [
                'obra' => $obras[0],
                'status' => StatusPendencia::CONCLUIDA,
                'urgencia' => UrgenciaPendencia::P1,
                'descricao' => 'Lâmpada queimada no corredor de acesso.',
                'local' => 'Corredor',
                'disciplina' => 'Elétrica',
                'impacto' => false,
                'data_inicio' => now()->subDays(10),
                'data_termino' => now()->subDays(8),
                'data_conclusao' => now()->subDays(8),
                'construtora' => $construtoras[0] ?? null,
            ],

            // Obra 2 — casos especiais
            [
                'obra' => $obras[1],
                'status' => StatusPendencia::EM_EXECUCAO,
                'urgencia' => UrgenciaPendencia::P3,
                'descricao' => 'Vazamento na tubulação de gás — risco de segurança.',
                'local' => 'Área técnica',
                'disciplina' => 'Gás',
                'impacto' => true,
                'data_inicio' => now()->subDays(1),
                'data_termino' => now()->addDay(),
                'construtora' => $construtoras[0] ?? null,
            ],
            [
                'obra' => $obras[1],
                'status' => StatusPendencia::AS_ORCAMENTOS,
                'urgencia' => UrgenciaPendencia::P2,
                'descricao' => 'Estrutura metálica da fachada com oxidação.',
                'local' => 'Fachada externa',
                'disciplina' => 'Serralheria',
                'impacto' => false,
                'construtora' => $construtoras[1] ?? $construtoras[0] ?? null,
            ],
            [
                'obra' => $obras[1],
                'status' => StatusPendencia::GARANTIA_SOLICITADA,
                'urgencia' => UrgenciaPendencia::P1,
                'descricao' => 'Trinca em parede estrutural — cobertura de garantia.',
                'local' => 'Parede lateral',
                'disciplina' => 'Civil',
                'impacto' => true,
                'construtora' => $construtoras[0] ?? null,
            ],

            // Obra 3 — atrasadas (SLA vencido)
            [
                'obra' => $obras[2],
                'status' => StatusPendencia::PENDENTE_COM_PRAZO,
                'urgencia' => UrgenciaPendencia::P3,
                'descricao' => 'Sistema CFTV fora do ar — câmeras sem sinal.',
                'local' => 'Central de segurança',
                'disciplina' => 'CFTV',
                'impacto' => true,
                'data_termino' => now()->subDays(3), // ATRASADA
                'construtora' => $construtoras[0] ?? null,
            ],
            [
                'obra' => $obras[2],
                'status' => StatusPendencia::EM_EXECUCAO,
                'urgencia' => UrgenciaPendencia::P2,
                'descricao' => 'Forro do banheiro com umidade e mofo.',
                'local' => 'Banheiro feminino',
                'disciplina' => 'Forro',
                'impacto' => false,
                'data_inicio' => now()->subDays(5),
                'data_termino' => now()->subDays(2), // ATRASADA
                'construtora' => $construtoras[1] ?? $construtoras[0] ?? null,
            ],
        ];

        $sequencia = 1;
        $ano = now()->year;

        foreach ($pendenciasDemo as $dados) {
            $obra = $dados['obra'];
            $disciplina = DisciplinaConfig::where('label', $dados['disciplina'])->first()
                ?? DisciplinaConfig::first();

            $codigo = sprintf('PO-%d-%04d', $ano, $sequencia++);

            $pendencia = Pendencia::create([
                'codigo' => $codigo,
                'obras_id' => $obra->id,
                'gestor_id' => $gestor->id,
                'construtora_id' => $dados['construtora'],
                'disciplina_config_id' => $disciplina?->id,
                'descricao' => $dados['descricao'],
                'local_especifico' => $dados['local'],
                'urgencia' => $dados['urgencia']->value,
                'status' => $dados['status']->value,
                'impacto_operacao' => $dados['impacto'],
                'data_inicio' => $dados['data_inicio'] ?? null,
                'data_termino' => $dados['data_termino'] ?? null,
                'data_conclusao' => $dados['data_conclusao'] ?? null,
            ]);

            // Registra histórico de status baseado no status atual
            $this->criarHistoricoStatus($pendencia, $dados['status'], $gestor->name);

            $this->command->info("  ✓ {$codigo} [{$dados['urgencia']->label()}] {$dados['status']->label()} — {$obra->sigla}");
        }

        $this->command->info("\nDemo criado: ".count($obras).' obras inauguradas, '.count($pendenciasDemo).' pendências.');
    }

    private function criarHistoricoStatus(Pendencia $pendencia, StatusPendencia $statusFinal, string $autor): void
    {
        // Simula histórico percorrendo o fluxo até o status final
        $fluxo = [
            StatusPendencia::REGISTRADA,
            StatusPendencia::NOTIFICADA_PRESTADORA,
            StatusPendencia::PENDENTE_COM_PRAZO,
            StatusPendencia::EM_EXECUCAO,
            StatusPendencia::AGUARDANDO_APROVACAO,
            StatusPendencia::CONCLUIDA,
        ];

        // Status "saída" não têm fluxo linear
        if ($statusFinal->isTerminal() && ! in_array($statusFinal, [StatusPendencia::CONCLUIDA])) {
            AtualizacaoStatus::create([
                'pendencia_id' => $pendencia->id,
                'status_anterior' => StatusPendencia::EM_EXECUCAO->value,
                'status_novo' => $statusFinal->value,
                'comentario' => 'Status definido via demo.',
                'atualizado_por' => $autor,
                'created_at' => now()->subDays(rand(1, 5)),
                'updated_at' => now()->subDays(rand(1, 5)),
            ]);

            return;
        }

        $posicaoFinal = array_search($statusFinal, $fluxo);
        if ($posicaoFinal === false) {
            return;
        }

        $anterior = null;
        foreach ($fluxo as $pos => $status) {
            if ($pos > $posicaoFinal) {
                break;
            }
            AtualizacaoStatus::create([
                'pendencia_id' => $pendencia->id,
                'status_anterior' => $anterior?->value,
                'status_novo' => $status->value,
                'comentario' => $pos === 0 ? 'Pendência registrada.' : null,
                'atualizado_por' => $autor,
                'created_at' => now()->subDays($posicaoFinal - $pos + 1),
                'updated_at' => now()->subDays($posicaoFinal - $pos + 1),
            ]);
            $anterior = $status;
        }
    }
}
