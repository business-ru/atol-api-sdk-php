<?php

namespace Atol\Api\Adapter\IlluminateAtolApi\Log;

use Atol\Api\Adapter\IlluminateAtolApi\Interfaces\LogInterface;
use Atol\Api\Exception\SimpleLogException;
use Monolog\DateTimeImmutable;
use Monolog\Formatter\LogstashFormatter;
use Monolog\Level;
use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\LogRecord;
use Psr\Log\InvalidArgumentException;

class Logger implements LogInterface
{
    /**
     * @var MonologLogger
     */
    private MonologLogger $logger;

    /**
     * Директория логов
     * @var string
     */
    private string $logPath = __DIR__  . '/../../../../logs';

    /**
     * The Log levels.
     *
     * @var array
     */
    protected array $levels = [
        'debug' => Level::Debug,
        'info' => Level::Info,
        'notice' => Level::Notice,
        'warning' => Level::Warning,
        'error' => Level::Error,
        'critical' => Level::Critical,
        'alert' => Level::Alert,
        'emergency' => Level::Emergency,
    ];

    /**
     * Parse the string level into a Monolog constant.
     *
     * @param array $config
     * @return int
     *
     * @throws InvalidArgumentException
     */
    protected function level(array $config): int
    {
        $level = $config['level'] ?? 'debug';

        if (isset($this->levels[$level])) {
            return $this->levels[$level]->value;
        }

        throw new InvalidArgumentException('Invalid log level.');
    }

    public function __construct()
    {
        if (!is_dir($this->logPath)
            && !mkdir($concurrentDirectory = $this->logPath)
            && !is_dir($concurrentDirectory)) {
            throw new SimpleLogException('Невозможно создать директорию для хранения логов /src/logs/');
        }
        $this->logger = new MonologLogger('AtolApiSDK');
    }

    public function info(string $message, array $context = []): void
    {
        $this->writeLog('info', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->writeLog('error', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->writeLog('warning', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->writeLog('critical', $message, $context);
    }

    private function writeLog(string $level, string $message, array $context = []): void
    {
        $logger = $this->logger;

        $formatter = new LogstashFormatter('AtolApiSDK');
        $record = new LogRecord(
            datetime: new DateTimeImmutable(true),
            channel: 'daily',
            level: MonologLogger::toMonologLevel($level),
            message: $message,
            context: $context
        );
        $formatter->format($record);
        $logFileDaily = $level . '-' . date("Y-m-d");
        $handler = new StreamHandler($this->logPath . "/$logFileDaily.log", $this->level(['level' => $level]));
        $handler->setFormatter($formatter);

        $logger->pushHandler($handler);
        $logger->$level($message, $context);
    }
}
