<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda_event_participant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_event_id')->constrained('agenda_events')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['agenda_event_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_event_participant');
    }
};
