<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TenantAddColumnAvailableDetractionForAmountMinorToConfigurations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->boolean('available_detraction_for_amount_minor')->default(false)->comment("Opción para que permita realizar detracciones por montos menores a 700 o USD");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->dropColumn('available_detraction_for_amount_minor');
        });
    }
}
