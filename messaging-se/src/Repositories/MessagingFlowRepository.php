<?php

namespace MessagingSmartEvaluering\Repositories;

use Carbon\Carbon;
use EmployeesSmartEvaluering\Models\MessagingFlowEmployeesFilter;
use Illuminate\Support\Collection;
use MessagingSmartEvaluering\Exceptions\SenderException;
use MessagingSmartEvaluering\Models\MessagingScheduledSendingIteration;
use \MessagingSmartEvaluering\Contracts\MessagingSendingIterationRepository as MessagingSendingIterationRepository;
use MessagingSmartEvaluering\Models\MessagingSender;
use MessagingSmartEvaluering\Models\MessagingSendingFlow;
use MessagingSmartEvaluering\Models\MessagingSendingIteration;
use SmartEvaluering\Models\Role;
use \MessagingSmartEvaluering\Contracts\MessagingFlowRepository as Contract;

class MessagingFlowRepository implements Contract
{
    /** @var MessagingSendingFlow $flowModel */
    private $flowModel;
    /** @var MessagingScheduledSendingIteration */
    private $scheduledIterationsTail;
    /** @var MessagingScheduledSendingIteration */
    private $scheduledIterationsHead;

    public function __construct()
    {
        $this->flowModel = app()->make(MessagingSendingFlow::class);
    }

    /**
     * @param int|MessagingSendingFlow $flow
     * @return Contract
     * @throws SenderException
     */
    public function instantiate($flow): Contract
    {
        if (!isset($flow))
            throw new SenderException("Flow is missing.");
        if (is_int($flow) && ($flow = MessagingSendingFlow::find($flow)) == null)
            throw new SenderException("Flow with id {$flow} has not been found.");
        if (!($flow instanceof MessagingSendingFlow))
            throw new SenderException("Wrong flow type.");
        if (isset($this->flowModel->sender) && $flow->sender_id !== $this->flowModel->sender->id)
            throw new SenderException("Forbidden!");

        $this->flowModel = $flow;

        $this->scheduledIterationsHead = $this->flowModel->schedules->first();
        $this->scheduledIterationsTail = $this->flowModel->schedules->slice(1)->reduce(function (MessagingScheduledSendingIteration $current, MessagingScheduledSendingIteration $scheduledIteration) {
//            $scheduledIteration->load('delayInMinutes');
            $current->setNextIteration($scheduledIteration);
            $scheduledIteration->delayInMinutes = $scheduledIteration->scheduled_time->diffInMinutes($scheduledIteration->prev->scheduled_time);
            return $current->next;
        }, $this->scheduledIterationsHead);

        $this->flowModel->schedules->each(function (MessagingScheduledSendingIteration $scheduledIteration) {
            $scheduledIteration->append('delayInMinutes');
        });

        return $this;
    }

    public function refresh(): Contract
    {
        return $this->instantiate($this->flowModel->refresh());
    }

    public function __invoke(): MessagingSendingFlow
    {
        $this->scheduledIterationsTail->calculateScheduledTime();
        $this->saveIterationsSchedules();
        $this->flowModel->save();
        return $this->flowModel;
    }

    /**
     * @inheritDoc
     */
    public function setSender($sender = null): Contract
    {
        $this->flowModel->sender()->associate(
            call_user_func(function () use ($sender) {
                if ($sender instanceof MessagingSender) return $sender;
                if (is_int($sender)) return MessagingSender::find($sender);
                return Role::bySlug(Role::SUPER_ADMIN)->users()->orderBy('id')->first()->sender()->getSender();
            })
        );

        return $this;
    }

    public function setFlowName(string $name): Contract
    {
        $this->flowModel->name = $name;
        return $this;
    }

