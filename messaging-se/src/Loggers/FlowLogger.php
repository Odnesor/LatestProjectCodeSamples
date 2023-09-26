<?php

namespace MessagingSmartEvaluering\Loggers;

use Illuminate\Database\Eloquent\Model;
use MessagingSmartEvaluering\Models\MessagingSendingFlow;

class FlowLogger extends MessagingLogger
{
    protected $dirName = 'flows';

    /**
     * @inheritDoc
     */
    public function getInstanceModelClass(): string
    {
        return MessagingSendingFlow::class;
    }
}