<?php

namespace MessagingSmartEvaluering\Commands;


use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use MessagingSmartEvaluering\Contracts\MessagingFlowRepository;
use MessagingSmartEvaluering\Models\MessagingScheduledSendingIteration;
use MessagingSmartEvaluering\Models\MessagingSender;
use MessagingSmartEvaluering\Models\MessagingSendingFlow;
use MessagingSmartEvaluering\Models\MessagingSendingIteration;

class SendReadyMessagingFlowsIterationsCommand extends \Illuminate\Console\Command
{

    protected $signature = "ready-messaging-iterations:send";

    public function handle(
        MessagingFlowRepository $flowRepository
    )
    {
        MessagingScheduledSendingIteration
            ::whereHas('flow', function($query) {
                $query->where('is_active', true);
            })
            ->where('scheduled_time', '<', Carbon::now())
            ->select('iteration_id', 'flow_id', 'scheduled_time', 'order', 'sent_at')
            ->whereNull('sent_at')
            ->with('iteration')->get()
            ->groupBy('flow_id')->each(function (Collection $pendingSchedulesByFlow) {
                $iterationId = $pendingSchedulesByFlow->where('order', $pendingSchedulesByFlow->min('order'))->first()->iteration_id;

                Artisan::call('messaging-iteration:send', compact('iterationId'));
            });
    }
}