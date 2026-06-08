<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropPosseDataPosseFromProjetosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Remove the column only if it exists.
     */
    public function up()
    {
        if (Schema::hasColumn('projetos', 'posse_data_posse')) {
            Schema::table('projetos', function (Blueprint $table) {
                $table->dropColumn('posse_data_posse');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * Recreate the column only if it does not exist.
     * Ajuste o tipo/default/after conforme o schema original caso necessário.
     */
    public function down()
    {
        if (! Schema::hasColumn('projetos', 'posse_data_posse')) {
            Schema::table('projetos', function (Blueprint $table) {
                $table->date('posse_data_posse')->nullable()->after('data_posse');
            });
        }
    }
}
