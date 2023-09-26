<?php

namespace MessagingSmartEvaluering\Contracts;

use Illuminate\Support\Collection;
use MessagingSmartEvaluering\Exceptions\SenderException;
use MessagingSmartEvaluering\Models\MessagingScheduledSendingIteration;
use MessagingSmartEvaluering\Models\MessagingSender;
use MessagingSmartEvaluering\Models\MessagingSendingFlow;
use MessagingSmartEvaluering\Models\MessagingSendingIteration;

/**
 *
 */
interface MessagingFlowRepository
{
    /**
     * @param MessagingSendingFlow|int $flow
     * @return $this
     */
    public function instantiate($flow): self;

    /**
     * @return $this
     */
    public function refresh(): self;

    /**
     * @param $sender
     * @return $this
     */
    public function setSender($sender): self;

    /**
     * @param string $name
     * @return $this
     */
    public function setFlowName(string $name): self;

    /**
     * @param string $description
     * @return $this
     */
    public function setFlowDescription(string $description): self;

    /**
     * @param int $delayInMinutes
     * @return $this
     */
    public function setDelay(int $delayInMinutes): self;

    /**
     * @param string $text
     * @param int|null $order
     * @return $this
     */
    public function addIteration(string $text, int $order): self;

    /**
     * @param MessagingSendingIteration|int $iterationToRemove
     * @throws SenderException
     * @return $this
     */
    public function removeIteration($iterationToRemove): self;

    /**
     * @return MessagingSendingFlow
     */
    public function getModel(): MessagingSendingFlow;

    /**
     * @return MessagingScheduledSendingIteration
     */
    public function getHead(): MessagingScheduledSendingIteration;

    /**
     * @param array $data
     * @return $this
     */
    public function updateIteration(array $data): self;

    /**
     * @param array $data
     * @return $this
     */
    public function updateSchedule(array $data): self;

    /**
     * @param int|MessagingScheduledSendingIteration $schedule
     * @return $this
     */
    public function deleteSchedule($schedule): self;

    /**
     * @param $date
     * @return $this
     */
    public function setStartDate($date): self;

    /**
     * @param bool $isActive
     * @return $this
     */
    public function setActive(bool $isActive): self;

    /**
     * @param MessagingScheduledSendingIteration $schedule
     * @return false|MessagingScheduledSendingIteration|null
     */
    public function findSchedule(MessagingScheduledSendingIteration $schedule);

    /**
     * @param array|Collection $recipients
     * @return $this
     */
    public function syncIterationsRecipients($recipients): self;
}