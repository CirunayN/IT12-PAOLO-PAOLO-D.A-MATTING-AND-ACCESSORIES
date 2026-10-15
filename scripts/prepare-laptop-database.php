<?php

try {
    require __DIR__.'/../vendor/autoload.php';
    $environment = Dotenv\Dotenv::createArrayBacked(dirname(__DIR__))->safeLoad();
    $setup = new App\Services\LaptopDatabaseSetup;
    $setup->databaseName($environment);
    $setup->prepare($environment, getenv('PAOLO_INSTALL_WINDOWS_USER') ?: '');
    fwrite(STDOUT, "Local application database and Windows access are ready.\n");
} catch (Throwable $error) {
    fwrite(STDERR, 'Database setup failed: '.$error->getMessage()."\n");
    exit(1);
}
