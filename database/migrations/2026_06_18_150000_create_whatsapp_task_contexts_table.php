<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_task_contexts', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->index();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('task_title');
            $table->timestamp('expires_at');
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_task_contexts');
    }
};
