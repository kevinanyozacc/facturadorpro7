<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Exception;

class DemoRestoreBackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demobk:dbrestore --database={database} --restore_dbname={restore_dbname} --restore_type={restore_type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restaurar base de datos demo de un archivo sql';

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
            $database = $this->argument('database');
            $restore_dbname = $this->argument('restore_dbname');
            $restore_type = $this->argument('restore_type');

            $this->initDbConfig();

            $backupPath = ($restore_type=='demo')?storage_path("app/demo_backups/demo/{$restore_dbname}.sql"):storage_path("app/demo_backups/system/{$restore_dbname}.sql");

            if (!file_exists($backupPath)) {
                Log::error("El archivo de backup no existe: {$backupPath}");
                $this->error("El archivo de backup no existe: {$backupPath}");
                return;
            }

            $this->dropAllTables($database);

            // Construir el comando para restaurar
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

    private function dropAllTables($database) {
        $pdo = new \PDO("mysql:host={$this->host};dbname={$database}", $this->username, $this->password);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    
        $pdo->exec("SET foreign_key_checks = 0");
        $tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
    
        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `$table`");
        }
    
        $pdo->exec("SET foreign_key_checks = 1");
    }

}
