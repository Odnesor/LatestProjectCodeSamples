<?php

namespace MessagingSmartEvaluering\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use MessagingSmartEvaluering\Contracts\MessagingSendingIterationRepository;
use MessagingSmartEvaluering\Models\MessagingSendingIteration;
use MessagingSmartEvaluering\Models\MessagingSendingIterationMessage;

class SendMessagingIteration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var MessagingSendingIteration */
    protected $iteration;

    /** @var MessagingSendingIterationRepository */
    private $iterationRepository;

    public $tries = 2;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(MessagingSendingIteration $iteration)
    {
        $this->iteration = $iteration;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(MessagingSendingIterationRepository $iterationRepository)
    {
        $iterationRepository->defineBasicModelInstance($this->iteration);
        $iterationRepository->logIterationProcessing();

        if ($iterationRepository->getMessages()->count() === 0)
            $iterationRepository->finish();

        $iterationRepository->getMessages()->where('is_sent', false)->chunk(5)->each(function (Collection $messagesChunk) use ($iterationRepository) {
            SendMessagingIterationMessages::dispatch($this->iteration, $messagesChunk->pluck('id')->toArray());
        });
        return true;
    }

    public function failed(\Exception $ex)
    {
        $this->delete();
    }
}
