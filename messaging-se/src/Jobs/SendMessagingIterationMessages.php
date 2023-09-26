<?php

namespace MessagingSmartEvaluering\Jobs;

use App\Inmobile\MM_Connector;
use App\Inmobile\MM_Message;
use Carbon\Carbon;
use Helpers\InMobileResponseCode;
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

class SendMessagingIterationMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var array */
    protected $messagesIds;

    /** @var MessagingSendingIteration */
    protected $iteration;

    public $tries = 2;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(MessagingSendingIteration $iteration, array $messagesIds)
    {
        $this->iteration = $iteration;
        $this->messagesIds = $messagesIds;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(MessagingSendingIterationRepository $iterationRepository)
    {
        $iterationRepository->defineBasicModelInstance($this->iteration);
        $iterationRepository->log('[SENDING MESSAGES]');

        $messages = MessagingSendingIterationMessage::whereIn('id', $this->messagesIds)->get();

        $gateway = $messages
            ->reduce(function (MM_Connector $gateway, MessagingSendingIterationMessage $message): MM_Connector {
                $gateway->addMessage(
                    new MM_Message(
                        $this->iteration->text,
                        [$message->phone],
                        config('smartevaluering.inmobile.virtual_number')
                    )
                );
                return $gateway;
            }, new MM_Connector(config('smartevaluering.inmobile.api_key'), 'https://mm.inmobile.dk'));

        if (($success = $gateway->send())) {
            MessagingSendingIterationMessage::whereIn('id', $this->messagesIds)->update(['is_sent' => true]);
            if ($this->iteration->messages()->where('is_sent', false)->count() === 0)
                $iterationRepository->finish();
        } else {
            $iterationRepository->log("ERROR|SMS");
            $iterationRepository->log([
                'apikey'        => config('smartevaluering.inmobile.api_key'),
                'xml'           => $gateway->getXml()->asXML(),
                'error_code'    => $gateway->getResponse(),
                'error_message' => InMobileResponseCode::getErrorMessageByCode($gateway->getResponse()),
            ]);
            throw new \Exception($gateway->getError());
        }


        $iterationRepository->logIterationMessages($messages);
        return true;
    }

    public function failed(\Exception $ex)
    {
        $this->delete();
    }
}
