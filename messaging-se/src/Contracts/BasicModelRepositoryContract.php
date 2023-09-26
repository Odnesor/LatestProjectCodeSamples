<?php

namespace MessagingSmartEvaluering\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface BasicModelRepositoryContract
{

    /**
     * @return Collection|Model
     */
    public function __invoke();

    /**
     * @return string
     */
    public function getModelClass(): string;


    /**
     * @return $this
     */
    public function clone(): self;

    /**
     * @return $this
     */
    public function instantiate(): self;
}