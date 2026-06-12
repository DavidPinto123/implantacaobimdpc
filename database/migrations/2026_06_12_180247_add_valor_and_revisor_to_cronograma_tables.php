<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('cronograma_fases', 'valor')) {
            Schema::table('cronograma_fases', function (Blueprint $table) {
                $table->decimal('valor', 14, 2)->nullable()->after('percentual_conclusao');
            });
        }

        Schema::table('cronograma_fase_itens', function (Blueprint $table) {
            if (! Schema::hasColumn('cronograma_fase_itens', 'valor')) {
                $table->decimal('valor', 14, 2)->nullable()->after('descricao');
            }
            if (! Schema::hasColumn('cronograma_fase_itens', 'revisor_id')) {
                $table->foreignId('revisor_id')->nullable()->after('valor')
                    ->constrained('users')->nullOnDelete();
            }
        });

        // Permite criar tarefas automaticamente sem unidade (marca_id)
        if (Schema::hasColumn('tasks', 'marca_id')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->unsignedBigInteger('marca_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('marca_id')->nullable(false)->change();
        });

        Schema::table('cronograma_fase_itens', function (Blueprint $table) {
            if (Schema::hasColumn('cronograma_fase_itens', 'revisor_id')) {
                $table->dropConstrainedForeignId('revisor_id');
            }
            if (Schema::hasColumn('cronograma_fase_itens', 'valor')) {
                $table->dropColumn('valor');
            }
        });

        if (Schema::hasColumn('cronograma_fases', 'valor')) {
            Schema::table('cronograma_fases', function (Blueprint $table) {
                $table->dropColumn('valor');
            });
        }
    }
};