    public function setFlowDescription(string $description): Contract
    {
        $this->flowModel->description = $description;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setDelay(int $delayInMinutes): Contract
    {
        $this->scheduledIterationsTail->delayInMinutes = $delayInMinutes;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addIteration(string $text, int $order = null): Contract
    {
        if (!isset($this->flowModel->id)) $this->flowModel->save();

        $newIterationSchedule = (new MessagingScheduledSendingIteration())
            ->defineFlowToScheduleIn($this->flowModel)
            ->defineIterationToBeScheduled(
                app()->make(MessagingSendingIterationRepository::class)->setSender($this->flowModel->sender)->setText($text)()
            )
            ->setOrder($order ?? $this->flowModel->schedules()->count());


        if (!isset($this->scheduledIterationsTail)) {
            $this->scheduledIterationsTail = $newIterationSchedule;
            $this->scheduledIterationsHead = $newIterationSchedule;
        } else {
            $this->scheduledIterationsTail->setNextIteration($newIterationSchedule);
            $this->scheduledIterationsTail = $newIterationSchedule;
        }

        return $this;
    }

    public function removeIteration($iterationToRemove): Contract
    {
        if (!isset($iterationToRemove))
            throw new SenderException("Iteration is missing.");
        if (is_int($iterationToRemove) && ($iterationToRemove = MessagingSendingIteration::find($iterationToRemove)) == null)
            throw new SenderException("Iteration with id {$iterationToRemove} has not been found.");
        if (!($iterationToRemove instanceof MessagingSendingIteration))
            throw new SenderException("Wrong iteration type.");


        (($schedule = $this->findScheduleByIteration($iterationToRemove)) !== false) && $schedule->prev->setNextIteration($schedule->next);

        return $this;
    }

    /**
     * @param MessagingSendingIteration|int $iteration
     * @return false|MessagingScheduledSendingIteration|null
     */
    private function findScheduleByIteration($iteration)
    {
        $iterationId = $iteration instanceof MessagingSendingIteration ? $iteration->id : $iteration;
        if (($current = $this->scheduledIterationsHead) == null) return false;

        do {
            if ($current->iteration_id === $iterationId) return $current;
        } while (($current = $current->next) !== null);

        return false;
    }

    /**
     * @param MessagingScheduledSendingIteration $schedule
     * @return false|MessagingScheduledSendingIteration|null
     */
    public function findSchedule(MessagingScheduledSendingIteration $schedule)
    {
        if (($current = $this->scheduledIterationsHead) == null) return false;

        do {
            if ($current->iteration_id === $schedule->iteration_id) return $current;
        } while (($current = $current->next) !== null);

        return false;
    }

    public function saveIterationsSchedules(): Collection
    {
        $schedules = new Collection();
        $order = 0;
        if (($current = $this->scheduledIterationsHead) !== null) {
            do {
                $current->setOrder($order);
                $current->save();
                $schedules->push($current);
            } while (($current = $current->next) !== null && ++$order);
        }

        return $schedules;
    }

    public function getModel(): MessagingSendingFlow
    {
        return $this->flowModel;
    }

    public function getHead(): MessagingScheduledSendingIteration
    {
        return $this->scheduledIterationsHead;
    }

    public function updateIteration(array $data): Contract
    {
        if (!isset($data['id'])) {
            $this->addIteration($data['text']);
            ($this)();
        }
        $this->flowModel->getRawIterationsQuery()->where('id', $data['id'])->update(
            collect($data)->only(collect((new MessagingSendingIteration())->getFillable()))->all()
        );

        $this->refresh();
        return $this;
    }

    public function updateSchedule(array $data): Contract
    {
        if (!isset($data['id'])) {
            return $this;
        }

        $schedule = $this->findScheduleByIteration($data['id']);
        if (($delayInMinutes = $data['delayInMinutes']) !== null) {
            $schedule->delayInMinutes = $delayInMinutes;
        }

        $schedule->fill(
            collect($data)->only((new MessagingScheduledSendingIteration())->getFillable())->all()
        );

        $this();
        $this->refresh();
        return $this;
    }

    public function deleteSchedule($schedule): Contract
    {
        $linkedSchedule = $this->findScheduleByIteration($schedule);
        if (!$linkedSchedule->next->iteration->messages()->exists())
            $linkedSchedule->next->iteration->messages()->saveMany($linkedSchedule->iteration->messages);

        $linkedSchedule->delete();
        $this->refresh();
        return $this;
    }

    public function setStartDate($date): Contract
    {
        $this->scheduledIterationsHead->scheduled_time = new Carbon($date);
        $this->scheduledIterationsTail->calculateScheduledTime();

        return $this;
    }

    public function setActive(bool $isActive): Contract
    {
        $this->flowModel->is_active = $isActive;
        return $this;
    }

    public function syncIterationsRecipients($recipients): Contract
    {
        $nextPendingIterationOrder = $this->flowModel->iterations()->where('is_finished', false)->min('order');
        if (!isset($nextPendingIterationOrder)) return $this;

        $iterations = $this->flowModel->iterations()->where('is_finished', false)->where(function ($query) use ($nextPendingIterationOrder) {
            $query->has('messages')->orWhere('order', $nextPendingIterationOrder);
        })->get();

        $iterations->reduce(function (MessagingSendingIterationRepository $iterationRepository, MessagingSendingIteration $iteration) use ($recipients) {
            $iteration->messages()->where('is_sent', false)->delete();

            $alreadySentPhones = $iteration->messages()->select('phone')->get()->pluck('phone');
            $newRecipients = array_filter($recipients, function ($phone) use ($alreadySentPhones) {
                return $alreadySentPhones->search($phone) === false;
            });

            $iterationRepository->defineBasicModelInstance($iteration)->addRecipients($newRecipients);
            return $iterationRepository;
        }, app()->make(MessagingSendingIterationRepository::class));

        return $this;
    }
}