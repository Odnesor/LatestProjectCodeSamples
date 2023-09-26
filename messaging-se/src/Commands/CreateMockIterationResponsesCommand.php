<?php

namespace MessagingSmartEvaluering\Commands;

use MessagingSmartEvaluering\Contracts\MessagingSendingIterationRepository;
use MessagingSmartEvaluering\Models\MessagingSendingIterationMessage;

class CreateMockIterationResponsesCommand extends \Illuminate\Console\Command
{

    protected $signature = "mock-responses:iteration {iterationId}";

        public function handle(
        MessagingSendingIterationRepository $iterationRepository
    ) {
        $iterationId = $this->argument('iterationId');
        $iterationRepository->defineBasicModelInstance($iterationId);
        $iterationRepository->getMessages()->each(function(MessagingSendingIterationMessage $message) use ($iterationRepository) {
            $iterationRepository->storeMessageResponse($message, rand(1, 5));
        });
    }
}