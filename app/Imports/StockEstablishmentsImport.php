<?php

namespace App\Imports;

use App\Models\Tenant\Establishment;
use App\Models\Tenant\Item;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Modules\Inventory\Models\Inventory;
use Modules\Inventory\Models\ItemWarehouse;

class StockEstablishmentsImport implements ToCollection 
{
    use Importable;

    protected $data;

    public function collection(Collection $rows)
    {
        
        $total = count($rows);
        $warehouse_id_de = request('warehouse_id');
        $registered = 0;
        unset($rows[0]);
        
        foreach ($rows as $row) {
            $establishemnts = Establishment::all();

            $establishemnts->each(function($et, $index) use($row, $registered) {
                $internal_id = $row[0] ?: null;
                $quantity_real = $row[$index + 1];

                if (is_null($quantity_real)) {
                    return;
                }

                $et_id = $et->id; // ID del establecimiento

                $item = Item::where('internal_id', str_replace("\t", "", $internal_id))
                                    ->first();

                if (is_null($item)) {
                    Log::info("No existe el producto");
                    return;
                }

                $warehouse_id = $et->warehouse->id;
                $item_warehouse = ItemWarehouse::whereHas('warehouse', function($query) use($et_id) {
                    $query->where('establishment_id', $et_id);
                })
                ->where('item_id', $item->id)
                ->select('stock')
                ->first();

                if (is_null($item_warehouse)) {
                    Log::info("El producto no esta registrado es este warehouse #". $warehouse_id);
                    return;
                }

                $quantity = $item_warehouse->stock;
                $type=1;
				$quantity_new=0;
				$quantity_new=$quantity_real-$quantity;
				if ($quantity_real<$quantity) {
					$quantity_new=$quantity-$quantity_real;
					$type=null;
				}

				$inventory = new Inventory();
				$inventory->type = $type;
				$inventory->description = 'STock Real';
				$inventory->item_id = $item->id;
				$inventory->warehouse_id = $warehouse_id;
				$inventory->quantity = $quantity_new;
				if ($quantity_real<$quantity) {
					$inventory->inventory_transaction_id = 28;
				}
				$inventory->save();

                $registered += 1;
            });
        }

        $this->data = compact('total', 'registered');
    }

    public function getData()
    {
        return $this->data;
    }
}