<?php

use App\Enums\AsStatus;
use App\Enums\StatusControleNotaFiscalNota;
use App\Models\AsEscopo;
use App\Models\AutorizacaoServico;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalItem;
use App\Models\ControleNotaFiscalNota;
use App\Models\Obras;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function createFinanceiroUserWithPermissions(array $permissions, bool $asSuperAdmin = false): User
{
    $user = User::factory()->active()->create();

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    if ($permissions !== []) {
        $user->givePermissionTo($permissions);
    }

    if ($asSuperAdmin) {
        $role = Role::findOrCreate('super_admin', 'web');
        $user->assignRole($role);
    }

    return $user;
}

function createControleNotaFiscalComNota(?User $importadoPor = null): array
{
    $obra = Obras::factory()->create();

    $controle = ControleNotaFiscal::create([
        'obra_id' => $obra->id,
        'status' => ControleNotaFiscal::STATUS_AGUARDANDO_CONSTRUTORA,
        'data_base' => now()->toDateString(),
        'unidade' => $obra->unidade,
        'sigla' => $obra->sigla,
        'endereco' => $obra->endereco,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Grupo Teste',
        'numero_as' => 'AS-'.strtoupper(str()->random(6)),
        'escopo' => 'Escopo de teste',
        'is_active' => true,
        'is_personalizado' => false,
    ]);

    $autorizacaoServico = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $escopo->id,
        'status' => AsStatus::CRIADA,
        'numero_as' => 'AS-'.strtoupper(str()->random(6)),
        'valor' => 1000,
        'valor_estimado' => 1000,
    ]);

    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'autorizacao_servico_id' => $autorizacaoServico->id,
        'as_escopo_id' => $escopo->id,
        'grupo' => $escopo->grupo,
        'numero_as' => $escopo->numero_as,
        'escopo' => $escopo->escopo,
        'empresa' => 'Empresa Teste',
        'valor_global_a' => 1000,
        'total_medicao_a_menos_b' => 1000,
        'valor_acumulado_medido' => 0,
        'saldo' => 1000,
    ]);

    $autorizacaoServico->update([
        'controle_nota_fiscal_item_id' => $item->id,
    ]);

    $nota = ControleNotaFiscalNota::create([
        'autorizacao_servico_id' => $autorizacaoServico->id,
        'importado_por_id' => $importadoPor?->id,
        'tipo_medicao' => 'mao_obra',
        'empresa' => 'Empresa Teste',
        'numero_nf' => 'NF-'.strtoupper(str()->random(8)),
        'valor_acumulado_medido_nf' => 150,
        'emissao' => now()->toDateString(),
        'envio' => now()->toDateString(),
        'status' => StatusControleNotaFiscalNota::EM_ANALISE->value,
    ]);

    return compact('controle', 'item', 'nota');
}
