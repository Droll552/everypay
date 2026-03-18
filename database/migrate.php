<?php

declare(strict_types=1);

/**
 * Run database migrations.
 * Creates the schema if it does not exist and seeds initial merchant data.
 *
 * Usage: php database/migrate.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config/app.php';

$pdo = new PDO($config['db_dsn'], options: [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

echo "Running migrations...\n";

$pdo->exec("
    CREATE TABLE IF NOT EXISTS merchants (
        id       TEXT PRIMARY KEY,
        name     TEXT NOT NULL,
        email    TEXT NOT NULL,
        api_key  TEXT NOT NULL UNIQUE,
        psp_type TEXT NOT NULL
    );
");

$pdo->exec("
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

echo "Schema ready.\n";

// Seed two test merchants (idempotent).
$stmt = $pdo->prepare("
    INSERT OR IGNORE INTO merchants (id, name, email, api_key, psp_type)
    VALUES (:id, :name, :email, :api_key, :psp_type)
");

$merchants = [
    [
        'id' => 'merchant-stripe-1',
        'name' => 'Acme Corp',
        'email' => 'billing@acme.example.com',
        'api_key' => 'test_stripe_key_abc123',
        'psp_type' => 'fake_stripe',
    ],
    [
        'id' => 'merchant-paypal-1',
        'name' => 'GlobalShop',
        'email' => 'finance@globalshop.example.com',
        'api_key' => 'test_paypal_key_xyz789',
        'psp_type' => 'fake_paypal',
    ],
];

foreach ($merchants as $m) {
    $stmt->execute($m);
    echo sprintf("Seeded merchant: %s (%s)\n", $m['name'], $m['id']);
}

echo "Done.\n";
