<?php

namespace MessagingSmartEvaluering\Contracts;

use Illuminate\Support\Collection;
use MessagingSmartEvaluering\Models\MessagingSender;
use MessagingSmartEvaluering\Models\MessagingSendingIteration;
use MessagingSmartEvaluering\Models\MessagingSendingIterationMessage;

interface MessagingSendingIterationRepository extends BasicModelRepositoryContract
{
    /**
     * @param MessagingSendingIteration|int $iteration
     * @return $this
     */
    public function defineBasicModelInstance($iteration): self;

    /**
     * @param string $text
     * @return $this
     */
    public function setText(string $text): self;

    /**
     * @param MessagingSender|int $sender
     * @return $this
     */
    public function setSender($sender): self;


    /**
     * @param array $data
     * @return $this
     */
    public function update(array $data): self;

    /**
     * @param array|string|Collection $phoneNumbers
     * @return $this
     */
    public function addRecipients($phoneNumbers): self;

    /**
     * @return null|$this
     */
    public function getPreviousIterationRepository();

    /**
     * @return Collection
     */
    public function getMessages(): Collection;

    /**
     * @return void
     */
    public function send(): void;

    /**
     * @return void
     */
    public function logIterationProcessing(): void;

    /**
     * @return void
     * @param Collection|array|null
     */
    public function logIterationMessages($messages): void;

    /**
     * @return void
     * @param $data
     */
    public function log($data): void;

    public function finish(): void;

    /**
     * @param MessagingSendingIterationMessage $message
     * @param string $text
     * @return $this
     */
    public function storeMessageResponse(MessagingSendingIterationMessage $message, string $text): self;
}