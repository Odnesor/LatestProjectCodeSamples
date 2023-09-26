<?php

namespace MessagingSmartEvaluering\Contracts;

use Illuminate\Support\Collection;
use MessagingSmartEvaluering\Exceptions\SenderException;
use MessagingSmartEvaluering\Models\MessagingScheduledSendingIteration;
use MessagingSmartEvaluering\Models\MessagingSender;
use MessagingSmartEvaluering\Models\MessagingSendingFlow;

interface SmartEvalueringSenderContract
{
    /**
     * @return void
     */
    public function getSenderUID(): string;

    /**
     * @return MessagingSender
     */
    public function getSender(): MessagingSender;

    /**
     * @param MessagingSendingFlow|int $flow
     * @return MessagingFlowRepository
     * @throws SenderException
     */
    public function getFlowSetter($flow): MessagingFlowRepository;

    /**
     * @param MessagingSendingFlow|int $flow
     * @param array $iterationData
     * @return array
     */
    public function updateIteration($flow, array $iterationData): array;

    /**
     * @param MessagingSendingFlow|int $flow
     * @param array $scheduleData
     * @return array
     */
    public function updateSchedule($flow, array $scheduleData): array;

    /**
     * @param MessagingSendingFlow|int $flow
     * @param array $flowData
     * @return MessagingSendingFlow
     */
    public function updateFlowData($flow, array $flowData): MessagingSendingFlow;

    /**
     * @param array $recipientsNumbers
     * @param int $iterationId
     * @return Collection
     */
    public function setIterationRecipients(int $iterationId, array $recipientsNumbers): Collection;

    /**
     * @return Collection
     */
    public function getFlows(): Collection;


    /**
     * @param int $flowId
     * @return array
     */
    public function getCompleteFlowData(int $flowId): array;

    public function syncFlowRecipients(int $flowId, array $recipientsNumbers): void;

    public function deleteFlow(int $flowId): void;

    /**
     * @return MessagingSendingFlow
     */
    public function getBlankFlow(): MessagingSendingFlow;

    /**
     * @param string $senderId
     * @param int $iterationId
     * @param string $phone
     * @param string $text
     * @return void
     */
    public static function messageResponseReceived(string $senderId, int $iterationId, string $phone, string $text): void;
}