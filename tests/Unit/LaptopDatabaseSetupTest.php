<?php

namespace Tests\Unit;

use App\Services\LaptopDatabaseSetup;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LaptopDatabaseSetupTest extends TestCase
{
    public function test_local_windows_authenticated_database_is_accepted(): void
    {
        $this->assertSame('it12', (new LaptopDatabaseSetup)->databaseName([]));
        $this->assertSame('Store_2026', (new LaptopDatabaseSetup)->databaseName([
            'DB_HOST' => 'localhost\\SQLEXPRESS', 'DB_DATABASE' => 'Store_2026',
            'DB_USERNAME' => 'null', 'DB_PASSWORD' => '(null)',
        ]));
    }

    public static function unsafeTargets(): array
    {
        return [
            'remote server' => [['DB_HOST' => 'remote\\SQLEXPRESS']],
            'different driver' => [['DB_CONNECTION' => 'mysql']],
            'URL override' => [['DB_URL' => 'sqlsrv://remote/records']],
            'port override' => [['DB_PORT' => '1433']],
            'SQL login' => [['DB_USERNAME' => 'sa']],
            'SQL password' => [['DB_PASSWORD' => 'secret']],
            'system database' => [['DB_DATABASE' => 'master']],
            'SQL identifier injection' => [['DB_DATABASE' => 'it12]; DROP DATABASE other;--']],
            'empty name' => [['DB_DATABASE' => '']],
        ];
    }

    #[DataProvider('unsafeTargets')]
    public function test_unsafe_database_targets_are_rejected_before_connection(array $environment): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new LaptopDatabaseSetup)->databaseName($environment);
    }

    public function test_invalid_windows_identity_is_rejected_before_connection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new LaptopDatabaseSetup)->prepare([], "malformed\nidentity");
    }
}
