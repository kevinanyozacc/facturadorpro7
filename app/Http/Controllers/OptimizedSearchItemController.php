<?php

namespace App\Http\Controllers;

use App\Models\Tenant\Item;
use App\Models\Tenant\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controlador optimizado para búsqueda de items
 * Mejora el rendimiento de la búsqueda de productos
 */
class OptimizedSearchItemController extends Controller
{
    /**
     * Búsqueda optimizada de items para documentos
     * Evita subconsultas costosas y mejora el rendimiento
     */
    public static function getOptimizedItemsToDocuments(Request $request = null)
    {
        $input = $request->input ?? '';
        $search_by_barcode = $request->has('search_by_barcode') && (bool)$request->search_by_barcode;
        $search_item_by_barcode_presentation = $request->has('search_item_by_barcode_presentation') && (bool)$request->search_item_by_barcode_presentation;
        $search_factory_code_items = $request->has('search_factory_code_items') && (bool)$request->search_factory_code_items;
        
        // Obtener warehouse del usuario una sola vez
        $establishment_id = auth()->user()->establishment_id;
        $warehouse = Warehouse::where('establishment_id', $establishment_id)->first();
        
        // Query base optimizada
        $query = Item::select([
            'items.*'
        ])
        ->where('items.active', 1);
        
        // Filtro por almacén optimizado usando JOIN en lugar de whereHas
        if ($warehouse) {
            $query->leftJoin('item_warehouses', function($join) use ($warehouse) {
                $join->on('items.id', '=', 'item_warehouses.item_id')
                     ->where('item_warehouses.warehouse_id', $warehouse->id);
            })
            ->where(function($q) {
                $q->whereNotNull('item_warehouses.item_id')
                  ->orWhere('items.unit_type_id', 'ZZ'); // Servicios
            });
        }
        
        // Aplicar filtros de búsqueda
        if (!empty($input)) {
            if ($search_by_barcode) {
                if ($search_item_by_barcode_presentation) {
                    // Búsqueda por código de barras de presentación
                    $query->whereHas('item_unit_types', function($q) use ($input) {
                        $q->where('barcode', $input);
                    })->limit(1);
                } else {
                    // Búsqueda directa por código de barras
                    $query->where('items.barcode', $input)->limit(1);
                }
            } else {
                // Búsqueda por texto con límite
                $searchInput = str_replace(' ', '%', $input);
                $query->where(function($q) use ($searchInput, $input, $search_factory_code_items) {
                    $q->where('items.description', 'like', "%{$searchInput}%")
                      ->orWhere('items.internal_id', 'like', "%{$input}%")
                      ->orWhere('items.barcode', $input);
                    
                    if ($search_factory_code_items) {
                        $q->orWhere('items.factory_code', 'like', "%{$input}%");
                    }
                })
                ->limit(config('extra.number_items_in_search', 50));
            }
        } else {
            // Sin filtro, limitar resultados iniciales
            $query->limit(config('extra.number_items_at_start', 10));
        }
        
        // Cargar relaciones necesarias
        $query->with(['warehousePrices', 'category', 'brand']);
        
        // Ordenar por relevancia
        if (!empty($input) && !$search_by_barcode) {
            $query->orderByRaw("
                CASE 
                    WHEN items.barcode = ? THEN 1
                    WHEN items.internal_id = ? THEN 2
                    WHEN items.description LIKE ? THEN 3
                    ELSE 4
                END, items.description ASC
            ", [$input, $input, $input . '%']);
        } else {
            $query->orderBy('items.description');
        }
        
        $items = $query->get();
        
        // Transformar a formato modal
        return $items->transform(function ($item) use ($warehouse) {
            return $item->getDataToItemModal($warehouse);
        });
    }
    
    /**
     * Método alternativo para casos específicos donde no se necesita filtro por almacén
     */
    public static function getSimpleItemsSearch(Request $request = null)
    {
        $input = $request->input ?? '';
        $search_by_barcode = $request->has('search_by_barcode') && (bool)$request->search_by_barcode;
        
        $query = Item::where('active', 1);
        
        if (!empty($input)) {
            if ($search_by_barcode) {
                $query->where('barcode', $input)->limit(1);
            } else {
                $searchInput = str_replace(' ', '%', $input);
                $query->where(function($q) use ($searchInput, $input) {
                    $q->where('description', 'like', "%{$searchInput}%")
                      ->orWhere('internal_id', 'like', "%{$input}%")
                      ->orWhere('barcode', $input);
                })
                ->limit(25); // Límite más bajo para búsqueda simple
            }
        } else {
            $query->limit(10);
        }
        
        return $query->orderBy('description')->get();
    }
}
