<?php

namespace Application\Service;

use Throwable;
use Monolog\Level;
use Monolog\Logger as MonologLogger;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\RotatingFileHandler;

/**
 * Application logger. Writes daily files to data/logs/app-YYYY-MM-DD.log and
 * keeps 30 days. Falls back to PHP's error_log() when data/logs is not
 * writable. Never throws: logging must not break the request that asked for it.
 *
 * Usage:
 *   $logger = $serviceManager->get('Logger');
 *   $logger->error('message', ['user' => $userId]);
 *   $logger->logException($throwable, 'Reference ab12cd34');
 */
final class Logger
{
    private const RETENTION_DAYS = 30;
    private const MAX_MESSAGE_LENGTH = 8000;

    private ?MonologLogger $logger = null;
    private string $logDir;
    private string $channel;

    public function __construct(?string $logDir = null, string $channel = 'app')
    {
        $this->logDir = $logDir ?? dirname(__DIR__, 5) . '/data/logs';
        $this->channel = $channel;
    }

    public function info(string $message, array $context = []): void
    {
        $this->log(Level::Info, $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log(Level::Warning, $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log(Level::Error, $message, $context);
    }

    /**
     * Log a Throwable with its class, message, file:line, trace and the chain of
     * previous exceptions.
     */
    public function logException(Throwable $e, string $prefix = 'Unhandled exception'): void
    {
        $previous = [];
        for ($p = $e->getPrevious(), $i = 0; $p !== null && $i < 10; $p = $p->getPrevious(), $i++) {
            $previous[] = $p::class . ': ' . $p->getMessage() . ' at ' . $p->getFile() . ':' . $p->getLine();
        }
        $this->log(
            Level::Error,
            $prefix . ': ' . $e::class . ': ' . $e->getMessage(),
            [
                'file' => $e->getFile() . ':' . $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'previous' => $previous,
            ]
        );
    }

    private function log(Level $level, string $message, array $context): void
    {
        try {
            if (strlen($message) > self::MAX_MESSAGE_LENGTH) {
                $message = substr($message, 0, self::MAX_MESSAGE_LENGTH) . '... [truncated]';
            }
            $this->getLogger()->log($level, $message, $context);
        } catch (Throwable $e) {
            // The file handler failed, for example on a log file owned by another
            // account. Keep the context too: for an exception it holds the trace.
            try {
                $details = (string) json_encode($context, JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
            } catch (Throwable) {
                $details = '[context could not be encoded]';
            }
            @error_log('Logger::log failed: ' . $e->getMessage() . ' | ' . $level->getName() . ': ' . $message . ' ' . $details);
        }
    }

    private function getLogger(): MonologLogger
    {
        if ($this->logger instanceof MonologLogger) {
            return $this->logger;
        }

        $this->logger = new MonologLogger($this->channel);
        try {
            if (!is_dir($this->logDir)) {
                @mkdir($this->logDir, 0775, true);
            }
            if (is_dir($this->logDir) && is_writable($this->logDir)) {
                $handler = new RotatingFileHandler(
                    "{$this->logDir}/{$this->channel}.log",
                    self::RETENTION_DAYS,
                    Level::Info,
                    true,
                    0664
                );
                $handler->setFilenameFormat('{filename}-{date}', 'Y-m-d');
                $this->logger->pushHandler($handler);
            }
        } catch (Throwable $e) {
            @error_log('Logger init failed: ' . $e->getMessage());
        }

        // Always keep one handler, so log() has somewhere to write
        if ($this->logger->getHandlers() === []) {
            $this->logger->pushHandler(new ErrorLogHandler(ErrorLogHandler::OPERATING_SYSTEM, Level::Warning));
        }

        return $this->logger;
    }
}
