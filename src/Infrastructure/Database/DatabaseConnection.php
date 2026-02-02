<?php

namespace App\Infrastructure\Database;

use PDO;
use PDOException;
use Dotenv\Dotenv;

class DatabaseConnection
{
    private static ?PDO $instance = null;
    private static array $config = [];

    private function __construct()
    {
        // Private constructor for singleton
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::loadConfig();
            self::$instance = self::createConnection();
        }

        return self::$instance;
    }

    private static function loadConfig(): void
    {
        $root = __DIR__ . '/../../../';
        try {
            Dotenv::createImmutable($root, '.env')->load();
        } catch (\Dotenv\Exception\InvalidPathException $e) {
            Dotenv::createImmutable($root . '.env', '.env')->load();
        }

        self::$config = [
            'host' => $_ENV['DB_HOST'] ?? 'db',
            'dbname' => $_ENV['DB_NAME'] ?? 'fx_platform',
            'user' => $_ENV['DB_USER'] ?? 'fx_user',
            'password' => $_ENV['DB_PASS'] ?? 'fx_password',
            'port' => $_ENV['DB_PORT'] ?? '3306',
        ];
    }

    private static function createConnection(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            self::$config['host'],
            self::$config['port'],
            self::$config['dbname']
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            return new PDO(
                $dsn,
                self::$config['user'],
                self::$config['password'],
                $options
            );
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new \RuntimeException("No se pudo conectar a la base de datos", 0, $e);
        }
    }

    public static function reset(): void
    {
        self::$instance = null;
    }
}
