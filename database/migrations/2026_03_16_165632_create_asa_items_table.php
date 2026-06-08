<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('asa_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asa_id')
                ->constrained('asas')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('item', 50)->nullable();
            $table->string('descricao');
            $table->string('unidade', 50)->nullable();
            $table->decimal('quantidade', 15, 2)->default(1);
            $table->decimal('valor_unitario', 15, 2)->default(0);
            $table->decimal('valor_total', 15, 2)->default(0);

            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asa_items');
    }
};
