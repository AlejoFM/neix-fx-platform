<?php

namespace App\Infrastructure\Logger;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\MemoryUsageProcessor;

class LoggerFactory
{
    private static array $loggers = [];
    private static ?string $logPath = null;

    public static function getLogger(string $context): Logger
    {
        if (!isset(self::$loggers[$context])) {
            if (self::$logPath === null) {
                $basePath = $_ENV['LOG_PATH'] ?? __DIR__ . '/../../../logs';
                self::$logPath = is_dir($basePath) ? realpath($basePath) : $basePath;
            }
            if (!is_dir(self::$logPath)) {
                @mkdir(self::$logPath, 0755, true);
            }
            self::$loggers[$context] = self::createLogger($context);
        }

        return self::$loggers[$context];
    }

    private static function createLogger(string $context): Logger
    {
        $logger = new Logger($context);
        $logLevel = self::getLogLevel();

        // Ruta absoluta del directorio de este contexto
        $contextPath = rtrim(self::$logPath, DIRECTORY_SEPARATOR) . '/' . $context;
        if (!is_dir($contextPath)) {
            @mkdir($contextPath, 0755, true);
        }

        $logFile = $contextPath . '/' . date('Y-m-d') . '.log';

        // Handler para archivo con formato JSON (incluye contexto completo)
        $fileHandler = new StreamHandler($logFile, $logLevel);
        $fileHandler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_NEWLINES, true, true));

        $logger->pushHandler($fileHandler);

        // Handler para stderr solo para ERROR y superiores (visible en Docker/consola)
        $stderrHandler = new StreamHandler('php://stderr', Logger::ERROR);
        $stderrHandler->setFormatter(new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s'
        ));
        $logger->pushHandler($stderrHandler);

        $logger->pushProcessor(new UidProcessor());
        $logger->pushProcessor(new MemoryUsageProcessor());

        return $logger;
    }

    private static function getLogLevel(): int
    {
        $level = strtoupper($_ENV['LOG_LEVEL'] ?? 'DEBUG');
        return match ($level) {
            'DEBUG' => Logger::DEBUG,
            'INFO' => Logger::INFO,
            'WARNING' => Logger::WARNING,
            'ERROR' => Logger::ERROR,
            default => Logger::DEBUG,
        };
    }
}
