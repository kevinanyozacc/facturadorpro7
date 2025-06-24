<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant\{Configuration, Company, Dispatch};
use Modules\ApiPeruDev\Http\Controllers\ServiceDispatchController;

class DispatchSendCommand extends Command
{
    protected $signature = 'dispatch:send';
    protected $description = 'Automatic send of dispatches to SUNAT';

    public function handle()
    {
        if (Configuration::firstOrFail()->cron) {
            $configuration = Configuration::firstOrFail();
            $company = Company::firstOrFail();

            // Buscar guias sin data en ticket y que esten en el entorno actual de la empresa
            $dispatches = Dispatch::query()
                ->where('soap_type_id', $company->soap_type_id)
                ->whereNull('ticket')
                ->get();

            if ($dispatches->isEmpty()) {
                $this->info('No dispatches pending to send');
                return;
            }

            $serviceController = new ServiceDispatchController();

            foreach ($dispatches as $dispatch) {
                try {
                    $result = $serviceController->send($dispatch->external_id);

                    if ($result['success']) {
                        $this->info("Dispatch {$dispatch->series}-{$dispatch->number} sent successfully: {$result['message']}");
                    } else {
                        $this->error("Failed to send dispatch {$dispatch->series}-{$dispatch->number}: {$result['message']}");
                    }
                } catch (\Exception $e) {
                    $this->error("Error sending dispatch {$dispatch->series}-{$dispatch->number}: " . $e->getMessage());
                }
            }
        } else {
            $this->info('The crontab is disabled');
        }

        $this->info('The command is finished');
    }
}
