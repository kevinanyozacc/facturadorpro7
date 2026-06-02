<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TenantAddOptimizedIndexesToItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('items', function (Blueprint $table) {
            // Verificar si los índices no existen antes de crearlos
            if (!$this->hasIndex('items', 'items_active_description_index')) {
                $table->index(['active', 'description'], 'items_active_description_index');
            }
            
            if (!$this->hasIndex('items', 'items_active_unit_type_index')) {
                $table->index(['active', 'unit_type_id'], 'items_active_unit_type_index');
            }
            
            if (!$this->hasIndex('items', 'items_active_barcode_index')) {
                $table->index(['active', 'barcode'], 'items_active_barcode_index');
            }
            
            if (!$this->hasIndex('items', 'items_active_internal_id_index')) {
                $table->index(['active', 'internal_id'], 'items_active_internal_id_index');
            }
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

    /**
     * Verificar si un índice existe
     */
    private function hasIndex($table, $index)
    {
        $connection = Schema::getConnection();
        $indexes = $connection->getDoctrineSchemaManager()->listTableIndexes($table);
        return array_key_exists($index, $indexes);
    }
}
