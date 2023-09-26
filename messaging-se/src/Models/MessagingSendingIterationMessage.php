<?php

namespace MessagingSmartEvaluering\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $phone
 * @property MessagingSendingIteration $iteration
 * @property bool $is_sent
 * @property string $response
 * @property Carbon $respond_at
 */
class MessagingSendingIterationMessage extends Model
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if ($connection = config('messaging-se.databaseconnection')) {
            $this->connection = $connection;
        }
    }

    protected $casts = [
    ];

    protected $fillable = [
        'phone', 'iteration_id', 'is_sent', 'response', 'respond_at'
    ];

    protected $appends = [
    ];

    public function iteration(): BelongsTo
    {
        return $this->belongsTo(MessagingSendingIteration::class, 'iteration_id');
    }

    public function log()
    {

    }
}