<?php

namespace MessagingSmartEvaluering\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use MessagingSmartEvaluering\Contracts\MessagingFlowRepository;
use MessagingSmartEvaluering\Contracts\MessagingSendingIterationRepository;
use MessagingSmartEvaluering\Exceptions\SenderException;
use MessagingSmartEvaluering\Models\MessagingScheduledSendingIteration;
use MessagingSmartEvaluering\Models\MessagingSender;
use \MessagingSmartEvaluering\Contracts\SmartEvalueringSenderContract as Contract;
use MessagingSmartEvaluering\Models\MessagingSendingFlow;
use MessagingSmartEvaluering\Models\MessagingSendingIteration;

abstract class SmartEvalueringSender implements Contract
{
    /**
     * @var MessagingSender
     */
    private $sender;

    abstract public function getSenderUID(): string;

    abstract public static function messageResponseReceived(string $senderId, int $iterationId, string $phone, string $text): void;

    public function getSender(): MessagingSender
    {
        return $this->sender ?? ($this->sender = MessagingSender::find($this->getSenderUID()));
    }

    public function getFlowSetter($flow): MessagingFlowRepository
    {
        /** @var MessagingFlowRepository $flowRepository */
        $flowRepository = app()->make(MessagingFlowRepository::class);

        return $flowRepository->setSender($this->sender)->instantiate($flow);
    }

    public function updateIteration($flow, array $iterationData): array
    {
        $setter = $this->getFlowSetter($flow)->updateIteration($iterationData);
        return ['flowHead' => $setter->getHead()];
    }

    public function updateSchedule($flow, array $scheduleData): array
    {
        $setter = $this->getFlowSetter($flow);
        if (count(array_keys($scheduleData)) && array_keys($scheduleData)[0] === 'id') {
            $setter->deleteSchedule($scheduleData['id']);
        } else {
            $setter->updateSchedule($scheduleData);
        }
        return ['flowHead' => $setter->getHead()];
    }

    public function updateFlowData($flow, array $flowData): MessagingSendingFlow
    {
        $setter = $this->getFlowSetter($flow);
        isset($flowData['name']) && $setter->setFlowName($flowData['name']);
        isset($flowData['description']) && $setter->setFlowDescription($flowData['description']);
        isset($flowData['startDate']) && $setter->setStartDate($flowData['startDate']);
        isset($flowData['is_active']) && $setter->setActive($flowData['is_active']);
        return ($setter)();
    }

    public function setIterationRecipients(int $iterationId, array $recipientsNumbers): Collection
    {
        /** @var MessagingSendingIteration $iteration */
        if (($iteration = MessagingSendingIteration::find($iterationId)) == null || $iteration->sender_id !== $this->getSender()->id) {
            throw new SenderException('wrong id!');
        }


        /** @var MessagingSendingIterationRepository $iterationsRepository */
        $iterationsRepository = app()->make(MessagingSendingIterationRepository::class);
        $iterationsRepository->defineBasicModelInstance($iteration)->addRecipients($recipientsNumbers);

        return $iterationsRepository->getMessages();
    }

    public function getFlows(): Collection
    {
        return $this->getSender()->flows;
    }

    public function getCompleteFlowData(int $flowId): array
    {
        /** @var MessagingFlowRepository $flowRepository */
        $flowRepository = app()->make(MessagingFlowRepository::class);

        $flowRepository->setSender($this->sender)->instantiate($flowId);

        return [
            'flowData' => $flowRepository->getModel()->load('schedules.iteration'),
            'flowHead' => $flowRepository->getHead()
        ];
    }

    public function syncFlowRecipients(int $flowId, array $recipientsNumbers): void
    {
        $this->getFlowSetter($flowId)->syncIterationsRecipients($recipientsNumbers);
    }

    public function deleteFlow(int $flowId): void
    {
        $this->getSender()->flows()->where('id', $flowId)->delete();
    }

    public function getBlankFlow(): MessagingSendingFlow
    {
        /** @var MessagingFlowRepository $flowRepository */
        $flowRepository = app()->make(MessagingFlowRepository::class);

        $defaultTitle = "New messaging flow";
        $defaultDescription = "Some default flow description";
        $defaultMessageText = "Your opinion matters! Participate in our quick feedback evaluation to improve our services.";

        $flowRepository
            ->setSender()
            ->setFlowName($defaultTitle)
            ->setFlowDescription($defaultDescription)
            ->addIteration($defaultMessageText)
            ->setStartDate(Carbon::now()->addMinutes(60));

        return ($flowRepository)();
    }
}