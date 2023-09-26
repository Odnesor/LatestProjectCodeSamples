<?php

namespace MessagingSmartEvaluering\Commands;


use EmployeesSmartEvaluering\Contracts\Employee\Filters\MessagingFlowFiltersRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use MessagingSmartEvaluering\Contracts\MessagingFlowRepository;
use MessagingSmartEvaluering\Models\MessagingScheduledSendingIteration;
use MessagingSmartEvaluering\Models\MessagingSender;
use MessagingSmartEvaluering\Models\MessagingSendingFlow;
use MessagingSmartEvaluering\Models\MessagingSendingIteration;
use MessagingSmartEvaluering\Models\MessagingSendingIterationMessage;
use SmartEvaluering\Models\Role;
use SmartEvaluering\Models\User;

class CreateMockMessagingFlows extends \Illuminate\Console\Command
{

    protected $signature = "mock-messaging-flows:create";

    public function handle(
        MessagingFlowRepository $flowRepository,
        MessagingFlowFiltersRepository $filtersRepository
    ) {
        MessagingSendingIterationMessage::truncate();
        MessagingSendingIteration::whereNotNull('id')->delete();
        MessagingSendingFlow::whereNotNull('id')->delete();
        MessagingSender::whereNotNull('id')->delete();


        $logs = _MESSAGING_ROOT_ . "/storage/logs/senders";
        File::exists($logs) && File::deleteDirectory($logs);


        $text = "Your opinion matters! Participate in our quick feedback evaluation to improve our services. Tap the link and share your valuable insights.\n\nHello! Help shape our offerings by taking our short evaluation. We value your responses. Click the link to start now.";

        Auth::login(Role::bySlug(Role::SUPER_ADMIN)->users()->orderBy('id')->first());

        for ($i = 0; $i < 5; $i++) {
            /** @var MessagingFlowRepository $flowRepository */
            $flowRepository = app()->make(MessagingFlowRepository::class);
            $flowRepository
                ->setSender()
                ->setFlowName('FlowSample ' . ($i + 1))
                ->setFlowDescription('Some description sample')

                ->addIteration("№1 {$text}", 1)
                ->setDelay(10)

                ->addIteration("№2 {$text}", 2)
                ->setDelay(720)

                ->addIteration("№3 {$text}", 3)
                ->setDelay(30)

                ->addIteration("№4 {$text}", 4)
                ->setDelay(1440 * 7);

            ($flowRepository)();

            $filtersRepository->syncFlowEmployeesFilters($flowRepository->getModel()->id);

        }
//        dump(MessagingScheduledSendingIteration::all()->toArray());
    }
}