<?php

namespace MessagingSmartEvaluering\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use MessagingSmartEvaluering\Contracts\BasicModelRepositoryContract;
use \MessagingSmartEvaluering\Contracts\MessagingSendingIterationRepository as Contract;
use MessagingSmartEvaluering\Contracts\SmartEvalueringSenderContract;
use MessagingSmartEvaluering\Jobs\SendMessagingIteration;
use MessagingSmartEvaluering\Models\MessagingScheduledSendingIteration;
use MessagingSmartEvaluering\Models\MessagingSendingIteration;
use MessagingSmartEvaluering\Models\MessagingSendingIteration as Model;
use MessagingSmartEvaluering\Models\MessagingSendingIterationMessage;

/**
 * @property Model $model
 */
class MessagingSendingIterationRepository extends BasicModelRepository implements Contract
{
    public function defineBasicModelInstance($iteration): Contract
    {
        $this->model = $iteration instanceof MessagingSendingIteration ? $iteration : MessagingSendingIteration::find($iteration);
//        dd($this->model->getFlow());
        return $this;
    }

    public function getModelClass(): string
    {
        return Model::class;
    }

    /**
     * @inheritDoc
     */
    public function setText(string $text): Contract
    {
        $this->model->text = $text;
        return $this;
    }

    public function setSender($sender): Contract
    {
        $this->model->sender()->associate($sender);
        return $this;
    }

    public function update(array $data): Contract
    {
        MessagingSendingIteration::where('id', $data['id'])->update(
            collect($data)->only((new MessagingSendingIteration())->getFillable())->toArray()
        );

        return $this;
    }

    public function addRecipients($phoneNumbers): Contract
    {
        $phoneNumbers = Collection::wrap(is_string($phoneNumbers) ? [$phoneNumbers] : $phoneNumbers);
        $this->model->messages()->saveMany(
            $phoneNumbers->map(function ($number) {
                return new MessagingSendingIterationMessage(['phone' => $number]);
            })
        );

        return $this;
    }

    public function getPreviousIterationRepository()
    {
        /** @var \MessagingSmartEvaluering\Contracts\MessagingFlowRepository $flowRepository */
        $flowRepository = app()->make(\MessagingSmartEvaluering\Contracts\MessagingFlowRepository::class);
        $flowRepository->instantiate($this->model->getFlow());

        return ($previousSchedule = $flowRepository->findSchedule($this->model->getSchedule())->prev) instanceof MessagingScheduledSendingIteration
            ? call_user_func(function ($iteration) {
                /** @var Contract $previousIterationRepository */
                $previousIterationRepository = app()->make(self::class);
                $previousIterationRepository->defineBasicModelInstance($iteration);

                return $previousIterationRepository;
            }, $previousSchedule->iteration)
            : null;
    }

    public function getMessages(): Collection
    {
        return ($messages = $this->model->messages)->isNotEmpty() ? $messages : call_user_func(function () {
            $prevIterationRepository = $this->getPreviousIterationRepository();
            if (!isset($prevIterationRepository)) return collect([]);

            isset($prevIterationRepository) && $this->addRecipients($prevIterationRepository->getMessages()->pluck('phone'));
            $this->model->refresh();

            return $this->model->messages;
        });
    }

    public function send(): void
    {
        (new SendMessagingIteration($this->model))->handle(app()->make(\MessagingSmartEvaluering\Contracts\MessagingSendingIterationRepository::class));
    }

    public function logIterationProcessing(): void
    {
        if (!isset($this->model->id)) return;
        $this->model->getLogger()->logIterationProcessing();
    }

    /**
     * @param array|Collection| null $messages
     * @return void
     */
    public function logIterationMessages($messages = null): void
    {
        $messages = isset($messages) ? Collection::wrap($messages) : $this->getMessages();


        $logText =
            "[MESSAGES]:\n" .
            $messages->map(function (MessagingSendingIterationMessage $message) {
                return preg_replace_callback_array([
                    '/\{phone\}/'     => function () use ($message) {
                        return $message->phone;
                    },
                    '/\{is_sent\}/'   => function () use ($message) {
                        return $message->is_sent ? 'sent' : 'pending';
                    },
                    '/\{sent_time\}/' => function () use ($message) {
                        return Carbon::now()->toString();
                    },
                ], "[{is_sent}]{phone}  @{sent_time}");
            })->implode(PHP_EOL);

        $this->log($logText);
    }

    public function log($data = null): void
    {
        $this->model->getLogger()->log($data);
    }

    public function checkPresence(string $heap, string $search)
    {
        if ($search === "") return true;
        if ($heap === "") return false;


        $letterNotFoundIndex = (collect(str_split($search))->search(function ($letterToSearch) use ($heap) {
            $letterIndexInHeap = collect(str_split($heap))->search(function ($heapLetter) use ($letterToSearch) {
                return $heapLetter === $letterToSearch;
            });

            return $letterIndexInHeap === false;
        }));

        return $letterNotFoundIndex === false;
    }

    public function finish(): void
    {
        if (!isset($this->model)) return;
        $this->model->getSchedule()->update(['sent_at' => Carbon::now()]);
        $this->model->is_finished = true;
        $this->model->save();
    }

    public function storeMessageResponse(MessagingSendingIterationMessage $message, string $text): Contract
    {
        $message->update([
            'response'   => $text,
            'respond_at' => Carbon::now()
        ]);

        app(SmartEvalueringSenderContract::class)::messageResponseReceived(
            $message->iteration->sender->id,
            $message->iteration->id,
            $message->phone,
            $message->response,
            $message->respond_at
        );

        return $this;
    }
}