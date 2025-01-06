<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Models\System\Client;
use Hyn\Tenancy\Environment;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use Illuminate\Support\Facades\DB;

class DemoRestoreTemporaryBackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demodb:bktemporary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restaurar base de datos demo de un archivo sql de diferentes tenant';

    private $host;
    private $username;
    private $password;

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

            $records = Client::latest()->get();

            foreach ($records as &$row) {
                $tenancy = app(Environment::class);
                $tenancy->tenant($row->hostname->website);
                $row->soap_type = DB::connection('tenant')
                    ->table('companies')
                    ->first()
                    ->soap_type_id;
                $row->database = $row->hostname->website->uuid;
            }

            $this->initDbConfig();

            $demoClients = $records->filter(function ($row) {
                return $row->soap_type === '01' && $row->enabled_cron_restore_bkdemo == 1 && $row->restore_dbname_bkdemo != null && $row->restore_type_bkdemo!=null;
            });

            foreach ($demoClients as $client) {

                $database = $client->database;
                $restore_dbname = $client->restore_dbname_bkdemo;
                $restore_type = $client->restore_type_bkdemo;

                $backupPath = ($restore_type=='demo')?storage_path("app/demo_backups/demo/{$restore_dbname}.sql"):storage_path("app/demo_backups/system/{$restore_dbname}.sql");

                if (!file_exists($backupPath)) {
                    Log::error("El archivo de backup no existe: {$backupPath}");
                    $this->error("El archivo de backup no existe: {$backupPath}");
                    return;
                }

                $this->dropAndRecreateDatabase($database);

                $command = sprintf(
                    'mysql -h %s -u %s -p%s %s < %s',
                    escapeshellarg($this->host),
                    escapeshellarg($this->username),
                    escapeshellarg($this->password),
                    escapeshellarg($database),
                    escapeshellarg($backupPath)
                );

                exec($command, $output, $returnVar);

                if ($returnVar !== 0) {
                    Log::error("Error al restaurar la base de datos {$database}.");
                    $this->error("Error al restaurar la base de datos {$database}.");
                    return;
                }

                Log::info("Base de datos {$database} restaurada correctamente.");
                $this->info("Base de datos {$database} restaurada correctamente.");

            }

            
        } catch (Exception $e) {
            Log::error("Error restaurando la base de datos: " . $e->getMessage());
            $this->error("Error: " . $e->getMessage());
        }
    }

    private function initDbConfig()
    {
        $dbConfig = config('database.connections.mysql');

        $this->host = $dbConfig['host'];
        $this->username = $dbConfig['username'];
        $this->password = $dbConfig['password'];
    }

    private function dropAndRecreateDatabase($database)
    {
        try {
            $pdo = new \PDO("mysql:host={$this->host}", $this->username, $this->password);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $pdo->exec("DROP DATABASE IF EXISTS `$database`");
            $pdo->exec("CREATE DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        } catch (\PDOException $e) {
            Log::error("Error al eliminar o recrear la base de datos {$database}: " . $e->getMessage());
            throw $e;
        }
    }
    
}
