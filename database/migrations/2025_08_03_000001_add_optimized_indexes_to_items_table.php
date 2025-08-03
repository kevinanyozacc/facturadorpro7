<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOptimizedIndexesToItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('items', function (Blueprint $table) {
            // Índice compuesto para búsquedas por estado activo y descripción
            $table->index(['active', 'description'], 'items_active_description_index');
            
            // Índice compuesto para búsquedas por estado activo y tipo de unidad (servicios)
            $table->index(['active', 'unit_type_id'], 'items_active_unit_type_index');
            
            // Índice compuesto para búsquedas por estado activo y código de barras
            $table->index(['active', 'barcode'], 'items_active_barcode_index');
            
            // Índice compuesto para búsquedas por estado activo e ID interno
            $table->index(['active', 'internal_id'], 'items_active_internal_id_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex('items_active_description_index');
            $table->dropIndex('items_active_unit_type_index');
            $table->dropIndex('items_active_barcode_index');
            $table->dropIndex('items_active_internal_id_index');
        });
    }
}
