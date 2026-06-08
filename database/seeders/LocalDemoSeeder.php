<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AsStatus;
use App\Enums\StatusControleNotaFiscalNota;
use App\Enums\TipoUnidade;
use App\Models\AsEscopo;
use App\Models\AutorizacaoServico;
use App\Models\CapexSimulacao;
use App\Models\CapexSimulacaoItem;
use App\Models\Construtora;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalAuxiliar;
use App\Models\ControleNotaFiscalItem;
use App\Models\ControleNotaFiscalNota;
use App\Models\Obras;
use App\Services\AutorizacaoServicoFluxoService;
use App\Services\AutorizacaoServicoService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class LocalDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException('LocalDemoSeeder só pode ser executado no ambiente local.');
        }

        $this->seedReferenceData();

        $this->call([
            LocalUserSeeder::class,
            AsEscopoSeeder::class,
            PosObraSeeder::class,
            AtualizacaoObraPermissionSeeder::class,
            PosObraPermissionsSeeder::class,
            CronogramaFasePermissionSeeder::class,
            ZerarDatasPermissionSeeder::class,
        ]);

        $this->syncUserSetores();

        $this->callIfTableIsEmpty('cronograma_templates', CronogramaTemplateSeeder::class);
        $this->callIfTableIsEmpty('capex_disciplinas', CapexEstruturaSeeder::class);
        $this->callExcelImportSeederIfTableIsEmpty('ambientes', AmbientesSeeder::class);
        $this->callExcelImportSeederIfTableIsEmpty('departamentos', DepartamentosSeeder::class);

        $this->seedProjetosDemo();

        $this->seedObrasDemo();
        $this->seedPendenciasDemo();

        $this->call([ControleNotaFiscalSeeder::class]);
        $this->syncLocalProfilePermissions();
        $this->syncControleNotaFiscalEscoposDemo();

        $this->callIfTableIsEmpty('atualizacoes_obra', FeedObraExemploSeeder::class);

        $this->seedAdditionalDemoData();
    }

    private function seedReferenceData(): void
    {
        $paisId = $this->updateOrInsertAndGetId('pais', ['nome' => 'Brasil']);

        $estadoId = $this->updateOrInsertAndGetId('estados', [
            'pais_id' => $paisId,
            'nome' => 'São Paulo',
        ], [
            'uf' => 'SP',
        ]);

        $cidadeId = $this->updateOrInsertAndGetId('cidades', [
            'estado_id' => $estadoId,
            'nome' => 'São Paulo',
        ]);

        foreach ([
            'Prospecção',
            'Reunião de comitê',
            'Viabilidade',
            'Briefing e Layout',
            'Ordem de investimento',
            'Contrato',
            'Projetos de obra',
            'Orçamentos e equalização',
            'Em Projeto',
            'Em Obra',
            'Inaugurada',
        ] as $etapa) {
            $this->updateOrInsertAndGetId('etapas', ['nome' => $etapa]);
        }

        $this->updateOrInsertAndGetId('marcas', ['nome' => 'Smart Fit']);
        $this->updateOrInsertAndGetId('marcas', ['nome' => 'Bio Ritmo']);

        $this->updateOrInsertAndGetId('pipes', ['pipeline' => 'EXPANSÃO']);
        $this->updateOrInsertAndGetId('pipes', ['pipeline' => 'LAND BANK 2027']);

        $this->updateOrInsertAndGetId('setores', ['setor' => 'Obras']);
        $this->updateOrInsertAndGetId('setores', ['setor' => 'Comercial']);
        $this->updateOrInsertAndGetId('setores', ['setor' => 'Terceiros Fornecedor']);
        $this->updateOrInsertAndGetId('setores', ['setor' => 'Orçamento']);
        $this->updateOrInsertAndGetId('setores', ['setor' => 'Arquitetura']);

        $this->updateOrInsertAndGetId(
            'construtoras',
            ['cnpj' => '12345678000199'],
            [
                'nome' => 'Fornecedor Demo Local',
                'telefone' => '(11) 3000-0001',
                'email' => 'contato.construtora@example.test',
            ],
        );

        DB::table('construtoras')
            ->where('nome', 'Fornecedor Demo Local')
            ->update([
                'email' => 'contato.construtora@example.test; financeiro.construtora@example.test',
                'telefone' => '(11) 3000-0001',
                'updated_at' => now(),
            ]);

        $this->updateOrInsertAndGetId('empresas', [
            'cnpj' => '98765432000188',
        ], [
            'nome' => 'Empresa Demo Local LTDA',
            'nome_fantasia' => 'Empresa Demo',
            'responsavel' => 'Administrador Local',
            'email' => 'empresa.demo@example.test',
            'contato' => '(11) 4000-0000',
            'tipo' => 'CLIENTE',
            'status' => true,
            'cidade_id' => $cidadeId,
            'estado_id' => $estadoId,
            'pais_id' => $paisId,
        ]);
    }

    private function callIfTableIsEmpty(string $table, string $seederClass): void
    {
        if (! DB::getSchemaBuilder()->hasTable($table)) {
            return;
        }

        if (DB::table($table)->count() > 0) {
            return;
        }

        $this->call([$seederClass]);
    }

    private function callExcelImportSeederIfTableIsEmpty(string $table, string $seederClass): void
    {
        if (! file_exists(public_path('dados_exportados.xlsx'))) {
            return;
        }

        $this->callIfTableIsEmpty($table, $seederClass);
    }

    private function syncUserSetores(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('setor_user')) {
            return;
        }

        $userIds = DB::table('users')->pluck('id');
        $setorIds = DB::table('setores')->pluck('id');

        foreach ($userIds as $userId) {
            foreach ($setorIds as $setorId) {
                $exists = DB::table('setor_user')
                    ->where('user_id', $userId)
                    ->where('setor_id', $setorId)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('setor_user')->insert([
                    'user_id' => $userId,
                    'setor_id' => $setorId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function seedAdditionalDemoData(): void
    {
        $this->seedTaskDemoData();
        $this->seedDadosDemo();
        $this->seedAcompanhamentoDemo();
        $this->seedListaEmails();
        $this->seedRegiaoInteresses();
        $this->seedMatterports();
        $this->seedGestaoObras();
        $this->seedObraRecebimentos();
        $this->seedObraDocumentosDemo();
        $this->seedOrdemInvestimentosDemo();
        $this->seedControlePedidosDemo();
        $this->seedFaixasAreaDemo();
        $this->seedCapexSimulacoesDemo();
        $this->seedControleAsFluxoOiDemo();
        $this->seedFluxoFiscalDemo();
        $this->seedRelatorioVisitaTecnicaDemo();
        $this->seedRelatorioFotograficoDemo();
        $this->seedImportacoesDemo();
        $this->seedHistoricoProjetosDemo();
    }

    private function seedObraDocumentosDemo(): void
    {
        if (! $this->hasTable('obra_documentos') || ! $this->hasTable('obras')) {
            return;
        }

        $usuarioId = DB::table('users')->where('email', 'gestor.obra@example.test')->value('id')
            ?? DB::table('users')->orderBy('id')->value('id');
        $construtoraId = $this->hasTable('construtoras')
            ? DB::table('construtoras')->orderBy('id')->value('id')
            : null;

        $obrasIds = DB::table('obras')->orderBy('id')->limit(3)->pluck('id');

        foreach ($obrasIds as $obraId) {
            $documentos = [
                ['nome' => "DEMO-DOC-{$obraId}-ASBUILT", 'status' => 'pendente'],
                ['nome' => "DEMO-DOC-{$obraId}-MANUAL", 'status' => 'recebido'],
            ];

            foreach ($documentos as $documento) {
                if (DB::table('obra_documentos')->where('obra_id', $obraId)->where('nome', $documento['nome'])->exists()) {
                    continue;
                }

                $payload = [
                    'obra_id' => $obraId,
                    'nome' => $documento['nome'],
                    'status' => $documento['status'],
                    'arquivo_path' => null,
                    'arquivo_nome' => null,
                    'usuario_id' => $usuarioId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if ($this->hasColumn('obra_documentos', 'construtora_id')) {
                    $payload['construtora_id'] = $construtoraId;
                }

                if ($this->hasColumn('obra_documentos', 'arquivos_paths')) {
                    $payload['arquivos_paths'] = json_encode([], JSON_THROW_ON_ERROR);
                }

                if ($this->hasColumn('obra_documentos', 'arquivos_nomes')) {
                    $payload['arquivos_nomes'] = json_encode([], JSON_THROW_ON_ERROR);
                }

                DB::table('obra_documentos')->insert($payload);
            }
        }
    }

    private function seedOrdemInvestimentosDemo(): void
    {
        if (! $this->hasTable('ordem_investimentos') || ! $this->hasTable('projetos')) {
            return;
        }

        $userId = DB::table('users')->where('email', 'coordenador.orcamentos@example.test')->value('id')
            ?? DB::table('users')->orderBy('id')->value('id');

        $projetos = DB::table('projetos')
            ->where('sigla', 'like', 'DEMO-PJT-%')
            ->orderBy('id')
            ->limit(2)
            ->get(['id', 'sigla']);

        foreach ($projetos as $index => $projeto) {
            if (DB::table('ordem_investimentos')->where('projeto_id', $projeto->id)->exists()) {
                continue;
            }

            $area = 900 + ($index * 120);
            $valorTotal = 980000 + ($index * 130000);

            $payload = [
                'projeto_id' => $projeto->id,
                'valor_total' => $valorTotal,
                'area' => $area,
                'custo_m2' => round($valorTotal / $area, 2),
                'estrutura' => json_encode([
                    ['nome' => 'EXECUÇÃO DE OBRA CIVIL - RECHEIO', 'padrao' => 1000, 'ad' => 250],
                    ['nome' => 'SHELL (OBRA CIVIL)', 'padrao' => 650, 'ad' => 150],
                ], JSON_THROW_ON_ERROR),
                'pdf_path' => null,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($this->hasColumn('ordem_investimentos', 'status_oi')) {
                $payload['status_oi'] = 'em_aprovacao';
            }

            DB::table('ordem_investimentos')->insert($payload);
        }
    }

    private function seedControlePedidosDemo(): void
    {
        if (! $this->hasTable('controle_pedidos') || ! $this->hasTable('projetos')) {
            return;
        }

        $projetoId = DB::table('projetos')->where('sigla', 'DEMO-PJT-001')->value('id')
            ?? DB::table('projetos')->orderBy('id')->value('id');
        $construtoraId = $this->hasTable('construtoras')
            ? DB::table('construtoras')->orderBy('id')->value('id')
            : null;
        $responsavelOrc = DB::table('users')->where('email', 'coordenador.orcamentos@example.test')->value('id')
            ?? DB::table('users')->orderBy('id')->value('id');
        $gestorObra = DB::table('users')->where('email', 'gestor.obra@example.test')->value('id')
            ?? DB::table('users')->orderBy('id')->value('id');

        if ($projetoId === null) {
            return;
        }

        $pedidoId = DB::table('controle_pedidos')
            ->where('projeto_id', $projetoId)
            ->where('numero', 9001)
            ->value('id');

        if ($pedidoId === null) {
            $payload = [
                'projeto_id' => $projetoId,
                'elaboracao_contrato' => now()->subDays(15)->toDateString(),
                'cnpj' => '12345678000199',
                'status' => 'definitivo',
                'contratacao' => now()->subDays(10)->toDateString(),
                'observacoes' => 'Pedido DEMO para ambiente local.',
                'pedidos' => json_encode(['1_1' => true, '2_1' => false], JSON_THROW_ON_ERROR),
                'valor_oi' => 180000,
                'valor_realizado' => 42000,
                'realizado_nf' => 15000,
                'saldo' => 138000,
                'situacao' => 'em_processo',
                'responsavel_orc' => (string) $responsavelOrc,
                'gestor_obra' => (string) $gestorObra,
                'tamanho' => 'Médio',
                'numero' => 9001,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($this->hasColumn('controle_pedidos', 'construtora_id')) {
                $payload['construtora_id'] = $construtoraId;
            }

            $pedidoId = DB::table('controle_pedidos')->insertGetId($payload);
        }

        if (! $this->hasTable('controle_pedido_itens')) {
            return;
        }

        $itens = [
            ['codigo' => '1.1', 'nome' => 'EXECUÇÃO DE OBRA CIVIL - RECHEIO', 'contratado' => true, 'valor' => 1250.00],
            ['codigo' => '2.1', 'nome' => 'SHELL (OBRA CIVIL)', 'contratado' => false, 'valor' => 0.00],
        ];

        foreach ($itens as $item) {
            $exists = DB::table('controle_pedido_itens')
                ->where('controle_pedido_id', $pedidoId)
                ->where('codigo', $item['codigo'])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('controle_pedido_itens')->insert([
                'controle_pedido_id' => $pedidoId,
                'codigo' => $item['codigo'],
                'nome' => $item['nome'],
                'contratado' => $item['contratado'],
                'valor' => $item['valor'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedFaixasAreaDemo(): void
    {
        if (! $this->hasTable('as_faixa_areas')) {
            return;
        }

        $faixas = [
            ['nome' => 'Pequena', 'area_min' => 0, 'area_max' => 500],
            ['nome' => 'Média', 'area_min' => 501, 'area_max' => 1200],
            ['nome' => 'Grande', 'area_min' => 1201, 'area_max' => null],
        ];

        $faixaIds = [];

        foreach ($faixas as $faixa) {
            $faixaId = DB::table('as_faixa_areas')->where('nome', $faixa['nome'])->value('id');

            if ($faixaId === null) {
                $faixaId = DB::table('as_faixa_areas')->insertGetId([
                    'nome' => $faixa['nome'],
                    'area_min' => $faixa['area_min'],
                    'area_max' => $faixa['area_max'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $faixaIds[$faixa['nome']] = $faixaId;
        }

        if (! $this->hasTable('as_escopo_faixa_area') || ! $this->hasTable('as_escopos')) {
            return;
        }

        $escopos = DB::table('as_escopos')->orderBy('id')->limit(3)->get(['id']);

        foreach ($escopos as $escopoIndex => $escopo) {
            foreach (['Pequena' => 1600, 'Média' => 1850, 'Grande' => 2100] as $nomeFaixa => $valorBase) {
                $faixaId = $faixaIds[$nomeFaixa] ?? null;

                if ($faixaId === null) {
                    continue;
                }

                DB::table('as_escopo_faixa_area')->updateOrInsert(
                    [
                        'as_escopo_id' => $escopo->id,
                        'as_faixa_area_id' => $faixaId,
                    ],
                    [
                        'valor_m2' => $valorBase + ($escopoIndex * 50),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        }
    }

    private function seedCapexSimulacoesDemo(): void
    {
        if (! $this->hasTable('capex_simulacoes') || ! $this->hasTable('projetos')) {
            return;
        }

        $projeto = DB::table('projetos')->where('sigla', 'DEMO-PJT-001')->first(['id', 'nome', 'sigla', 'endereco']);

        if ($projeto === null) {
            return;
        }

        $faixa = $this->hasTable('as_faixa_areas')
            ? DB::table('as_faixa_areas')->where('nome', 'Média')->first(['id', 'nome'])
            : null;

        $simulacaoId = DB::table('capex_simulacoes')->where('sigla', 'DEMO-CAPEX-001')->value('id');

        if ($simulacaoId === null) {
            $simulacaoId = DB::table('capex_simulacoes')->insertGetId([
                'projeto_id' => $projeto->id,
                'nome' => 'Simulação CAPEX DEMO Local',
                'sigla' => 'DEMO-CAPEX-001',
                'endereco' => (string) ($projeto->endereco ?? 'Endereço DEMO'),
                'uf' => 'SP',
                'area_unidade' => 950,
                'fator_correcao' => 1.05,
                'as_faixa_area_id' => $faixa?->id,
                'faixa_nome' => $faixa?->nome,
                'custo_total_estimado' => 1450000,
                'custo_por_m2' => 1526.32,
                'status' => 1,
                'comentario' => 'DEMO-CAPEX: versão inicial para ambiente local.',
                'revisao' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! $this->hasTable('capex_simulacao_itens')) {
            return;
        }

        $shellEscopoId = $this->hasTable('as_escopos')
            ? DB::table('as_escopos')->where('grupo', 'Shell')->orderBy('id')->value('id')
            : null;
        $recheioEscopoId = $this->hasTable('as_escopos')
            ? DB::table('as_escopos')->where('numero_as', '01.1')->value('id')
            : null;

        $itens = [
            [
                'nome_escopo' => 'SHELL (OBRA CIVIL)',
                'as_escopo_id' => $shellEscopoId,
                'numero_complemento' => '',
                'tipo' => 'manual',
                'valor_base_m2' => 680000,
                'custo_estimado' => 714000,
            ],
            [
                'nome_escopo' => 'EXECUÇÃO DE OBRA CIVIL - RECHEIO',
                'as_escopo_id' => $recheioEscopoId,
                'numero_complemento' => '',
                'tipo' => 'auto',
                'valor_base_m2' => 760,
                'custo_estimado' => 758100,
            ],
            [
                'nome_escopo' => 'EXECUÇÃO DE OBRA CIVIL - RECHEIO COMPLEMENTO C1',
                'as_escopo_id' => $recheioEscopoId,
                'numero_complemento' => 'C1',
                'tipo' => 'manual',
                'valor_base_m2' => 125000,
                'custo_estimado' => 131250,
            ],
        ];

        foreach ($itens as $ordem => $item) {
            DB::table('capex_simulacao_itens')->updateOrInsert(
                [
                    'capex_simulacao_id' => $simulacaoId,
                    'nome_escopo' => $item['nome_escopo'],
                ],
                [
                    'as_escopo_id' => $item['as_escopo_id'],
                    'numero_complemento' => $item['numero_complemento'],
                    'tipo' => $item['tipo'],
                    'incluir' => true,
                    'ordem' => $ordem + 1,
                    'valor_base_m2' => $item['valor_base_m2'],
                    'valor_base_m2_editado' => $item['tipo'] === 'manual',
                    'area' => $item['tipo'] === 'auto' ? 950 : null,
                    'fator_correcao' => 1.05,
                    'custo_estimado' => $item['custo_estimado'],
                    'percentual' => $ordem === 0 ? 48.50 : 51.50,
                    'comentario' => 'Item DEMO-CAPEX',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        DB::table('capex_simulacoes')
            ->where('id', $simulacaoId)
            ->update([
                'custo_total_estimado' => collect($itens)->sum('custo_estimado'),
                'custo_por_m2' => round(collect($itens)->sum('custo_estimado') / 950, 2),
                'updated_at' => now(),
            ]);
    }

    private function seedControleAsFluxoOiDemo(): void
    {
        if (
            ! $this->hasTable('capex_simulacoes')
            || ! $this->hasTable('capex_simulacao_itens')
            || ! $this->hasTable('controle_nota_fiscals')
            || ! $this->hasTable('controle_nota_fiscal_items')
            || ! $this->hasTable('autorizacao_servicos')
            || ! $this->hasTable('controle_nota_fiscal_notas')
        ) {
            return;
        }

        $simulacao = CapexSimulacao::query()
            ->where('sigla', 'DEMO-CAPEX-001')
            ->with('itens.escopo')
            ->first();
        $construtora = Construtora::query()
            ->where('nome', 'Fornecedor Demo Local')
            ->first();

        if (! $simulacao instanceof CapexSimulacao || ! $construtora instanceof Construtora) {
            return;
        }

        $obra = Obras::query()
            ->where('projeto_id', $simulacao->projeto_id)
            ->first();

        if (! $obra instanceof Obras) {
            return;
        }

        $controle = ControleNotaFiscal::query()->updateOrCreate(
            [
                'obra_id' => $obra->id,
                'tipo_unidade' => TipoUnidade::EXPANSAO->value,
            ],
            [
                'status' => ControleNotaFiscal::STATUS_ATIVO,
                'data_base' => now()->toDateString(),
                'unidade' => $obra->unidade,
                'sigla' => $obra->sigla,
                'endereco' => $obra->endereco,
            ],
        );
        $usuarioEnvioId = DB::table('users')->where('email', 'coordenador.orcamentos@example.test')->value('id')
            ?? DB::table('users')->orderBy('id')->value('id');
        $usuarioAprovacaoId = DB::table('users')->where('email', 'gestor.obra@example.test')->value('id')
            ?? $usuarioEnvioId;

        foreach ($simulacao->itens->sortBy('ordem')->values() as $index => $itemSimulador) {
            if (! $itemSimulador instanceof CapexSimulacaoItem || ! filled($itemSimulador->as_escopo_id)) {
                continue;
            }

            $numeroComplemento = filled($itemSimulador->numero_complemento)
                ? (string) $itemSimulador->numero_complemento
                : null;
            $itemControle = ControleNotaFiscalItem::query()
                ->where('controle_nota_fiscal_id', $controle->id)
                ->where('as_escopo_id', $itemSimulador->as_escopo_id)
                ->where(function ($query) use ($numeroComplemento): void {
                    if (filled($numeroComplemento)) {
                        $query->where('numero_complemento', $numeroComplemento);

                        return;
                    }

                    $query->whereNull('numero_complemento')->orWhere('numero_complemento', '');
                })
                ->first();
            $itemControle ??= new ControleNotaFiscalItem([
                'controle_nota_fiscal_id' => $controle->id,
                'as_escopo_id' => $itemSimulador->as_escopo_id,
                'numero_complemento' => $numeroComplemento,
            ]);

            $valorEstimado = round((float) $itemSimulador->custo_estimado, 2);
            $valorFechado = round($valorEstimado * 0.92, 2);

            $itemControle->fill([
                'capex_simulacao_item_id' => $itemSimulador->id,
                'grupo' => $itemSimulador->escopo?->grupo,
                'numero_as' => $itemSimulador->escopo?->numero_as,
                'numero_complemento' => $numeroComplemento,
                'escopo' => $itemSimulador->escopo?->escopo ?? $itemSimulador->nome_escopo,
                'escopo_complementar' => filled($numeroComplemento) ? 'Complemento demo originado da Simulação OI.' : null,
                'empresa' => $construtora->nome,
                'quantidade' => 1,
                'percentual_total' => 100,
                'percentual_faturamento_mao_obra' => 60,
                'percentual_faturamento_material' => 40,
                'valor_estimado_as' => $valorEstimado,
                'valor_estimado_as_simulador' => $valorEstimado,
                'valor_estimado_as_editado_manualmente' => false,
                'valor_global_a' => $valorFechado,
                'total_medicao_a_menos_b' => $valorFechado,
                'valor_acumulado_medido' => 0,
                'saldo' => $valorFechado,
                'observacoes' => 'DEMO OI -> Controle AS: linha sincronizada com origem no Simulador OI.',
                'liberado_para_fornecedor_at' => now()->subDays(3),
                'sort_order' => 100 + $index,
            ]);
            $itemControle->save();

            $numeroAs = app(AutorizacaoServicoService::class)->gerarNumeroAsEstruturado(
                $obra,
                $itemSimulador->escopo,
                $construtora,
            );
            $autorizacaoServico = AutorizacaoServico::query()->updateOrCreate(
                [
                    'obra_id' => $obra->id,
                    'as_escopo_id' => $itemSimulador->as_escopo_id,
                    'construtora_id' => $construtora->id,
                    'numero_complemento' => $numeroComplemento ?? '',
                ],
                [
                    'status' => AsStatus::ENVIADA->value,
                    'numero_as' => $numeroAs,
                    'controle_nota_fiscal_item_id' => $itemControle->id,
                    'valor' => $valorFechado,
                    'valor_estimado' => $valorEstimado,
                    'created_by_id' => $usuarioEnvioId,
                    'enviado_por_id' => $usuarioEnvioId,
                    'enviado_em' => now()->subDays(3),
                    'observacoes' => 'DEMO OI -> AS: autorização enviada e liberada para importação de NF.',
                ],
            );

        }

        $itensFluxo = ControleNotaFiscalItem::query()
            ->where('controle_nota_fiscal_id', $controle->id)
            ->whereNotNull('capex_simulacao_item_id')
            ->orderBy('sort_order')
            ->get();

        $notaAnaliseItem = $itensFluxo->first();
        $notaAprovadaItem = $itensFluxo->firstWhere('numero_complemento', 'C1') ?? $itensFluxo->skip(1)->first();

        $this->updateOrCreateNotaDemoOi(
            item: $notaAnaliseItem,
            numeroNf: 'DEMO-OI-NF-ANALISE',
            status: StatusControleNotaFiscalNota::EM_ANALISE->value,
            importadoPorId: $usuarioEnvioId,
            decididoPorId: null,
            valor: 12500,
        );
        $this->updateOrCreateNotaDemoOi(
            item: $notaAprovadaItem,
            numeroNf: 'DEMO-OI-NF-APROVADA',
            status: StatusControleNotaFiscalNota::APROVADO->value,
            importadoPorId: $usuarioEnvioId,
            decididoPorId: $usuarioAprovacaoId,
            valor: 9800,
        );
    }

    private function updateOrCreateNotaDemoOi(
        ?ControleNotaFiscalItem $item,
        string $numeroNf,
        string $status,
        mixed $importadoPorId,
        mixed $decididoPorId,
        float $valor,
    ): void {
        if (! $item instanceof ControleNotaFiscalItem) {
            return;
        }

        $autorizacaoServicoId = AutorizacaoServico::query()
            ->where('controle_nota_fiscal_item_id', $item->id)
            ->value('id');

        ControleNotaFiscalNota::query()->updateOrCreate(
            ['numero_nf' => $numeroNf],
            [
                'autorizacao_servico_id' => $autorizacaoServicoId,
                'autorizacao_servico_adicional_id' => null,
                'importado_por_id' => $importadoPorId,
                'decidido_por_id' => $decididoPorId,
                'tipo_medicao' => ControleNotaFiscalNota::TIPO_MEDICAO_MATERIAL,
                'empresa' => (string) $item->empresa,
                'cnpj_fornecedor' => '12345678000199',
                'cnpj_faturamento' => '98765432000188',
                'instrucoes_pagamento' => 'pix',
                'valor_acumulado_medido_nf' => $valor,
                'emissao' => now()->subDays(5)->toDateString(),
                'envio' => now()->subDays(4)->toDateString(),
                'status' => $status,
                'decidido_em' => $status === StatusControleNotaFiscalNota::APROVADO->value ? now()->subDays(2) : null,
                'arquivo_path' => null,
                'boleto_path' => null,
                'observacoes' => 'DEMO OI -> NF: nota importada para validar aprovação no fluxo local.',
            ],
        );
    }

    private function seedFluxoFiscalDemo(): void
    {
        if (
            ! $this->hasTable('obras')
            || ! $this->hasTable('projetos')
            || ! $this->hasTable('controle_nota_fiscals')
            || ! $this->hasTable('controle_nota_fiscal_items')
            || ! $this->hasTable('controle_nota_fiscal_auxiliares')
            || ! $this->hasTable('autorizacao_servicos')
            || ! $this->hasTable('autorizacao_servico_adicionais')
            || ! $this->hasTable('elaboracao_aditivos')
        ) {
            return;
        }

        $gestorId = DB::table('users')->where('email', 'gestor.obra@example.test')->value('id');
        $orcamentistaId = DB::table('users')->where('email', 'coordenador.orcamentos@example.test')->value('id');
        $fornecedorUserId = DB::table('users')->where('email', 'fornecedor.terceiros@example.test')->value('id');
        $construtora = Construtora::query()->where('nome', 'Fornecedor Demo Local')->first();
        $obra = Obras::query()
            ->where('codigo', 'DEMO-OBRA-001')
            ->with('projeto')
            ->first();

        if (! $obra instanceof Obras || ! $construtora instanceof Construtora || $gestorId === null || $orcamentistaId === null || $fornecedorUserId === null) {
            return;
        }

        DB::table('projetos')
            ->where('id', $obra->projeto_id)
            ->update([
                'user_id' => $gestorId,
                'updated_at' => now(),
            ]);

        DB::table('users')
            ->whereIn('email', ['fornecedor.terceiros@example.test', 'fornecedor.obra@example.test'])
            ->update([
                'construtoras_id' => $construtora->id,
                'is_fornecedor' => true,
                'is_active' => true,
                'must_change_password' => false,
                'updated_at' => now(),
            ]);

        AutorizacaoServico::query()
            ->whereIn('numero_as', ['AD-DEMO-01', 'AD-DEMO-02'])
            ->orWhere('observacoes', 'like', 'AS demo adicional gerada automaticamente%')
            ->delete();
        ControleNotaFiscalNota::query()
            ->whereIn('numero_nf', ['DEMO-NF-0001', 'DEMO-NF-0002'])
            ->delete();
        if ($this->hasTable('autorizacao_servico_adicional_items')) {
            DB::table('autorizacao_servico_adicional_items')
                ->whereIn('autorizacao_servico_adicional_id', DB::table('autorizacao_servico_adicionais')->where('numero_asa', 'DEMO-ASA-0001')->pluck('id'))
                ->delete();
        }
        DB::table('autorizacao_servico_adicionais')
            ->where('numero_asa', 'DEMO-ASA-0001')
            ->delete();
        if ($this->hasTable('elaboracao_aditivo_items')) {
            DB::table('elaboracao_aditivo_items')
                ->whereIn('elaboracao_aditivo_id', DB::table('elaboracao_aditivos')->where('ref_servico', 'DEMO-ADIT-001')->pluck('id'))
                ->delete();
        }
        DB::table('elaboracao_aditivos')
            ->where('ref_servico', 'DEMO-ADIT-001')
            ->delete();
        ControleNotaFiscalAuxiliar::query()
            ->where(function ($query): void {
                $query
                    ->where('numero_as', 'like', 'AD-CALC-%')
                    ->orWhereIn('numero_as', ['AD-DEMO-01', 'AD-DEMO-02']);
            })
            ->delete();

        $controle = ControleNotaFiscal::query()->updateOrCreate(
            [
                'obra_id' => $obra->id,
                'tipo_unidade' => TipoUnidade::EXPANSAO->value,
            ],
            [
                'status' => ControleNotaFiscal::STATUS_ATIVO,
                'data_base' => now()->toDateString(),
                'unidade' => $obra->unidade,
                'sigla' => $obra->sigla,
                'endereco' => $obra->endereco,
            ],
        );

        $asEscopos = collect([
            ['grupo' => 'Shell', 'numero_as' => 'DEMO-AS-001', 'escopo' => 'DEMO AS - criar AS'],
            ['grupo' => 'Shell', 'numero_as' => 'DEMO-AS-002', 'escopo' => 'DEMO AS - criada aguardando envio'],
            ['grupo' => 'Recheio', 'numero_as' => 'DEMO-AS-003', 'escopo' => 'DEMO AS - enviada com NF em análise'],
            ['grupo' => 'Recheio', 'numero_as' => 'DEMO-AS-004', 'escopo' => 'DEMO AS - enviada com NF aprovada'],
        ])->mapWithKeys(function (array $escopo): array {
            $model = AsEscopo::query()->updateOrCreate(
                ['numero_as' => $escopo['numero_as']],
                [
                    'grupo' => $escopo['grupo'],
                    'escopo' => $escopo['escopo'],
                    'is_active' => true,
                    'percentual_faturamento_mao_obra_default' => 60,
                    'percentual_faturamento_material_default' => 40,
                ],
            );

            return [$escopo['numero_as'] => $model];
        });

        $asRows = [
            [
                'key' => 'DEMO-AS-001',
                'complemento' => '',
                'estimado' => 10000,
                'fechado' => 0,
                'status' => null,
                'liberado' => false,
                'nota' => null,
                'observacoes' => 'DEMO fluxo AS: linha com valor estimado para testar criação de AS pelo Controle AS.',
            ],
            [
                'key' => 'DEMO-AS-002',
                'complemento' => '',
                'estimado' => 15000,
                'fechado' => 14000,
                'status' => AsStatus::CRIADA->value,
                'liberado' => false,
                'nota' => null,
                'observacoes' => 'DEMO fluxo AS: AS criada, aguardando Enviar AS.',
            ],
            [
                'key' => 'DEMO-AS-003',
                'complemento' => '',
                'estimado' => 20000,
                'fechado' => 18000,
                'status' => AsStatus::ENVIADA->value,
                'liberado' => true,
                'nota' => ['numero' => 'DEMO-FISCAL-AS-ANALISE', 'status' => StatusControleNotaFiscalNota::EM_ANALISE->value, 'valor' => 3000],
                'observacoes' => 'DEMO fluxo AS: enviada e liberada para fornecedor, com NF em análise.',
            ],
            [
                'key' => 'DEMO-AS-004',
                'complemento' => 'C1',
                'estimado' => 25000,
                'fechado' => 22000,
                'status' => AsStatus::ENVIADA->value,
                'liberado' => true,
                'nota' => ['numero' => 'DEMO-FISCAL-AS-APROVADA', 'status' => StatusControleNotaFiscalNota::APROVADO->value, 'valor' => 4000],
                'observacoes' => 'DEMO fluxo AS: enviada e liberada para fornecedor, com NF aprovada.',
            ],
        ];

        foreach ($asRows as $index => $row) {
            /** @var AsEscopo|null $asEscopo */
            $asEscopo = $asEscopos->get($row['key']);

            if (! $asEscopo instanceof AsEscopo) {
                continue;
            }

            $item = ControleNotaFiscalItem::query()->updateOrCreate(
                [
                    'controle_nota_fiscal_id' => $controle->id,
                    'as_escopo_id' => $asEscopo->id,
                    'numero_complemento' => $row['complemento'],
                ],
                [
                    'grupo' => $asEscopo->grupo,
                    'numero_as' => $asEscopo->numero_as,
                    'escopo' => $asEscopo->escopo,
                    'escopo_complementar' => filled($row['complemento']) ? 'Complemento demo do fluxo fiscal.' : null,
                    'empresa' => $construtora->nome,
                    'quantidade' => 1,
                    'percentual_total' => 100,
                    'percentual_faturamento_mao_obra' => 60,
                    'percentual_faturamento_material' => 40,
                    'valor_estimado_as' => $row['estimado'],
                    'valor_estimado_as_simulador' => $row['estimado'],
                    'valor_estimado_as_editado_manualmente' => false,
                    'valor_global_a' => $row['fechado'],
                    'total_medicao_a_menos_b' => $row['fechado'],
                    'valor_acumulado_medido' => 0,
                    'saldo' => $row['fechado'],
                    'observacoes' => $row['observacoes'],
                    'liberado_para_fornecedor_at' => $row['liberado'] ? now()->subDays(2) : null,
                    'sort_order' => 700 + $index,
                ],
            );

            if ($row['status'] !== null) {
                $autorizacaoServico = AutorizacaoServico::query()->updateOrCreate(
                    [
                        'controle_nota_fiscal_item_id' => $item->id,
                    ],
                    [
                        'obra_id' => $obra->id,
                        'as_escopo_id' => $asEscopo->id,
                        'construtora_id' => $construtora->id,
                        'status' => $row['status'],
                        'numero_as' => 'AS-'.$row['key'],
                        'numero_complemento' => $row['complemento'],
                        'valor' => $row['fechado'],
                        'desconto_autorizacao_servico' => max((float) $row['estimado'] - (float) $row['fechado'], 0),
                        'valor_estimado' => $row['estimado'],
                        'created_by_id' => $orcamentistaId,
                        'enviado_por_id' => $row['liberado'] ? $orcamentistaId : null,
                        'enviado_em' => $row['liberado'] ? now()->subDays(2) : null,
                        'observacoes' => $row['observacoes'],
                    ],
                );

                if (is_array($row['nota'])) {
                    $this->upsertNotaFiscalDemo(
                        numeroNf: $row['nota']['numero'],
                        autorizacaoServicoId: $autorizacaoServico->id,
                        asaId: null,
                        importadoPorId: $fornecedorUserId,
                        decididoPorId: $row['nota']['status'] === StatusControleNotaFiscalNota::APROVADO->value ? $orcamentistaId : null,
                        empresa: $construtora->nome,
                        status: $row['nota']['status'],
                        valor: (float) $row['nota']['valor'],
                    );

                    if ($row['nota']['status'] === StatusControleNotaFiscalNota::APROVADO->value) {
                        $valorAcumulado = (float) $row['nota']['valor'];

                        $item->forceFill([
                            'valor_acumulado_medido' => $valorAcumulado,
                            'total_medicao_a_menos_b' => max((float) $row['fechado'] - $valorAcumulado, 0),
                            'saldo' => max((float) $row['fechado'] - $valorAcumulado, 0),
                        ])->save();
                    }
                }
            }
        }

        $asaRows = [
            [
                'suffix' => 'ELAB',
                'aditivo_status' => 'elaboracao',
                'asa_status' => null,
                'auxiliar' => false,
                'liberado' => false,
                'nota' => null,
                'valor_bruto' => 8000,
                'valor_total' => 7600,
                'descricao' => 'DEMO ASA - elaboração ainda não enviada ao gestor',
            ],
            [
                'suffix' => 'GESTOR',
                'aditivo_status' => 'em_aprovacao_gestor',
                'asa_status' => AsStatus::SOLICITADO->value,
                'auxiliar' => false,
                'liberado' => false,
                'nota' => null,
                'valor_bruto' => 9000,
                'valor_total' => 8500,
                'descricao' => 'DEMO ASA - aguardando aprovação do gestor',
            ],
            [
                'suffix' => 'ORC',
                'aditivo_status' => 'em_aprovacao_orcamento',
                'asa_status' => AsStatus::EM_APROVACAO_ORCAMENTO->value,
                'auxiliar' => true,
                'liberado' => false,
                'nota' => null,
                'valor_bruto' => 12000,
                'valor_total' => 11000,
                'descricao' => 'DEMO ASA - gestor aprovou, aguardando Aprovar ASA no Controle AS',
            ],
            [
                'suffix' => 'APROV',
                'aditivo_status' => 'aprovado',
                'asa_status' => AsStatus::APROVADO->value,
                'auxiliar' => true,
                'liberado' => false,
                'nota' => null,
                'valor_bruto' => 15000,
                'valor_total' => 14000,
                'descricao' => 'DEMO ASA - aprovada pelo orçamento, aguardando Enviar ASA',
            ],
            [
                'suffix' => 'ENV',
                'aditivo_status' => 'aprovado',
                'asa_status' => AsStatus::APROVADO->value,
                'auxiliar' => true,
                'liberado' => true,
                'nota' => ['numero' => 'DEMO-FISCAL-ASA-ANALISE', 'status' => StatusControleNotaFiscalNota::EM_ANALISE->value, 'valor' => 2500],
                'valor_bruto' => 18000,
                'valor_total' => 16500,
                'descricao' => 'DEMO ASA - enviada e liberada para importação de NF',
            ],
        ];

        foreach ($asaRows as $index => $row) {
            $aditivoId = $this->upsertElaboracaoAditivoFluxoDemo(
                suffix: $row['suffix'],
                obraId: $obra->id,
                userId: $fornecedorUserId,
                construtoraId: $construtora->id,
                gestorId: $gestorId,
                orcamentistaId: $orcamentistaId,
                status: $row['aditivo_status'],
                descricao: $row['descricao'],
                valorTotal: (float) $row['valor_total'],
            );

            if ($row['asa_status'] === null || $aditivoId === null) {
                continue;
            }

            $numeroAsa = 'DEMO-ASA-'.$row['suffix'];
            $codigoAsa = 'ASA-DEMO-'.$row['suffix'];
            $auxiliar = null;

            if ($row['auxiliar']) {
                $valorAcumulado = is_array($row['nota']) && $row['nota']['status'] === StatusControleNotaFiscalNota::APROVADO->value
                    ? (float) $row['nota']['valor']
                    : 0.0;
                $saldo = max((float) $row['valor_total'] - $valorAcumulado, 0);

                $auxiliar = ControleNotaFiscalAuxiliar::query()->updateOrCreate(
                    [
                        'controle_nota_fiscal_id' => $controle->id,
                        'numero_as' => $codigoAsa,
                        'numero_complemento' => '',
                    ],
                    [
                        'grupo' => 'Projeto',
                        'escopo' => $row['descricao'],
                        'empresa' => $construtora->nome,
                        'percentual_total' => 100,
                        'percentual_faturamento_mao_obra' => 60,
                        'percentual_faturamento_material' => 40,
                        'valor_global_a' => $row['valor_total'],
                        'total_medicao_a_menos_b' => $saldo,
                        'valor_acumulado_medido' => $valorAcumulado,
                        'saldo' => $saldo,
                        'observacoes' => $row['descricao'],
                        'liberado_para_fornecedor_at' => $row['liberado'] ? now()->subDay() : null,
                        'sort_order' => 800 + $index,
                    ],
                );
            }

            $asaId = $this->upsertAsaFluxoDemo(
                numeroAsa: $numeroAsa,
                codigoAsa: $codigoAsa,
                projetoId: (int) $obra->projeto_id,
                aditivoId: $aditivoId,
                gestorId: $gestorId,
                status: $row['asa_status'],
                descricao: $row['descricao'],
                valorBruto: (float) $row['valor_bruto'],
                valorTotal: (float) $row['valor_total'],
                auxiliarId: $auxiliar?->id,
            );

            if ($auxiliar instanceof ControleNotaFiscalAuxiliar && is_array($row['nota'])) {
                $this->upsertNotaFiscalDemo(
                    numeroNf: $row['nota']['numero'],
                    autorizacaoServicoId: null,
                    asaId: $asaId,
                    importadoPorId: $fornecedorUserId,
                    decididoPorId: $row['nota']['status'] === StatusControleNotaFiscalNota::APROVADO->value ? $orcamentistaId : null,
                    empresa: $construtora->nome,
                    status: $row['nota']['status'],
                    valor: (float) $row['nota']['valor'],
                );
            }
        }
    }

    private function upsertElaboracaoAditivoFluxoDemo(
        string $suffix,
        int $obraId,
        int $userId,
        int $construtoraId,
        int $gestorId,
        int $orcamentistaId,
        string $status,
        string $descricao,
        float $valorTotal,
    ): ?int {
        $refServico = 'DEMO-FISCAL-ADIT-'.$suffix;
        $aprovadoGestor = in_array($status, ['em_aprovacao_orcamento', 'aprovado'], true);
        $aprovadoOrcamento = $status === 'aprovado';

        DB::table('elaboracao_aditivos')->updateOrInsert(
            ['ref_servico' => $refServico],
            [
                'user_id' => $userId,
                'construtora_id' => $construtoraId,
                'gestor_id' => $gestorId,
                'obra_id' => $obraId,
                'data' => now()->toDateString(),
                'justificativa' => $descricao,
                'anexos' => json_encode([], JSON_THROW_ON_ERROR),
                'foto_antes' => json_encode([], JSON_THROW_ON_ERROR),
                'foto_depois' => json_encode([], JSON_THROW_ON_ERROR),
                'projeto_orcado' => json_encode([], JSON_THROW_ON_ERROR),
                'projeto_revisado' => json_encode([], JSON_THROW_ON_ERROR),
                'escopo_contratado' => json_encode([], JSON_THROW_ON_ERROR),
                'escopo_real' => json_encode([], JSON_THROW_ON_ERROR),
                'status_fluxo' => $status,
                'aprovado_gestor_por_id' => $aprovadoGestor ? $gestorId : null,
                'aprovado_gestor_em' => $aprovadoGestor ? now()->subDays(2) : null,
                'aprovado_orcamento_por_id' => $aprovadoOrcamento ? $orcamentistaId : null,
                'aprovado_orcamento_em' => $aprovadoOrcamento ? now()->subDay() : null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $aditivoId = DB::table('elaboracao_aditivos')->where('ref_servico', $refServico)->value('id');

        if ($aditivoId === null || ! $this->hasTable('elaboracao_aditivo_items')) {
            return $aditivoId !== null ? (int) $aditivoId : null;
        }

        DB::table('elaboracao_aditivo_items')->updateOrInsert(
            [
                'elaboracao_aditivo_id' => $aditivoId,
                'item' => '1',
            ],
            [
                'descricao_servico' => $descricao,
                'quantidade' => 1,
                'unidade' => 'vb',
                'valor_material_unitario' => round($valorTotal * 0.4, 2),
                'valor_mao_obra_unitario' => round($valorTotal * 0.6, 2),
                'total_unitario' => $valorTotal,
                'valor_total_geral' => $valorTotal,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) $aditivoId;
    }

    private function upsertAsaFluxoDemo(
        string $numeroAsa,
        string $codigoAsa,
        int $projetoId,
        int $aditivoId,
        int $gestorId,
        string $status,
        string $descricao,
        float $valorBruto,
        float $valorTotal,
        ?int $auxiliarId,
    ): int {
        $payload = [
            'numero_asa_hash' => hash('sha256', $numeroAsa),
            'projeto_id' => $projetoId,
            'elaboracao_aditivo_id' => $aditivoId,
            'contrato' => 'Projeto',
            'controle_nota_fiscal_destino' => 'adicional',
            'status' => $status,
            'codigo_as_emitida' => $codigoAsa,
            'data_solicitacao' => now()->subDays(3)->toDateString(),
            'data_aprovacao' => $status === AsStatus::APROVADO->value ? now()->subDay()->toDateString() : null,
            'objeto' => $descricao,
            'descricao' => $descricao,
            'justificativa' => $descricao,
            'altera_prazo' => 'nao',
            'dias_prazo' => 0,
            'valor_bruto' => $valorBruto,
            'desconto' => max($valorBruto - $valorTotal, 0),
            'valor_total' => $valorTotal,
            'evidencias' => json_encode([], JSON_THROW_ON_ERROR),
            'observacoes' => $descricao,
            'gestor_id' => $gestorId,
            'solicitante' => 'Fornecedor Demo Local',
            'foto_antes' => json_encode([], JSON_THROW_ON_ERROR),
            'foto_depois' => json_encode([], JSON_THROW_ON_ERROR),
            'projeto_orcado' => json_encode([], JSON_THROW_ON_ERROR),
            'projeto_revisado' => json_encode([], JSON_THROW_ON_ERROR),
            'escopo_contratado' => json_encode([], JSON_THROW_ON_ERROR),
            'escopo_real' => json_encode([], JSON_THROW_ON_ERROR),
            'controle_nota_fiscal_auxiliar_id' => $auxiliarId,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('autorizacao_servico_adicionais')->updateOrInsert(
            ['numero_asa' => $numeroAsa],
            $payload,
        );

        return (int) DB::table('autorizacao_servico_adicionais')->where('numero_asa', $numeroAsa)->value('id');
    }

    private function upsertNotaFiscalDemo(
        string $numeroNf,
        ?int $autorizacaoServicoId,
        ?int $asaId,
        int $importadoPorId,
        ?int $decididoPorId,
        string $empresa,
        string $status,
        float $valor,
    ): void {
        ControleNotaFiscalNota::query()->updateOrCreate(
            ['numero_nf' => $numeroNf],
            [
                'autorizacao_servico_id' => $autorizacaoServicoId,
                'autorizacao_servico_adicional_id' => $asaId,
                'importado_por_id' => $importadoPorId,
                'decidido_por_id' => $decididoPorId,
                'tipo_medicao' => ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA,
                'empresa' => $empresa,
                'cnpj_fornecedor' => '12345678000199',
                'cnpj_faturamento' => '12345678000199',
                'numero_nf' => $numeroNf,
                'instrucoes_pagamento' => 'DEMO fluxo fiscal local.',
                'valor_acumulado_medido_nf' => $valor,
                'emissao' => now()->subDays(4)->toDateString(),
                'envio' => now()->subDays(3)->toDateString(),
                'status' => $status,
                'decidido_em' => $decididoPorId !== null ? now()->subDay() : null,
                'arquivo_path' => null,
                'boleto_path' => null,
                'observacoes' => 'DEMO fluxo fiscal local.',
                'sort_order' => 10,
            ],
        );
    }

    private function seedRelatorioVisitaTecnicaDemo(): void
    {
        if (! $this->hasTable('relatorio_visita_tecnicas') || ! $this->hasTable('projetos')) {
            return;
        }

        if (DB::table('relatorio_visita_tecnicas')->where('numero_relatorio_vt', 'DEMO-VT-0001')->exists()) {
            return;
        }

        $projeto = DB::table('projetos')->where('sigla', 'DEMO-PJT-001')->first(['id', 'nome', 'sigla', 'endereco', 'marca']);

        if ($projeto === null) {
            return;
        }

        $marcaId = $this->hasTable('marcas')
            ? DB::table('marcas')->where('nome', (string) ($projeto->marca ?? 'Smart Fit'))->value('id')
            : null;

        DB::table('relatorio_visita_tecnicas')->insert([
            'projeto_id' => $projeto->id,
            'marca_id' => $marcaId,
            'numero_relatorio_vt' => 'DEMO-VT-0001',
            'iniciado_em' => now()->subDays(3),
            'agendado_em' => now()->subDays(2),
            'concluido_em' => now()->subDay(),
            'autor' => 'Equipe DEMO Engenharia',
            'unidade_relatorio' => (string) ($projeto->sigla ?? 'DEMO-PJT-001'),
            'unidade' => (string) ($projeto->nome ?? 'Unidade DEMO'),
            'endereco' => (string) ($projeto->endereco ?? 'Endereço DEMO'),
            'condicoes_imovel' => 'Condições gerais adequadas para início de mobilização.',
            'pavimento' => json_encode(['Térreo'], JSON_THROW_ON_ERROR),
            'empreendimento' => 'DEMO Empreendimento',
            'locacao' => 'Multiusuário',
            'contato_responsavel' => 'Responsável DEMO',
            'responsavel_tecnico' => 'Engenheiro DEMO',
            'prazo_de_obras' => '120 dias',
            'status' => 'rascunho',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedRelatorioFotograficoDemo(): void
    {
        if (! $this->hasTable('relatorio_fotograficos') || ! $this->hasTable('projetos')) {
            return;
        }

        if (DB::table('relatorio_fotograficos')->where('sigla', 'DEMO-RF-001')->exists()) {
            return;
        }

        $projeto = DB::table('projetos')->where('sigla', 'DEMO-PJT-001')->first(['id', 'sigla', 'endereco']);

        if ($projeto === null) {
            return;
        }

        $autorId = DB::table('users')->where('email', 'coordenador.obra@example.test')->value('id')
            ?? DB::table('users')->orderBy('id')->value('id');
        $gestorId = DB::table('users')->where('email', 'gestor.obra@example.test')->value('id')
            ?? $autorId;

        DB::table('relatorio_fotograficos')->insert([
            'status_relatorio' => 'rascunho',
            'projeto_id' => $projeto->id,
            'gestor_id' => $gestorId,
            'autor_id' => $autorId,
            'status' => 'rascunho',
            'data_posse' => now()->subDays(20)->toDateString(),
            'status_termo_de_posse' => 'pendente',
            'entregas_contratuais' => json_encode([], JSON_THROW_ON_ERROR),
            'fotos' => json_encode([], JSON_THROW_ON_ERROR),
            'sigla' => 'DEMO-RF-001',
            'tipo_unidade' => 'Loja',
            'endereco' => (string) ($projeto->endereco ?? 'Endereço DEMO'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedImportacoesDemo(): void
    {
        if (! $this->hasTable('importacao_logs')) {
            return;
        }

        $userId = DB::table('users')->where('email', 'super.admin@example.test')->value('id')
            ?? DB::table('users')->orderBy('id')->value('id');

        if ($userId === null) {
            return;
        }

        $logId = DB::table('importacao_logs')->where('arquivo_original', 'DEMO-importacao-obras.xlsx')->value('id');

        if ($logId === null) {
            $logId = DB::table('importacao_logs')->insertGetId([
                'arquivo_original' => 'DEMO-importacao-obras.xlsx',
                'arquivo_path' => 'importacoes/demo/DEMO-importacao-obras.xlsx',
                'modulo' => 'obras',
                'status' => 'concluido',
                'total_linhas' => 2,
                'linhas_criadas' => 1,
                'linhas_atualizadas' => 1,
                'linhas_erro' => 0,
                'erros' => json_encode([], JSON_THROW_ON_ERROR),
                'mapeamento_usado' => json_encode([
                    'codigo' => 'codigo',
                    'unidade' => 'unidade',
                    'status' => 'status',
                ], JSON_THROW_ON_ERROR),
                'user_id' => $userId,
                'iniciado_em' => now()->subMinutes(5),
                'finalizado_em' => now()->subMinutes(1),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! $this->hasTable('importacao_staging')) {
            return;
        }

        $obraId = $this->hasTable('obras')
            ? DB::table('obras')->orderBy('id')->value('id')
            : null;

        $linhas = [
            [
                'linha_planilha' => 2,
                'codigo' => 'DEMO-OBRA-001',
                'acao' => 'criar',
                'dados' => ['codigo' => 'DEMO-OBRA-001', 'unidade' => 'Unidade DEMO 01', 'status' => 'Inaugurada'],
            ],
            [
                'linha_planilha' => 3,
                'codigo' => 'DEMO-OBRA-002',
                'acao' => 'atualizar',
                'dados' => ['codigo' => 'DEMO-OBRA-002', 'unidade' => 'Unidade DEMO 02', 'status' => 'Em Obra'],
            ],
        ];

        foreach ($linhas as $linha) {
            if (DB::table('importacao_staging')->where('importacao_log_id', $logId)->where('linha_planilha', $linha['linha_planilha'])->exists()) {
                continue;
            }

            DB::table('importacao_staging')->insert([
                'importacao_log_id' => $logId,
                'linha_planilha' => $linha['linha_planilha'],
                'codigo' => $linha['codigo'],
                'acao' => $linha['acao'],
                'obra_existente_id' => $obraId,
                'dados' => json_encode($linha['dados'], JSON_THROW_ON_ERROR),
                'conflitos' => json_encode([], JSON_THROW_ON_ERROR),
                'erro' => json_encode([], JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedHistoricoProjetosDemo(): void
    {
        if (! $this->hasTable('historico_projetos') || ! $this->hasTable('projetos')) {
            return;
        }

        $projetoId = DB::table('projetos')->where('sigla', 'DEMO-PJT-001')->value('id')
            ?? DB::table('projetos')->orderBy('id')->value('id');
        $usuarioId = DB::table('users')->where('email', 'super.admin@example.test')->value('id')
            ?? DB::table('users')->orderBy('id')->value('id');

        if ($projetoId === null || $usuarioId === null) {
            return;
        }

        $eventos = [
            ['acao' => 'DEMO-PROJETO-CRIADO', 'status' => 'Em processo', 'etapa' => 'Em Projeto', 'status_antigo' => null, 'status_novo' => 'Em processo'],
            ['acao' => 'DEMO-STATUS-ATUALIZADO', 'status' => 'Em obra', 'etapa' => 'Em Obra', 'status_antigo' => 'Em processo', 'status_novo' => 'Em obra'],
            ['acao' => 'DEMO-INAUGURACAO-PREVISTA', 'status' => 'Inauguração planejada', 'etapa' => 'Em Obra', 'status_antigo' => 'Em obra', 'status_novo' => 'Inauguração planejada'],
        ];

        foreach ($eventos as $evento) {
            if (DB::table('historico_projetos')->where('projeto_id', $projetoId)->where('acao', $evento['acao'])->exists()) {
                continue;
            }

            DB::table('historico_projetos')->insert([
                'projeto_id' => $projetoId,
                'usuario_id' => $usuarioId,
                'setor' => 'Obras',
                'status' => $evento['status'],
                'etapa' => $evento['etapa'],
                'status_antigo' => $evento['status_antigo'],
                'status_novo' => $evento['status_novo'],
                'acao' => $evento['acao'],
                'fase' => 'DEMO',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function hasTable(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }

    private function hasColumn(string $table, string $column): bool
    {
        if (! $this->hasTable($table)) {
            return false;
        }

        return DB::getSchemaBuilder()->hasColumn($table, $column);
    }

    private function syncLocalProfilePermissions(): void
    {
        if (! $this->hasTable('roles') || ! $this->hasTable('permissions')) {
            return;
        }

        $profilePermissions = [
            'Gestor' => [
                'Create:AutorizacaoServico',
                'Create:ControleNotaFiscal',
                'Update:Asa',
                'Update:AutorizacaoServico',
                'Update:ControleNotaFiscal',
                'Update:ControleNotaFiscalNota',
                'View:AprovacaoNotasFiscaisPage',
                'View:Asa',
                'View:AutorizacaoServico',
                'View:ControleNotaFiscal',
                'View:ControleNotaFiscalNota',
                'View:EntregaContratual',
                'View:Obras',
                'ViewAny:Asa',
                'ViewAny:AutorizacaoServico',
                'ViewAny:ControleNotaFiscal',
                'ViewAny:ControleNotaFiscalNota',
                'ViewAny:Obras',
            ],
            'coordenador_orcamento' => [
                'Create:AutorizacaoServico',
                'Create:CapexSimulacao',
                'Create:ControlePedido',
                'Update:Asa',
                'Update:AutorizacaoServico',
                'Update:CapexSimulacao',
                'Update:ControlePedido',
                'View:Asa',
                'View:AutorizacaoServico',
                'View:CapexSimulacao',
                'View:ControlePedido',
                'View:DashboardOI',
                'View:DashboardPedidos',
                'View:SimuladorCapex',
                'ViewAny:Asa',
                'ViewAny:AutorizacaoServico',
                'ViewAny:CapexSimulacao',
                'ViewAny:ControlePedido',
            ],
            'Fornecedor' => [
                'Create:Construtora',
                'Create:ControleNotaFiscalNota',
                'Create:ElaboracaoAditivo',
                'Create:ImportacaoNotaFiscal',
                'Create:ObraDocumento',
                'Create:ObraRecebimento',
                'Update:Construtora',
                'Update:ControleNotaFiscalNota',
                'Update:ElaboracaoAditivo',
                'Update:ImportacaoNotaFiscal',
                'Update:ObraDocumento',
                'Update:ObraRecebimento',
                'View:Construtora',
                'View:ConstrutoraControlesNotaFiscalPage',
                'View:ControleNotaFiscalNota',
                'View:ElaboracaoAditivo',
                'View:ImportacaoNotaFiscal',
                'View:ObraDocumento',
                'View:ObraRecebimento',
                'ViewAny:Construtora',
                'ViewAny:ControleNotaFiscalNota',
                'ViewAny:ElaboracaoAditivo',
                'ViewAny:ImportacaoNotaFiscal',
                'ViewAny:ObraDocumento',
                'ViewAny:ObraRecebimento',
            ],
        ];

        foreach (array_merge(...array_values($profilePermissions)) as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        foreach ($profilePermissions as $roleName => $permissions) {
            $role = Role::findByName($roleName, 'web');
            $mergedPermissions = $role->permissions()
                ->pluck('name')
                ->merge($permissions)
                ->reject(fn (string $permissionName): bool => $permissionName === 'Delete:ControleNotaFiscal')
                ->unique()
                ->values()
                ->all();

            $role->syncPermissions($mergedPermissions);
        }

        $superAdmin = Role::findByName('super_admin', 'web');
        $superAdmin->syncPermissions(
            Permission::query()
                ->where('guard_name', 'web')
                ->pluck('name')
                ->all(),
        );
    }

    private function syncControleNotaFiscalEscoposDemo(): void
    {
        if (! $this->hasTable('controle_nota_fiscal_items') || ! $this->hasTable('construtoras')) {
            return;
        }

        $construtora = Construtora::query()
            ->where('nome', 'Fornecedor Demo Local')
            ->first();

        if (! $construtora instanceof Construtora) {
            return;
        }

        $itemIds = DB::table('controle_nota_fiscal_items')
            ->whereNotNull('as_escopo_id')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        foreach ($itemIds as $index => $itemId) {
            $item = ControleNotaFiscalItem::query()
                ->with(['controleNotaFiscal.obra', 'asEscopo'])
                ->find($itemId);

            if (! $item instanceof ControleNotaFiscalItem) {
                continue;
            }

            $payload = [
                'empresa' => $construtora->nome,
                'quantidade' => 1,
                'percentual_total' => 100,
                'percentual_faturamento_mao_obra' => 60,
                'percentual_faturamento_material' => 40,
                'valor_estimado_as' => 10000 + ($index * 1250),
                'valor_global_a' => 9500 + ($index * 1100),
                'total_medicao_a_menos_b' => 9500 + ($index * 1100),
                'valor_acumulado_medido' => 0,
                'saldo' => 9500 + ($index * 1100),
                'observacoes' => $index === 0
                    ? 'Linha demo pronta para testar Gerar AS e Enviar AS pelo Filament.'
                    : 'Linha demo para visualização do controle de AS.',
            ];

            if ($index === 1) {
                $payload['numero_complemento'] = 'C1';
                $payload['escopo_complementar'] = 'Escopo complementar demo para validação visual da importação.';
            }

            $item->fill($payload);
            $item->save();

            if (($index > 0 && $index < 3) && filled($item->as_escopo_id) && $item->controleNotaFiscal?->obra_id) {
                $numeroAs = app(AutorizacaoServicoService::class)->gerarNumeroAsEstruturado(
                    $item->controleNotaFiscal->obra,
                    $item->asEscopo,
                    $construtora,
                );

                $autorizacaoServico = AutorizacaoServico::query()->updateOrCreate(
                    [
                        'obra_id' => $item->controleNotaFiscal->obra_id,
                        'as_escopo_id' => $item->as_escopo_id,
                        'construtora_id' => $construtora->id,
                        'numero_complemento' => $index === 1 ? 'C1' : '',
                    ],
                    [
                        'status' => AsStatus::CRIADA->value,
                        'numero_as' => $numeroAs,
                        'controle_nota_fiscal_item_id' => $item->id,
                        'valor' => (float) $payload['valor_global_a'],
                        'desconto_autorizacao_servico' => $index === 1 ? 250 : 0,
                        'valor_estimado' => (float) $payload['valor_estimado_as'],
                        'anexo_autorizacao_servico' => null,
                        'observacoes' => $index === 0
                            ? 'AS demo pronta para envio local.'
                            : 'AS demo gerada automaticamente para escopo liberado no LocalDemoSeeder.',
                    ],
                );

                $this->prepararAutorizacaoServicoDemo($autorizacaoServico->refresh(), $index);
            }
        }

    }

    private function prepararAutorizacaoServicoDemo(AutorizacaoServico $autorizacaoServico, int $index): void
    {
        $service = app(AutorizacaoServicoFluxoService::class);
        $subtotal = round((float) $autorizacaoServico->valor, 2);
        $desconto = round((float) ($autorizacaoServico->desconto_autorizacao_servico ?? 0), 2);
        $total = max($subtotal, 0);

        $service->gerar(
            $autorizacaoServico,
            parcelamento: [[
                'parcela' => 'Parcela 01',
                'percentual' => 100,
                'valor' => $total,
                'observacao' => '>> FATURAR SOMENTE COM AUTORIZAÇÃO DO(A) GESTOR(A) DPC',
            ]],
            datas: [
                'data_inicio_servico' => $autorizacaoServico->obra?->inicio?->format('Y-m-d'),
                'data_termino_servico' => $autorizacaoServico->obra?->fim?->format('Y-m-d'),
                'data_entrega_material' => $autorizacaoServico->itens()->oldest('id')->first()?->data_entrega?->format('Y-m-d'),
                'desconto_autorizacao_servico' => $desconto,
            ],
        );

        if ($index === 2) {
            $service->abrirOrcamento($autorizacaoServico->refresh());
        }
    }

    private function seedObrasDemo(): void
    {
        $projectIds = DB::table('projetos')->orderBy('id')->limit(3)->pluck('id');

        foreach ($projectIds as $index => $projectId) {
            DB::table('obras')->updateOrInsert(
                ['projeto_id' => $projectId],
                [
                    'codigo' => sprintf('DEMO-OBRA-%03d', $index + 1),
                    'unidade' => sprintf('Unidade DEMO %02d', $index + 1),
                    'endereco' => sprintf('Avenida Demo, %d - São Paulo/SP', 100 + $index),
                    'engenharia' => 'Gestor Obra',
                    'inicio' => now()->subDays(5)->toDateString(),
                    'fim' => now()->addDays(45)->toDateString(),
                    'status' => 'Inaugurada',
                    'comentarios' => 'Obra fake local para popular o resource.',
                    'cronograma_implantacao' => 'Aprovado',
                    'dias_para_inauguracao' => 15 - ($index * 5),
                    'percentual_obra' => 100,
                    'energia' => 'Ligação concluída',
                    'agua' => 'Ligação concluída',
                    'gas' => 'Não aplicável',
                    'email_solicitacao_cl' => 'Enviado',
                    'envio_qrcod' => 'Concluído',
                    'checklist_manutencao' => 'Pendente',
                    'inicio_prev_pendencias' => now()->subDays(10)->toDateString(),
                    'termino_prev_pendencias' => now()->addDays(10)->toDateString(),
                    'comentarios_adicionais' => 'Registro fake local gerado pelo LocalDemoSeeder.',
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    private function seedPendenciasDemo(): void
    {
        $obraId = DB::table('obras')->orderBy('id')->value('id');
        $gestorId = DB::table('users')->orderBy('id')->value('id');
        $construtoraId = DB::table('construtoras')->orderBy('id')->value('id');
        $disciplinaId = DB::table('po_disciplinas_config')->orderBy('id')->value('id');

        if ($obraId === null || $gestorId === null || $disciplinaId === null) {
            return;
        }

        $codigo = 'PO-DEMO-0001';

        $pendenciaId = DB::table('po_pendencias')->where('codigo', $codigo)->value('id');

        if ($pendenciaId === null) {
            $pendenciaId = DB::table('po_pendencias')->insertGetId([
                'codigo' => $codigo,
                'obras_id' => $obraId,
                'construtora_id' => $construtoraId,
                'lider_obra_id' => $gestorId,
                'gestor_id' => $gestorId,
                'disciplina_config_id' => $disciplinaId,
                'ticket' => 'TICKET-DEMO-001',
                'descricao' => 'Pendência fake de pós-obra para ambiente local.',
                'observacoes' => 'Gerada automaticamente pelo LocalDemoSeeder.',
                'urgencia' => 'P2',
                'status' => 'em_execucao',
                'data_inicio' => now()->subDays(3)->toDateString(),
                'data_termino' => now()->addDays(4)->toDateString(),
                'data_conclusao' => null,
                'impacto_operacao' => true,
                'local_especifico' => 'Recepção',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $historicoExiste = DB::table('po_atualizacoes_status')
            ->where('pendencia_id', $pendenciaId)
            ->exists();

        if ($historicoExiste) {
            return;
        }

        DB::table('po_atualizacoes_status')->insert([
            'pendencia_id' => $pendenciaId,
            'status_anterior' => 'registrada',
            'status_novo' => 'em_execucao',
            'comentario' => 'Histórico fake inicial de pós-obra.',
            'atualizado_por' => 'LocalDemoSeeder',
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);
    }

    private function seedDadosDemo(): void
    {
        if (DB::table('dados')->where('nova_sigla', 'DEMO-DADO-001')->exists()) {
            return;
        }

        DB::table('dados')->insert([
            'nova_sigla' => 'DEMO-DADO-001',
            'unidade' => 'Smart Fit Paulista',
            'marca' => 'Smart Fit',
            'bloco_tipo' => 'BL-001',
            'categoria' => 'Portas',
            'descricao' => 'Item fake local para popular o resource de dados.',
            'quantidade' => '5',
            'un' => 'UN',
            'pavimento' => 'Térreo',
            'status' => 'Nova Construção',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedAcompanhamentoDemo(): void
    {
        if (DB::table('acompanhamentos')->where('sigla', 'DEMO-ACOMP-001')->exists()) {
            return;
        }

        DB::table('acompanhamentos')->insert([
            'sigla' => 'DEMO-ACOMP-001',
            'nova_sigla' => 'DEMO-ACOMP-001',
            'nome_mkt' => 'Acompanhamento Demo Local',
            'tipo' => 'Própria',
            'marca' => 'Smart Fit',
            'escopo' => 'Nova Unidade',
            'pipeline' => 'EXPANSÃO',
            'status' => 'EM OBRA',
            'inicio_obra' => now()->subDays(30)->toDateString(),
            'entrega_obra' => now()->addDays(60)->toDateString(),
            'implantacao' => now()->addDays(75)->toDateString(),
            'inauguracao' => now()->addDays(90)->toDateString(),
            'ano_inauguracao' => (int) now()->addDays(90)->format('Y'),
            'endereco' => 'Av. Paulista, 1000',
            'cep' => '01310-100',
            'bairro' => 'Bela Vista',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'regiao' => 'Sudeste',
            'pais' => 'Brasil',
            'razao_social' => 'Empresa Demo Local LTDA',
            'cnpj' => '98765432000188',
            'empreendimento_adm' => 'Administração Demo',
            'tipo_loja' => 'MALL / SHOPPING',
            'perfil_loja' => 'Padrão',
            'tipo_obra' => 'Nova Construção',
            'situacao_contratual' => 'Assinado',
            'area_contrato' => 1200,
            'area_util' => 1100,
            'area_producao' => 950,
            'estacionamento' => '40 vagas compartilhadas',
            'obs' => 'Registro fake local para popular o resource de acompanhamento.',
            'inicio_projeto' => now()->subDays(60)->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedProjetosDemo(): void
    {
        $userId = DB::table('users')->where('email', 'gestor.obra@example.test')->value('id')
            ?? DB::table('users')->orderBy('id')->value('id');
        $etapaId = DB::table('etapas')->where('nome', 'Em Projeto')->value('id');
        $paisId = DB::table('pais')->where('nome', 'Brasil')->value('id');
        $estadoId = DB::table('estados')->where('nome', 'São Paulo')->value('id');
        $cidadeId = DB::table('cidades')->where('nome', 'São Paulo')->value('id');

        if ($userId === null || $etapaId === null || $paisId === null || $estadoId === null || $cidadeId === null) {
            return;
        }

        $projetos = [
            [
                'sigla' => 'DEMO-PJT-001',
                'sigla_antiga' => 'SF-PAULISTA',
                'nome' => 'Projeto Demo Paulista',
                'cnpj' => '12.345.678/0001-95',
                'cnpj_provisorio' => null,
                'status_cnpj' => 'definitivo',
            ],
            [
                'sigla' => 'DEMO-PJT-002',
                'sigla_antiga' => 'SF-LAPA',
                'nome' => 'Projeto Demo Lapa',
                'cnpj' => '98.XYZ.765/01AB-46',
                'cnpj_provisorio' => null,
                'status_cnpj' => 'definitivo',
            ],
            [
                'sigla' => 'DEMO-PJT-003',
                'sigla_antiga' => 'SF-CAMPINAS',
                'nome' => 'Projeto Demo Campinas',
                'cnpj' => null,
                'cnpj_provisorio' => '11.222.333/0001-81',
                'status_cnpj' => 'provisorio',
            ],
            [
                'sigla' => 'DEMO-PJT-004',
                'sigla_antiga' => 'SF-SANTOS',
                'nome' => 'Projeto Demo Santos',
                'cnpj' => '66.777.888/0001-81',
                'cnpj_provisorio' => '55.ABC.666/01DE-42',
                'status_cnpj' => 'provisorio',
            ],
            [
                'sigla' => 'DEMO-PJT-005',
                'sigla_antiga' => 'BR-GUARULHOS',
                'nome' => 'Projeto Demo Guarulhos',
                'cnpj' => '88.999.000/0001-98',
                'cnpj_provisorio' => null,
                'status_cnpj' => 'definitivo',
            ],
        ];

        foreach ($projetos as $index => $projeto) {
            DB::table('projetos')->updateOrInsert(
                ['sigla' => $projeto['sigla']],
                [
                    'nome' => $projeto['nome'],
                    'nova_sigla' => $projeto['sigla'],
                    'sigla_antiga' => $projeto['sigla_antiga'],
                    'cnpj' => $projeto['cnpj'],
                    'cnpj_provisorio' => $projeto['cnpj_provisorio'],
                    'status_cnpj' => $projeto['status_cnpj'],
                    'user_id' => $userId,
                    'etapa_id' => $etapaId,
                    'cidade_id' => $cidadeId,
                    'estado_id' => $estadoId,
                    'pais_id' => $paisId,
                    'status' => 'Em processo',
                    'pipeline' => 'EXPANSÃO',
                    'marca' => $index === 4 ? 'Bio Ritmo' : 'Smart Fit',
                    'codigo' => $projeto['sigla'],
                    'data_posse' => now()->addDays(30 + $index)->toDateString(),
                    'endereco' => 'Av. Paulista, '.(1000 + $index),
                    'tipo_imovel' => 'padrao',
                    'projeto_croqui' => false,
                    'locacao' => 'Multiusuário',
                    'contato_corretor' => 'Corretor Demo',
                    'status_comite' => '05 - MINUTA',
                    'status_imovel' => 'OBRA PP',
                    'status_contrato' => 'MINUTA',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function seedTaskDemoData(): void
    {
        $categoryId = $this->updateOrInsertAndGetId('task_categories', ['name' => 'Acompanhamento Geral']);

        $marcaId = DB::table('marcas')->where('nome', 'Smart Fit')->value('id');
        $createdBy = DB::table('users')->orderBy('id')->value('id');
        $assignedTo = DB::table('users')->orderBy('id')->value('id');
        $setorId = DB::table('setores')->where('setor', 'Obras')->value('id');
        $projetoId = DB::table('projetos')->orderBy('id')->value('id');

        if ($marcaId === null || $createdBy === null || $assignedTo === null) {
            return;
        }

        $taskExists = DB::table('tasks')->where('title', 'Revisar status inicial da obra demo')->exists();

        if ($taskExists) {
            return;
        }

        DB::table('tasks')->insert([
            'title' => 'Revisar status inicial da obra demo',
            'description' => 'Tarefa fake para popular o resource de tarefas no ambiente local.',
            'task_category_id' => $categoryId,
            'sigla' => 'DEMO-TASK-001',
            'marca_id' => $marcaId,
            'created_by' => $createdBy,
            'assigned_to' => $assignedTo,
            'setor_id' => $setorId,
            'prazo' => 5,
            'dias_corridos' => false,
            'inicio' => now()->toDateString(),
            'termino_programado' => now()->addDays(5)->toDateString(),
            'data_entrega' => null,
            'status' => 'pendente',
            'projeto_id' => $projetoId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedListaEmails(): void
    {
        if (DB::table('lista_emails')->where('nome', 'Lista Demo Local')->exists()) {
            return;
        }

        DB::table('lista_emails')->insert([
            'nome' => 'Lista Demo Local',
            'descricao' => 'Lista de e-mails fake para o ambiente local.',
            'emails' => json_encode(['admin@example.com', 'gestor@example.com'], JSON_THROW_ON_ERROR),
            'ativo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedRegiaoInteresses(): void
    {
        $paisId = DB::table('pais')->where('nome', 'Brasil')->value('id');
        $estadoId = DB::table('estados')->where('nome', 'São Paulo')->value('id');
        $cidadeId = DB::table('cidades')->where('nome', 'São Paulo')->value('id');

        if ($paisId === null || $estadoId === null || $cidadeId === null) {
            return;
        }

        if (DB::table('regiao_interesses')->where('nome', 'Região Demo Centro')->exists()) {
            return;
        }

        DB::table('regiao_interesses')->insert([
            'pais_id' => $paisId,
            'estado_id' => $estadoId,
            'cidade_id' => $cidadeId,
            'nome' => 'Região Demo Centro',
            'endereco' => 'Av. Paulista, 1000',
            'bairro' => 'Bela Vista',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedMatterports(): void
    {
        if (DB::table('matterports')->where('codigo', 'MTP-DEMO-001')->exists()) {
            return;
        }

        DB::table('matterports')->insert([
            'codigo' => 'MTP-DEMO-001',
            'nome' => 'Matterport Demo Local',
            'pais' => 'Brasil',
            'estado' => 'São Paulo',
            'cidade' => 'São Paulo',
            'endereco' => 'Av. Paulista, 1000',
            'link_matterport1' => 'https://example.com/matterport/demo-1',
            'link_matterport2' => null,
            'link_matterport3' => null,
            'link_drone' => 'https://example.com/drone/demo-1',
            'imagem' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedGestaoObras(): void
    {
        if (DB::table('gestao_obras')->where('codigo', 'GO-DEMO-001')->exists()) {
            return;
        }

        DB::table('gestao_obras')->insert([
            'codigo' => 'GO-DEMO-001',
            'nome' => 'Gestão Obra Demo',
            'construtora' => 'Fornecedor Demo Local',
            'orcamento_inicial' => 1500000,
            'realizado' => 450000,
            'comprometido' => 600000,
            'pdp' => 120000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedObraRecebimentos(): void
    {
        $obraId = DB::table('obras')->orderBy('id')->value('id');
        $usuarioId = DB::table('users')->orderBy('id')->value('id');

        if ($obraId === null) {
            return;
        }

        $itens = [
            ['nome' => 'As built', 'status' => 'pendente'],
            ['nome' => 'Manual de operação', 'status' => 'recebido'],
            ['nome' => 'Treinamento da equipe', 'status' => 'nao_aplicavel'],
        ];

        foreach ($itens as $item) {
            $exists = DB::table('obra_recebimentos')
                ->where('obra_id', $obraId)
                ->where('nome', $item['nome'])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('obra_recebimentos')->insert([
                'obra_id' => $obraId,
                'nome' => $item['nome'],
                'status' => $item['status'],
                'usuario_id' => $usuarioId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function updateOrInsertAndGetId(string $table, array $attributes, array $values = []): int
    {
        $record = DB::table($table)->where($attributes)->first();

        if ($record !== null) {
            if ($values !== []) {
                DB::table($table)
                    ->where('id', $record->id)
                    ->update(array_merge($values, ['updated_at' => now()]));
            }

            return (int) $record->id;
        }

        return (int) DB::table($table)->insertGetId(array_merge(
            $attributes,
            $values,
            [
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ));
    }
}
