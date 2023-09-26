<?php

namespace MessagingSmartEvaluering\Repositories;

use MessagingSmartEvaluering\Contracts\BasicModelRepositoryContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class BasicModelRepository implements BasicModelRepositoryContract
{
    /** @var Model */
    public $model;

    /** @var static */
    public $prev;
    /** @var static */
    public $next;

    /** @var Collection */
    protected $data;

    public function __construct()
    {
        $this->model = app($this->getModelClass());
        $this->data = new Collection();
    }

    abstract public function getModelClass(): string;

    public function instantiate(self $base = null): BasicModelRepositoryContract
    {
        $newRepository = isset($base) ? clone $base : app()->make(static::class);
        $newRepository->model = app($this->getModelClass());
        $base->next = $newRepository;
        $newRepository->prev = $base;

        return $newRepository;
    }

    public function __invoke()
    {
        try {
            $this->model->save();
        } catch (\Exception $ex) {
            $this->logException($ex);
            return isset($this->prev) ? $this->prev->data : null;
        }


        $this->data = isset($this->prev) ? Collection::wrap(($this->prev)())->filter()->push($this->model) : $this->model;

        return $this->data;
    }

    public function clone(): BasicModelRepositoryContract
    {
        $repository = $this->instantiate($this);
        $repository->model = clone $this->model;
        return $repository;
    }

    protected function getLogPath()
    {
        return collect(explode('\\', static::class))->pop();
    }

    protected function logException(\Exception $ex)
    {
//        Log::useDailyFiles(_EMPLOYEES_ROOT_ . "/storage/logs/".  $this->getLogPath());
//        Log::info('ERROR==========');
//        ($user = Auth::user()) !== null && Log::info("userId: {$user->id}");
//        Log::info($ex->getMessage());
//        Log::info(
//            "Project file trace:" . PHP_EOL.
//            collect($ex->getTrace())->reduce(function ($trace, $file) {
//                if (!isset($file['class']) || !str_contains($file['class'], 'EmployeesSmartEvaluering')) return $trace;
//
//                $trace->push($file['class']);
//                return $trace;
//            }, new Collection())->implode(PHP_EOL)
//        );
    }
}