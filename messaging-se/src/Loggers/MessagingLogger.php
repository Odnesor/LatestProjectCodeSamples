<?php

namespace MessagingSmartEvaluering\Loggers;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

abstract class MessagingLogger
{
    /** @var Model */
    private $instance;
    private $instanceId;
    protected $dirName = 'general';
    protected $fileName = 'log';
    /** @var self */
    protected $parentLogger;

    /**
     * @param $instanceToLog
     * @param MessagingLogger $parentLogger
     */
    public function __construct($instanceToLog)
    {
        is_object($instanceToLog) && get_class($instanceToLog) === $this->getInstanceModelClass()
            ? ($this->instanceId = ($this->instance = $instanceToLog)->id)
            : ($this->instanceId = $instanceToLog);

    }


    /**
     * @return string
     */
    abstract public function getInstanceModelClass(): string;

    /**
     * @return Model
     */
    final protected function getInstance(): Model
    {
        return $this->instance ?? ($this->instance = ($this->getInstanceModelClass())::find($this->instanceId));
    }

    final public function getInstanceId(): int
    {
        return $this->instanceId;
    }

    public function setParentLogger(self $parentLogger): self
    {
        $this->parentLogger = $parentLogger;
        return $this;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return "{$this->getPathPrefix()}/{$this->dirName}/{$this->instanceId}";
    }

    private function getPathPrefix(): string
    {
        return $this->parentLogger ? $this->parentLogger->getPath() : _MESSAGING_ROOT_ . "/storage/logs";
    }

    public function log($data = null, string $fileName = null): string
    {
        !File::exists($this->getPath()) && File::makeDirectory($this->getPath(), 0777, true);

        $fileName = $fileName ?? $this->fileName;
        $path = "{$this->getPath()}/{$fileName}.log";

        $content = print_r($data ?? call_user_func(function () {
                $this->getInstance();
                $this->instance->makeHidden(array_keys($this->instance->getRelations()));
                $output = $this->instance->toArray();
                $this->instance->makeHidden([]);
                return $output;
            }), true);
        $timestamp = Carbon::now()->toString();
        File::append($path, "\n{$timestamp}\n{$content}");

        return $path;
    }
}