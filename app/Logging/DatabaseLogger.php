<?php

namespace App\Logging;

use App\Models\SystemLog;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

class DatabaseLogger extends AbstractProcessingHandler
{
    /**
     * Create a new DatabaseLogger instance.
     */
    public function __construct(array $config = [])
    {
        $level = $config['level'] ?? Level::Debug;
        $bubble = $config['bubble'] ?? true;

        parent::__construct($level, $bubble);
    }

    /**
     * Write the log record to the database.
     */
    protected function write(LogRecord $record): void
    {
        // Convert Monolog level to our type
        $type = match ($record->level->getName()) {
            'INFO' => 'info',
            'NOTICE' => 'info',
            'WARNING' => 'warning',
            'ERROR' => 'error',
            'CRITICAL' => 'error',
            'ALERT' => 'error',
            'EMERGENCY' => 'error',
            'DEBUG' => 'debug',
            default => 'info',
        };

        SystemLog::create([
            'type' => $type,
            'message' => $record->message,
            'context' => $record->context,
            'source' => 'monolog',
        ]);
    }
}
