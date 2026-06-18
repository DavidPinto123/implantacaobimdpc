<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_templates_config', function (Blueprint $table) {
            $table->string('template_key', 100)->primary();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('whatsapp_subscricoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('template_key', 100);
            $table->timestamps();
            $table->unique(['user_id', 'template_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_subscricoes');
        Schema::dropIfExists('whatsapp_templates_config');
    }
};
