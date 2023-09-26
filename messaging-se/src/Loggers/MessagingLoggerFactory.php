<?php

namespace MessagingSmartEvaluering\Loggers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class MessagingLoggerFactory
{
    /** @var Collection */
    private $existingLoggers;

    public function __construct()
    {
        $this->existingLoggers = new Collection();
    }

    /**
     * @param string $loggerClass
     * @param Model|int $instanceToLog
     * @return MessagingLogger
     */
    public function getUniqueLogger(string $loggerClass, $instanceToLog): MessagingLogger
    {
        $instanceToLogId = $instanceToLog instanceof Model ? $instanceToLog->id : $instanceToLog;

        if (($logger = $this->existingLoggers->first(function (array $existingLogger) use ($loggerClass, $instanceToLogId) {
                return get_class($existingLogger['loggerInstance']) === $loggerClass
                    && $existingLogger['instanceToLogId'] === $instanceToLogId;
            })) !== null) {
            return $logger['loggerInstance'];
        }

        $logger = new $loggerClass($instanceToLog);
        $this->existingLoggers->push([
            'loggerInstance'  => $logger,
            'instanceToLogId' => $instanceToLogId
        ]);
        return $logger;
    }


    /**
     * @param string $loggerClass
     * @param Model|int $instanceToLog
     * @return MessagingLogger
     */
    public static function getLogger(string $loggerClass, $instanceToLog): MessagingLogger
    {
        /** @var self $factory */
        $factory = app()->make(self::class);
        return $factory->getUniqueLogger($loggerClass, $instanceToLog);
    }
}