<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('po_whatsapp_config', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number_id');
            $table->text('token'); // encrypted
            $table->string('verify_token');
            $table->boolean('ativo')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_whatsapp_config');
    }
};
