<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Ifsnop\Mysqldump as IMysqldump;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;

class DemoBackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demobk:dbcreate --database={database} --new_database={new_database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Demo backup database';

    protected $process;

    protected $host;
    protected $username;
    protected $password;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $database = $this->argument('database');
            $new_database = $this->argument('new_database');
            $this->initDbConfig();

            if (!File::exists(storage_path('app/demo_backups/demo'))) {
                File::makeDirectory(storage_path('app/demo_backups/demo'), 0755, true);
            }

            $tenant_dump = new IMysqldump\Mysqldump(
                'mysql:host=' . $this->host . ';dbname=' . $database, $this->username, $this->password
            );
            $tenant_dump->start(storage_path("app/demo_backups/demo/{$new_database}.sql"));
            Log::info('Backup ' . $database . ' into '. $new_database .' database success');
            
            $this->output->success('Copia de base de datos creada con éxito');
            return 0;

        }catch (Exception $e) {

            Log::error("Backup failed -- Line: {$e->getLine()} - Message: {$e->getMessage()} - File: {$e->getFile()}");

            $this->output->error('Error inesperado: ' . $e->getMessage());
            return 1;
        }

    }


    private function initDbConfig(){

        $dbConfig = config('database.connections.' . config('tenancy.db.system-connection-name', 'system'));

        $this->host = Arr::first(Arr::wrap($dbConfig['host'] ?? ''));
        $this->username = $dbConfig['username'];
        $this->password = $dbConfig['password'];

    }

}
