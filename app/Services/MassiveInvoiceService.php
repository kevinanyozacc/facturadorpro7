<?php

namespace App\Services;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;
use App\Models\System\Client;

class MassiveInvoiceService
{
    public function processExcel($file)
    {
        $spreadsheet = IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        array_shift($rows); // Eliminar encabezados
        
        return $this->transformToJsonRequests($rows);
    }

    private function transformToJsonRequests(array $rows)
    {
        return collect($rows)->map(function($row) {
            if (empty($row[0])) return null;

            // Obtenemos el token del cliente según el RUC emisor
            $rucEmisor = $row[2] ?? null; // RUC_emisor
            $client = \App\Models\System\Client::where('number', $rucEmisor)->first();
            
            if (!$client) {
                throw new \Exception("No se encontró el cliente con RUC: {$rucEmisor}");
            }

            // Obtenemos el token del cliente
            $token = $client->token;
            if (empty($token)) {
                throw new \Exception("El cliente con RUC {$rucEmisor} no tiene token configurado");
            }

            // Determinar tipo de documento y serie
            $tipoComprobante = $row[3] === 'Boleta' ? '03' : '01';
            
            // Determinar tipo de afectación y base imponible según el caso
            $tipoAfectacion = $this->getTipoAfectacion($row[19] ?? '10');
            
            // Obtener valores directamente del Excel
            $cantidad = floatval($row[17] ?? 1); 
            $precio = floatval($row[20] ?? 0);
            
            // Calcular montos según tipo de afectación
            if ($tipoAfectacion == '20' || $tipoAfectacion == '30') { // Exonerado o Inafecto
                $igvPercentage = 0;
                $valorUnitario = $precio; // El precio es el valor unitario sin IGV
                $baseImponible = $valorUnitario * $cantidad;
                $igv = 0;
                $total = $baseImponible;
            } else { // Gravado (10)
                $igvPercentage = 18;
                $valorUnitario = $precio / (1 + ($igvPercentage/100));
                $baseImponible = $valorUnitario * $cantidad;
                $igv = $baseImponible * ($igvPercentage/100);
                $total = $baseImponible + $igv;
            }

            // Validar tipo de documento según receptor
            if ($tipoComprobante === '01' && strlen($row[5]) !== 11) {
                throw new \Exception("Para facturas el receptor debe tener RUC válido de 11 dígitos");
            }

            // Validar documento receptor según tipo
            if ($tipoComprobante === '03' && strlen($row[5]) !== 8) {
                throw new \Exception("Para boletas el receptor debe tener DNI válido de 8 dígitos"); 
            }

            return [
                'tenant_id' => $rucEmisor,
                'token' => $token,
                'data' => [
                    'serie_documento' => $row[4] ?? '',
                    'numero_documento' => '#',
                    'fecha_de_emision' => Carbon::parse($row[0])->format('Y-m-d'),
                    'hora_de_emision' => Carbon::now()->format('H:i:s'),
                    'codigo_tipo_operacion' => '0101',
                    'codigo_tipo_documento' => $tipoComprobante,
                    'codigo_tipo_moneda' => $row[7] ?? 'PEN',
                    'fecha_de_vencimiento' => Carbon::parse($row[1])->format('Y-m-d'),
                    'numero_orden_de_compra' => $row[10] ?? '',
                    
                    'datos_del_cliente_o_receptor' => [
                        'codigo_tipo_documento_identidad' => $tipoComprobante === '03' ? '1' : '6',
                        'numero_documento' => $row[5] ?? '',
                        'apellidos_y_nombres_o_razon_social' => 'CLIENTE GENERAL',
                        'codigo_pais' => 'PE',
                        'ubigeo' => '150101',
                        'direccion' => 'DIRECCION GENERAL',
                        'correo_electronico' => $row[6] ?? '',
                        'telefono' => ''
                    ],
                    
                    'totales' => [
                        'total_exportacion' => 0,
                        'total_operaciones_gravadas' => $tipoAfectacion == '10' ? $baseImponible : 0,
                        'total_operaciones_inafectas' => $tipoAfectacion == '30' ? $baseImponible : 0,
                        'total_operaciones_exoneradas' => $tipoAfectacion == '20' ? $baseImponible : 0,
                        'total_operaciones_gratuitas' => 0,
                        'total_igv' => $igv,
                        'total_impuestos' => $igv,
                        'total_valor' => $baseImponible,
                        'total_venta' => $total
                    ],
                    
                    'items' => [
                        [
                            'codigo_interno' => $row[15] ?? '',
                            'descripcion' => $row[16] ?? '',
                            'codigo_producto_sunat' => '51121703',
                            'unidad_de_medida' => 'NIU',
                            'cantidad' => $cantidad,
                            'valor_unitario' => $valorUnitario,
                            'codigo_tipo_precio' => '01',
                            'precio_unitario' => $precio,
                            'codigo_tipo_afectacion_igv' => $tipoAfectacion,
                            'total_base_igv' => $baseImponible,
                            'porcentaje_igv' => $igvPercentage,
                            'total_igv' => $igv,
                            'total_impuestos' => $igv,
                            'total_valor_item' => $baseImponible,
                            'total_item' => $total
                        ]
                    ],
                    
                    'informacion_adicional' => "Forma de pago:{$row[8]}|{$row[9]}"
                ]
            ];
        })->filter()->values()->all();
    }

    private function getTipoAfectacion($tipo) 
    {
        $tipos = [
            'GRAVADO_OPERACION_ONEROSA' => '10',
            'INAFECTO_OPERACION_ONEROSA' => '30', 
            'EXONERADO_OPERACION_ONEROSA' => '20'
        ];

        // Si viene el código directo (10, 20, 30) lo retorna, sino busca la descripción
        if (in_array($tipo, ['10', '20', '30'])) {
            return $tipo;
        }

        return $tipos[$tipo] ?? '10';
    }
}
