<?php

namespace App\Services;

use InvalidArgumentException;
use PDO;

class LaptopDatabaseSetup
{
    public function databaseName(array $environment): string
    {
        $host = strtolower(trim((string) ($environment['DB_HOST'] ?? '.\\SQLEXPRESS')));
        if (($environment['DB_CONNECTION'] ?? 'sqlsrv') !== 'sqlsrv'
            || ! in_array($host, ['.\\sqlexpress', 'localhost\\sqlexpress', '(local)\\sqlexpress'], true)
            || ! empty($environment['DB_PORT']) || ! empty($environment['DB_URL'])) {
            throw new InvalidArgumentException('Laptop setup supports only the local SQLEXPRESS instance with an empty DB_PORT and DB_URL. Existing settings were not changed.');
        }
        foreach (['DB_USERNAME', 'DB_PASSWORD'] as $key) {
            if (! in_array(strtolower(trim((string) ($environment[$key] ?? ''))), ['', 'null', '(null)'], true)) {
                throw new InvalidArgumentException('Laptop setup requires Windows authentication (DB_USERNAME=null and DB_PASSWORD=null). Existing credentials were not changed.');
            }
        }
        $name = $environment['DB_DATABASE'] ?? 'it12';
        if (! is_string($name) || ! preg_match('/\A[A-Za-z][A-Za-z0-9_]{0,63}\z/', $name)
            || in_array(strtolower($name), ['master', 'model', 'msdb', 'tempdb'], true)) {
            throw new InvalidArgumentException('Use a separate application database name containing only letters, digits, and underscores.');
        }
        return $name;
    }

    public function prepare(array $environment, string $windowsAccount): void
    {
        $name = $this->databaseName($environment);
        $accountParts = explode('\\', $windowsAccount);
        if (count($accountParts) !== 2 || $accountParts[0] === '' || $accountParts[1] === ''
            || preg_match('/[\x00-\x1f]/', $windowsAccount)) {
            throw new InvalidArgumentException('A valid Windows account is required.');
        }
        // Shared memory connects only to this laptop; no SQL network port is opened.
        $connection = new PDO('sqlsrv:Server=lpc:.\\SQLEXPRESS;Database=master;Encrypt=yes;TrustServerCertificate=yes;ConnectionPooling=0;LoginTimeout=10', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $query = $connection->prepare('SELECT DB_ID(?)');
        $query->execute([$name]);
        if ($query->fetchColumn() === null) $connection->exec('CREATE DATABASE ['.$name.']');
        $identifier = '['.str_replace(']', ']]', $windowsAccount).']';
        $literal = "N'".str_replace("'", "''", $windowsAccount)."'";
        $connection->exec("IF SUSER_ID($literal) IS NULL CREATE LOGIN $identifier FROM WINDOWS");
        $connection->exec('USE ['.$name.']');
        $principalQuery = $connection->prepare('SELECT name FROM sys.database_principals WHERE sid = SUSER_SID(?)');
        $principalQuery->execute([$windowsAccount]);
        $principal = $principalQuery->fetchColumn();
        if ($principal === false) {
            $connection->exec("CREATE USER $identifier FOR LOGIN $identifier");
            $principal = $windowsAccount;
        }
        // The account that created a database is already mapped to dbo.
        if ($principal !== 'dbo') {
            $principalIdentifier = '['.str_replace(']', ']]', $principal).']';
            $principalLiteral = "N'".str_replace("'", "''", $principal)."'";
            $connection->exec("IF IS_ROLEMEMBER(N'db_owner', $principalLiteral) <> 1 ALTER ROLE [db_owner] ADD MEMBER $principalIdentifier");
        }
    }
}
