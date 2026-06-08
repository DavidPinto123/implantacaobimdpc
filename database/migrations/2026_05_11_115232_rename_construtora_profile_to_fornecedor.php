<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->renomearRole('Construtora', 'Fornecedor');
        $this->renomearSetor('Terceiros Construtora', 'Terceiros Fornecedor');
        $this->renomearChavesWhatsapp('construtora.', 'fornecedor.');
    }

    public function down(): void
    {
        $this->renomearRole('Fornecedor', 'Construtora');
        $this->renomearSetor('Terceiros Fornecedor', 'Terceiros Construtora');
        $this->renomearChavesWhatsapp('fornecedor.', 'construtora.');
    }

    private function renomearRole(string $atual, string $novo): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $roleAtual = DB::table('roles')
            ->where('name', $atual)
            ->where('guard_name', 'web')
            ->first();

        if ($roleAtual === null) {
            return;
        }

        $roleNova = DB::table('roles')
            ->where('name', $novo)
            ->where('guard_name', 'web')
            ->first();

        if ($roleNova === null) {
            DB::table('roles')
                ->where('id', $roleAtual->id)
                ->update(['name' => $novo]);

            return;
        }

        if (Schema::hasTable('model_has_roles')) {
            DB::table('model_has_roles')
                ->where('role_id', $roleAtual->id)
                ->orderBy('model_id')
                ->get()
                ->each(function (object $vinculo) use ($roleNova): void {
                    DB::table('model_has_roles')->insertOrIgnore([
                        'role_id' => $roleNova->id,
                        'model_type' => $vinculo->model_type,
                        'model_id' => $vinculo->model_id,
                    ]);
                });

            DB::table('model_has_roles')->where('role_id', $roleAtual->id)->delete();
        }

        if (Schema::hasTable('role_has_permissions')) {
            DB::table('role_has_permissions')
                ->where('role_id', $roleAtual->id)
                ->pluck('permission_id')
                ->each(function (int $permissionId) use ($roleNova): void {
                    DB::table('role_has_permissions')->insertOrIgnore([
                        'permission_id' => $permissionId,
                        'role_id' => $roleNova->id,
                    ]);
                });

            DB::table('role_has_permissions')->where('role_id', $roleAtual->id)->delete();
        }

        DB::table('roles')->where('id', $roleAtual->id)->delete();
    }

    private function renomearSetor(string $atual, string $novo): void
    {
        if (! Schema::hasTable('setores')) {
            return;
        }

        $setorAtual = DB::table('setores')->where('setor', $atual)->first();

        if ($setorAtual === null) {
            return;
        }

        $setorNovo = DB::table('setores')->where('setor', $novo)->first();

        if ($setorNovo === null) {
            DB::table('setores')
                ->where('id', $setorAtual->id)
                ->update(['setor' => $novo]);

            return;
        }

        if (Schema::hasTable('setor_user')) {
            DB::table('setor_user')
                ->where('setor_id', $setorAtual->id)
                ->update(['setor_id' => $setorNovo->id]);
        }

        DB::table('setores')->where('id', $setorAtual->id)->delete();
    }

    private function renomearChavesWhatsapp(string $prefixoAtual, string $prefixoNovo): void
    {
        if (! Schema::hasTable('po_whatsapp_bot_mensagens')) {
            return;
        }

        DB::table('po_whatsapp_bot_mensagens')
            ->where('chave', 'like', $prefixoAtual.'%')
            ->orderBy('id')
            ->get(['id', 'chave'])
            ->each(function (object $mensagem) use ($prefixoAtual, $prefixoNovo): void {
                $novaChave = $prefixoNovo.substr((string) $mensagem->chave, strlen($prefixoAtual));

                if (DB::table('po_whatsapp_bot_mensagens')->where('chave', $novaChave)->exists()) {
                    DB::table('po_whatsapp_bot_mensagens')->where('id', $mensagem->id)->delete();

                    return;
                }

                DB::table('po_whatsapp_bot_mensagens')
                    ->where('id', $mensagem->id)
                    ->update(['chave' => $novaChave]);
            });
    }
};
