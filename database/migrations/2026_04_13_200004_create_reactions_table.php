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
        if (Schema::hasTable('reactions')) {
            return;
        }

        Schema::create('reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reactable_type');
            $table->unsignedBigInteger('reactable_id');
            $table->string('emoji');
            $table->char('guest_id', 26)->nullable();
            $table->string('guest_name')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['reactable_type', 'reactable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reactions');
    }
};
