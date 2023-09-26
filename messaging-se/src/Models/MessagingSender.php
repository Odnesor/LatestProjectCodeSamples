<?php

namespace MessagingSmartEvaluering\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use MessagingSmartEvaluering\Loggers\MessagingLoggerFactory;
use MessagingSmartEvaluering\Loggers\SenderLogger;
use Ramsey\Uuid\Uuid;
use SmartEvaluering\Models\User;

/**
 * @property Collection $flows
 */
class MessagingSender extends Model
{
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    private $logger;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if ($connection = config('messaging-se.databaseconnection')) {
            $this->connection = $connection;
        }

        $this->id = Uuid::uuid4()->toString();
    }

    protected $fillable = [
    ];

    protected $appends = [
    ];

    /**
     * @return SenderLogger
     */
    public function getLogger(): SenderLogger
    {
        return $this->logger ?? ($this->logger = MessagingLoggerFactory::getLogger(SenderLogger::class, $this));
    }

    public function flows(): HasMany
    {
        return $this->hasMany(MessagingSendingFlow::class, 'sender_id');
    }
}