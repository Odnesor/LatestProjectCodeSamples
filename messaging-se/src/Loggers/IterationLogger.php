<?php

namespace MessagingSmartEvaluering\Loggers;

use Illuminate\Database\Eloquent\Model;
use MessagingSmartEvaluering\Models\MessagingSendingIteration;

class IterationLogger extends FlowLogger
{
    protected $dirName = 'iterations';

    public function getInstanceModelClass(): string
    {
        return MessagingSendingIteration::class;
    }

    public function logIterationProcessing()
    {
        $this->parentLogger->log("Iteration {$this->getInstance()->id} processing");
        $this->log('Iteration processing ' . $this->getInstance()->id);
        $this->log();
    }
}