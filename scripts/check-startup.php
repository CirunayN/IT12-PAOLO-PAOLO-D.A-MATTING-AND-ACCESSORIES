<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;

define('LARAVEL_START', microtime(true));

try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require __DIR__.'/../bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();

    if (! config('app.key')) {
        throw new RuntimeException('APP_KEY is missing. Run scripts\php.ps1 artisan key:generate.');
    }

    foreach (['styles.css', 'app.js'] as $asset) {
        if (! is_file(public_path('assets/build/'.$asset))) {
            throw new RuntimeException('The bundled UI file '.$asset.' is missing. Restore public/assets/build from GitHub.');
        }
    }

    $connectionName = config('database.default');
    if ($connectionName === 'sqlsrv' && ! in_array('sqlsrv', PDO::getAvailableDrivers(), true)) {
        throw new RuntimeException('The PHP SQL Server driver is not loaded. Run scripts\setup-sqlserver.ps1 once, then use start.bat.');
    }

    // This check reads the database without migrating, seeding, or restoring data.
    config(['database.connections.'.$connectionName.'.login_timeout' => 5]);
    $database = $app->make('db');
    $database->connection()->getPdo();
    if (config('session.driver') === 'database') {
        $sessionConnectionName = config('session.connection') ?: $connectionName;
        config(['database.connections.'.$sessionConnectionName.'.login_timeout' => 5]);
        $sessionConnection = $database->connection($sessionConnectionName);
        if (! $sessionConnection->getSchemaBuilder()->hasTable(config('session.table'))) {
            throw new RuntimeException('The sessions table is missing. Run scripts\php.ps1 artisan migrate against the intended app database.');
        }
    }

    fwrite(STDOUT, "Startup check passed.\n");
    exit(0);
} catch (Throwable $error) {
    fwrite(STDERR, 'Startup check failed: '.$error->getMessage()."\n");
    if ($error instanceof PDOException || $error instanceof QueryException) {
        fwrite(STDERR, "Check that SQL Server (SQLEXPRESS) is running and the .env database settings match your local instance.\n");
    }
    exit(1);
}
