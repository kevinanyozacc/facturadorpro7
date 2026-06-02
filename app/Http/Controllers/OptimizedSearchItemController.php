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
        $input = $request->input('input', '');
        $search_by_barcode = $request->has('search_by_barcode') && (bool)$request->search_by_barcode;
        $search_item_by_barcode_presentation = $request->has('search_item_by_barcode_presentation') && (bool)$request->search_item_by_barcode_presentation;
        $search_factory_code_items = $request->has('search_factory_code_items') && (bool)$request->search_factory_code_items;
        
        // Obtener warehouse del usuario una sola vez
        $establishment_id = auth()->user()->establishment_id;
        $warehouse = Warehouse::where('establishment_id', $establishment_id)->first();
        
        // Query completamente optimizada - Todos los campos necesarios
        $query = Item::select([
            'items.*'  // Seleccionar todos los campos para compatibilidad completa
        ])
        ->where('items.active', 1);
        
        // OPTIMIZACIÓN TEMPORAL: Omitir filtro de warehouse para testing
        // TODO: Reactivar cuando esté funcionando
        /*
        if ($warehouse) {
            $query->leftJoin('item_warehouse', function($join) use ($warehouse) {
                $join->on('items.id', '=', 'item_warehouse.item_id')
                     ->where('item_warehouse.warehouse_id', $warehouse->id);
            })
            ->where(function($q) {
                $q->whereNotNull('item_warehouse.item_id')
                  ->orWhere('items.unit_type_id', 'ZZ'); // Servicios siempre se muestran
            });
        }
        */
        
        // Aplicar filtros de búsqueda con LÍMITES AGRESIVOS
        if (!empty($input)) {
            if ($search_by_barcode) {
                // Búsqueda por código de barras - súper rápida
                $query->where('items.barcode', $input)->limit(1);
            } else {
                // Búsqueda por texto - LIMITADA a 30 resultados máximo
                $searchInput = str_replace(' ', '%', $input);
                $query->where(function($q) use ($searchInput, $input, $search_factory_code_items) {
                    $q->where('items.description', 'like', "%{$searchInput}%")
                      ->orWhere('items.name', 'like', "%{$searchInput}%")
                      ->orWhere('items.internal_id', 'like', "%{$input}%")
                      ->orWhere('items.barcode', $input);
                    
                    if ($search_factory_code_items) {
                        $q->orWhere('items.factory_code', 'like', "%{$input}%");
                    }
                })
                ->limit(10); // LÍMITE REDUCIDO para testing
            }
        } else {
            // Sin filtro - solo 3 resultados
            $query->limit(3);
        }
        
        // Ordenar por relevancia SIMPLE
        if (!empty($input) && !$search_by_barcode) {
            $query->orderByRaw("
                CASE 
                    WHEN items.barcode = ? THEN 1
                    WHEN items.internal_id = ? THEN 2
                    WHEN items.name LIKE ? THEN 3
                    WHEN items.description LIKE ? THEN 4
                    ELSE 5
                END, items.name ASC
            ", [$input, $input, $input . '%', $input . '%']);
        } else {
            $query->orderBy('items.name');
        }
        
        // Ejecutar consulta con relaciones MÍNIMAS necesarias para getDataToItemModal
        $items = $query->with([
            'sale_affectation_igv_type', // Cargar toda la relación por ahora
            'category:id,name',  // Solo campos necesarios
            'brand:id,name',     // Solo campos necesarios
        ])->get();
        
        // Usar el método original del modelo pero de forma optimizada
        return $items->transform(function ($item) use ($warehouse) {
            try {
                // Cargar solo las relaciones necesarias de forma lazy
                $item->load([
                    'item_unit_types' => function($query) {
                        $query->select('id', 'item_id', 'unit_type_id', 'description', 'quantity_unit', 'price1', 'price2', 'price3', 'price_default', 'barcode');
                    }
                ]);
                
                $data = $item->getDataToItemModal($warehouse);
                
                // AGREGAR ALIAS CRÍTICO: El frontend espera 'affectation_igv_type' no 'sale_affectation_igv_type'
                // Asegurar que siempre tenga la estructura correcta con 'free'
                if (isset($data['sale_affectation_igv_type'])) {
                    // Si sale_affectation_igv_type es un objeto Eloquent, convertir a array
                    if (is_object($data['sale_affectation_igv_type'])) {
                        $saleAffectation = $data['sale_affectation_igv_type'];
                        $data['sale_affectation_igv_type'] = [
                            'id' => $saleAffectation->id ?? null,
                            'description' => $saleAffectation->description ?? '',
                            'free' => $saleAffectation->free ?? false
                        ];
                    }
                    
                    $data['affectation_igv_type'] = $data['sale_affectation_igv_type'];
                } else {
                    // Si no existe, crear estructura por defecto
                    $data['affectation_igv_type'] = ['free' => false];
                    $data['sale_affectation_igv_type'] = ['free' => false];
                }
                
                // Asegurar que 'free' siempre exista - verificación adicional
                if (is_array($data['affectation_igv_type']) && !isset($data['affectation_igv_type']['free'])) {
                    $data['affectation_igv_type']['free'] = false;
                }
                if (is_array($data['sale_affectation_igv_type']) && !isset($data['sale_affectation_igv_type']['free'])) {
                    $data['sale_affectation_igv_type']['free'] = false;
                }
                
                return $data;
            } catch (\Exception $e) {
                // En caso de error, devolver estructura básica pero completa
                return [
                    'id' => $item->id,
                    'name' => $item->name ?? '',
                    'description' => $item->description ?? '',
                    'internal_id' => $item->internal_id ?? '',
                    'barcode' => $item->barcode ?? '',
                    'sale_unit_price' => (float) $item->sale_unit_price,
                    'currency_type_id' => $item->currency_type_id ?? 'PEN',
                    'unit_type_id' => $item->unit_type_id ?? 'NIU',
                    'stock' => 0,
                    'has_igv' => (bool) $item->has_igv,
                    'full_description' => trim(($item->name ?? '') . ' ' . ($item->description ?? '')),
                    'item_unit_types' => [],
                    'category' => $item->category ?? ['id' => null, 'name' => ''],
                    'brand' => $item->brand ?? ['id' => null, 'name' => ''],
                    'warehouses' => [],
                    'lots' => [],
                    'sale_affectation_igv_type' => ['free' => false], // Estructura garantizada
                    'affectation_igv_type' => ['free' => false], // ALIAS CRÍTICO con estructura garantizada
                    'error' => $e->getMessage()
                ];
            }
        });
    }
    
    /**
     * Método alternativo para casos específicos donde no se necesita filtro por almacén
     */
    public static function getSimpleItemsSearch(Request $request = null)
    {
        $input = $request->input('input', '');
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
