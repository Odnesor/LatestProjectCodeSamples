<?php

namespace MessagingSmartEvaluering\Commands;


use MessagingSmartEvaluering\Contracts\MessagingSendingIterationRepository;

class SendMessagingIterationMessagesCommand extends \Illuminate\Console\Command
{

    protected $signature = "messaging-iteration:send {iterationId}";

    public function handle(
        MessagingSendingIterationRepository $iterationRepository
    ) {
        $iterationRepository->defineBasicModelInstance((int)$this->argument('iterationId'));
        
        $iterationRepository->send();
    }
}