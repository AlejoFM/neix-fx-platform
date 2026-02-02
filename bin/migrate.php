<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Infrastructure\Database\DatabaseConnection;

$root = __DIR__ . '/../';
try {
    Dotenv::createImmutable($root, '.env')->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    Dotenv::createImmutable($root . '.env', '.env')->load();
}

echo "Ejecutando migraciones...\n";

try {
    $db = DatabaseConnection::getInstance();
    echo "✓ Conexión a base de datos establecida\n";
    
    // Las tablas ya se crean automáticamente con el init.sql de Docker
    // Este script puede usarse para migraciones futuras
    
    echo "✓ Migraciones completadas\n";
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
