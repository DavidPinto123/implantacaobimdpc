<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_escopos', function (Blueprint $table): void {
            $table->string('item_recebimento')->nullable()->after('escopo');
        });
    }

    public function down(): void
    {
        Schema::table('as_escopos', function (Blueprint $table): void {
            $table->dropColumn('item_recebimento');
        });
    }
};
