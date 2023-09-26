<?php

namespace MessagingSmartEvaluering\Commands;

use Illuminate\Support\Facades\Artisan;
use MessagingSmartEvaluering\Contracts\MessagingFlowRepository;
use MessagingSmartEvaluering\Contracts\MessagingSendingIterationRepository;
use MessagingSmartEvaluering\Models\MessagingSendingIterationMessage;

class CreateMockFlowResponsesCommand extends \Illuminate\Console\Command
{

    protected $signature = "mock-responses:flow {flowId}";

    public function handle(
        MessagingFlowRepository $flowRepository
    ) {
        $flowHead = $flowRepository
            ->instantiate((int)$this->argument('flowId'))
            ->getHead();
        $current = $flowHead;

        do {
            Artisan::call("mock-responses:iteration", ['iterationId' => $current->iteration_id]);
        } while (($current = $current->next) !== null);
    }
}