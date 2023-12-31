<?php

namespace MessagingSmartEvaluering\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Ramsey\Uuid\Uuid;

/**
 */

/**
 * @property Carbon $scheduled_time
 * @property Carbon $sent_at
 * @property int $order
 * @property int $iteration_id
 * @property int $flow_id
 * @property bool $is_finished
 * @property MessagingSendingIteration $iteration
 */
class MessagingScheduledSendingIteration extends Pivot
{
    protected $table = 'messaging_sending_flows_schedules';
    public $foreignKey = 'iteration_id';
    protected $relatedKey = 'flow_id';

    /** @var self */
    public $prev = null;
    /** @var self */
    public $next = null;
    /** @var Carbon */
    public $delayInMinutes = null;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if ($connection = config('messaging-se.databaseconnection')) {
            $this->connection = $connection;
        }
    }

    protected $fillable = [
        'scheduled_time', 'sent_at', 'order', 'id'
    ];

    protected $dates = ['scheduled_time'];

    public function iteration(): BelongsTo
    {
        return $this->belongsTo(MessagingSendingIteration::class, 'iteration_id');
    }

    public function flow(): BelongsTo
    {
        return $this->belongsTo(MessagingSendingFlow::class, 'flow_id');
    }

    public function defineFlowToScheduleIn($flow)
    {
        $this->flow()->associate($flow);
        return $this;
    }

    public function defineIterationToBeScheduled($iteration)
    {
        $this->iteration()->associate($iteration);
        return $this;
    }

    public function setOrder(int $order)
    {
        $this->order = $order;
        return $this;
    }

    public function calculateScheduledTime(): Carbon
    {
        $this->scheduled_time = isset($this->prev)
            ? $this->prev->calculateScheduledTime()->addMinutes($this->delayInMinutes ?? 0)
            : ($this->scheduled_time ?? Carbon::now()->addMinutes($this->delayInMinutes ?? 1));

        return $this->scheduled_time->copy();
    }

    public function setDelayInMinutesAttribute($value)
    {
        $this->attributes['delayInMinutes'] = new Carbon($value);
    }

    public function getDelayInMinutesAttribute()
    {
        return $this->attributes['delayInMinutes'] ?? (
            isset($this->prev) &&
            isset($this->prev->scheduled_time) &&
            isset($this->scheduled_time) ?
                (new Carbon($this->scheduled_time))->diffInMinutes($this->prev->scheduled_time)
                : 0
            );
    }

    /**
     * @param self|null $iteration
     * @return void
     */
    public function setNextIteration($iteration)
    {
        $this->next = $iteration;
        if (isset($this->next)) $this->next->prev = $this;
    }

    public function toArray()
    {
        $custom = [];
        if (isset($this->next))
            $custom['next'] = $this->next->toArray();
        if (isset($this->delayInMinutes))
            $custom['delayInMinutes'] = $this->delayInMinutes;

        return $custom + parent::toArray(); // TODO: Change the autogenerated stub
    }

    public function delete()
    {
        $this->iteration()->delete();
        return parent::delete(); // TODO: Change the autogenerated stub
    }
}