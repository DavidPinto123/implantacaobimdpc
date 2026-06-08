<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('midias', function (Blueprint $table) {
            $table->id();
            $table->morphs('mediavel');
            $table->string('path');
            $table->string('disk', 20)->default('r2');
            $table->string('categoria')->default('obra');
            $table->string('tipo', 30)->default('imagem');
            $table->string('nome_original')->nullable();
            $table->unsignedInteger('ordem')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['mediavel_type', 'mediavel_id', 'categoria']);
        });

        $this->migrarFotosObras();
    }

    public function down(): void
    {
        Schema::dropIfExists('midias');
    }

    private function migrarFotosObras(): void
    {
        $obras = DB::table('obras')
            ->whereNotNull('fotos')
            ->where('fotos', '!=', '[]')
            ->get(['id', 'fotos']);

        foreach ($obras as $obra) {
            $fotos = json_decode($obra->fotos, true);

            if (! is_array($fotos)) {
                continue;
            }

            foreach ($fotos as $ordem => $path) {
                if (! is_string($path) || empty($path)) {
                    continue;
                }

                $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $tipo = match (true) {
                    in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']) => 'imagem',
                    in_array($extension, ['mp4', 'mov', 'avi', 'webm']) => 'video',
                    in_array($extension, ['pdf']) => 'documento',
                    default => 'arquivo',
                };

                DB::table('midias')->insert([
                    'mediavel_type' => 'App\\Models\\Obras',
                    'mediavel_id' => $obra->id,
                    'path' => $path,
                    'disk' => 'r2',
                    'categoria' => 'obra',
                    'tipo' => $tipo,
                    'nome_original' => basename($path),
                    'ordem' => $ordem,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
