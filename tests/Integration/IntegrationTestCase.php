<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Infrastructure\Container;
use PDO;
use PHPUnit\Framework\TestCase;

abstract class IntegrationTestCase extends TestCase
{
    protected Container $container;
    protected PDO $pdo;
    protected string $mailLogPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Unique log file per test — prevents cross-test log pollution.
        $this->mailLogPath = sys_get_temp_dir() . '/test_mail_' . uniqid('', true) . '.log';

        // Use an in-memory SQLite database for each test run.
        $config = [
            'db_dsn' => 'sqlite::memory:',
            'mail_log_path' => $this->mailLogPath,
        ];

        $this->container = new Container($config);
        $this->pdo = $this->container->getPdo();

        $this->runMigrations();
        $this->seedMerchants();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (file_exists($this->mailLogPath)) {
            unlink($this->mailLogPath);
        }
    }

    protected function readMailLog(): string
    {
        if (!file_exists($this->mailLogPath)) {
            return '';
        }

        return (string) file_get_contents($this->mailLogPath);
    }

    private function runMigrations(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS merchants (
                id       TEXT PRIMARY KEY,
                name     TEXT NOT NULL,
                email    TEXT NOT NULL,
                api_key  TEXT NOT NULL UNIQUE,
                psp_type TEXT NOT NULL
            );
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS charges (
                id            TEXT PRIMARY KEY,
                merchant_id   TEXT NOT NULL,
                amount        INTEGER NOT NULL,
                currency      TEXT NOT NULL,
                status        TEXT NOT NULL,
                psp_reference TEXT NOT NULL,
                created_at    TEXT NOT NULL,
                FOREIGN KEY (merchant_id) REFERENCES merchants(id)
            );
        ");
    }

    private function seedMerchants(): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO merchants (id, name, email, api_key, psp_type)
             VALUES (:id, :name, :email, :api_key, :psp_type)'
        );

        $stmt->execute([
            'id' => 'merchant-stripe-1',
            'name' => 'Acme Corp',
            'email' => 'billing@acme.example.com',
            'api_key' => 'test_stripe_key_abc123',
            'psp_type' => 'fake_stripe',
        ]);

        $stmt->execute([
            'id' => 'merchant-paypal-1',
            'name' => 'GlobalShop',
            'email' => 'finance@globalshop.example.com',
            'api_key' => 'test_paypal_key_xyz789',
            'psp_type' => 'fake_paypal',
        ]);
    }
}
