<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Services\MassiveInvoiceService;
use Illuminate\Http\Request;
use Exception;

class MassiveInvoiceController extends Controller
{
    protected $massiveInvoiceService;

    public function __construct(MassiveInvoiceService $massiveInvoiceService)
    {
        $this->massiveInvoiceService = $massiveInvoiceService;
    }

    public function index()
    {
        return view('system.massive_invoice.index');
    }

    public function downloadFormat()
    {
        $filename = public_path('formats/formato_facturas_masivas.xlsx');
        return response()->download($filename);
    }

    public function upload(Request $request)
    {
        if ($request->hasFile('file')) {
            try {
                $file = $request->file('file');
                $jsonRequests = $this->massiveInvoiceService->processExcel($file);
                
                // Guardamos temporalmente los datos procesados en la sesión
                session(['massive_invoice_data' => $jsonRequests]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Archivo procesado correctamente',
                    'data' => $jsonRequests
                ]);
            } catch (Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al procesar el archivo: ' . $e->getMessage()
                ], 422);
            }
        }
        
        return response()->json([
            'success' => false,
            'message' => 'No se encontró ningún archivo'
        ], 422);
    }

    public function process(Request $request) 
    {
        try {
            $documents = $request->input('documents', []);
            $responses = [];
            
            foreach($documents as $document) {
                try {
                    $client = new \GuzzleHttp\Client([
                        'verify' => false,
                        'http_errors' => false
                    ]);
                    
                    $tenant = \App\Models\System\Client::where('number', $document['tenant_id'])->first();
                    if (!$tenant) {
                        throw new \Exception("Cliente no encontrado para el RUC: " . $document['tenant_id']);
                    }

                    $urlBase = $tenant->hostname->fqdn;
                    $protocol = config('app.env') === 'production' ? 'https' : 'http';
                    $apiUrl = "{$protocol}://{$urlBase}/api/documents";

                    $response = $client->post($apiUrl, [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $document['token'],
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json'
                        ],
                        'json' => $document['data']
                    ]);
                    
                    $statusCode = $response->getStatusCode();
                    $responseBody = json_decode((string) $response->getBody(), true);

                    // Obtener el número completo del comprobante de la respuesta
                    $numeroCompleto = $responseBody['data']['number'] ?? null;
                    if(!$numeroCompleto) {
                        throw new \Exception("No se pudo obtener el número de comprobante de la respuesta");
                    }

                    // Verificar respuesta de SUNAT
                    $estadoSunat = 'Pendiente';
                    $mensajeSunat = '';
                    
                    if (isset($responseBody['data']['state_type_id'])) {
                        switch($responseBody['data']['state_type_id']) {
                            case '01':
                                $estadoSunat = 'Registrado';
                                break;
                            case '03':
                                $estadoSunat = 'Enviado';
                                break;
                            case '05':
                                $estadoSunat = 'Aceptado';
                                break;
                            case '09':
                            case '11':
                                $estadoSunat = 'Rechazado';
                                break;
                            default:
                                $estadoSunat = 'Pendiente';
                        }
                    }

                    // Extraer montos del documento
                    $totales = $document['data']['totales'];
                    $baseImponible = $totales['total_operaciones_gravadas'] ?? 0;
                    $igv = $totales['total_igv'] ?? 0;
                    $total = $totales['total_venta'] ?? 0;

                    // Guardar en BD con los montos correctos y el número de la respuesta
                    $invoice = \App\Models\System\MassiveInvoice::create([
                        'fecha_emision' => $document['data']['fecha_de_emision'],
                        'fecha_vencimiento' => $document['data']['fecha_de_vencimiento'],
                        'tipo_comprobante' => $document['data']['codigo_tipo_documento'],
                        'serie_comprobante' => $numeroCompleto,  // Guardamos el número completo de la respuesta
                        'ruc' => $document['data']['datos_del_cliente_o_receptor']['numero_documento'],
                        'correo' => $document['data']['datos_del_cliente_o_receptor']['correo_electronico'],
                        'moneda' => $document['data']['codigo_tipo_moneda'],
                        'forma_pago' => explode('|', $document['data']['informacion_adicional'])[0] ?? '',
                        'observacion' => explode('|', $document['data']['informacion_adicional'])[1] ?? '',
                        'item' => $document['data']['items'][0]['codigo_interno'],
                        'descripcion_producto' => $document['data']['items'][0]['descripcion'],
                        'cantidad' => $document['data']['items'][0]['cantidad'],
                        'unidad_medida' => $document['data']['items'][0]['unidad_de_medida'],
                        'tipo_afectacion' => $document['data']['items'][0]['codigo_tipo_afectacion_igv'],
                        'precio' => $document['data']['items'][0]['precio_unitario'],
                        'status' => $responseBody['success'] ? 'PROCESADO' : 'ERROR',
                        'nota' => isset($responseBody['message']) ? $responseBody['message'] : '',
                        'external_id' => $responseBody['data']['external_id'] ?? null,
                        'pdf_link' => $responseBody['links']['pdf'] ?? null,
                        'xml_link' => $responseBody['links']['xml'] ?? null,
                        'cdr_link' => $responseBody['links']['cdr'] ?? null,
                        'estado_sunat' => $estadoSunat,
                        'mensaje_sunat' => $mensajeSunat,
                        'total_gravado' => $baseImponible,
                        'total_igv' => $igv,
                        'total_venta' => $total
                    ]);
                    
                    $responses[] = [
                        'success' => true,
                        'tenant' => $tenant->number,
                        'url' => $apiUrl,
                        'data' => $responseBody
                    ];

                } catch (\Exception $e) {
                    \Log::error('Error procesando documento: ' . $e->getMessage());
                    
                    $responses[] = [
                        'success' => false,
                        'tenant' => $document['tenant_id'] ?? 'No disponible',
                        'error' => $e->getMessage(),
                        'url' => $apiUrl ?? null
                    ];
                }
            }
            
            // Verificar si todos fallaron
            $allFailed = collect($responses)->every(function($response) {
                return !$response['success'];
            });

            return response()->json([
                'success' => !$allFailed,
                'message' => $allFailed ? 'Ningún documento pudo ser procesado' : 'Proceso completado con algunos errores',
                'responses' => $responses
            ], $allFailed ? 422 : 200);
            
        } catch (\Exception $e) {
            \Log::error('Error general: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error en el proceso: ' . $e->getMessage()
            ], 422);
        }
    }

    public function records()
    {
        $records = \App\Models\System\MassiveInvoice::latest()
            ->select([
                'id',
                'fecha_emision',
                'tipo_comprobante',
                'serie_comprobante',
                'ruc',
                'descripcion_producto',
                'cantidad',
                'precio',
                'status',
                'nota',
                'estado_sunat',
                'total_gravado',
                'total_igv',
                'total_venta'
            ])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $records
        ]);
    }

    public function downloadFile($id, $type)
    {
        $invoice = \App\Models\System\MassiveInvoice::findOrFail($id);

        try {
            $client = new \GuzzleHttp\Client([
                'verify' => false,
                'http_errors' => false
            ]);

            // Usar el link guardado directamente
            $downloadUrl = $type === 'xml' ? $invoice->xml_link : $invoice->pdf_link;

            if (empty($downloadUrl)) {
                throw new \Exception("URL de descarga no disponible");
            }

            $response = $client->get($downloadUrl);
            
            if ($response->getStatusCode() !== 200) {
                throw new \Exception("Error al descargar archivo");
            }

            $contentType = $type === 'xml' ? 'application/xml' : 'application/pdf';
            $extension = $type === 'xml' ? 'xml' : 'pdf';
            $filename = "{$invoice->serie_comprobante}.{$extension}";

            return response((string) $response->getBody())
                ->header('Content-Type', $contentType)
                ->header('Content-Disposition', "attachment; filename={$filename}");

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
