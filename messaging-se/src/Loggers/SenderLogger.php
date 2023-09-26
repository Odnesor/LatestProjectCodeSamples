<?php

namespace MessagingSmartEvaluering\Loggers;

use Illuminate\Database\Eloquent\Model;
use MessagingSmartEvaluering\Models\MessagingSender;

class SenderLogger extends MessagingLogger
{
    protected $dirName = 'senders';
    protected $fileName = 'general-sender-data';

    /**
     * @inheritDoc
     */
    public function getInstanceModelClass(): string
    {
        return MessagingSender::class;
    }
}