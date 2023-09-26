<?php

namespace MessagingSmartEvaluering\Services;

use Carbon\Carbon;
use EmployeesSmartEvaluering\Contracts\MessagingIterationResponsesRepository as EmployeesResponsesRepository;
use EmployeesSmartEvaluering\Models\EmployeeIterationResponse;
use EmployeesSmartEvaluering\Models\EmployeeManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MessagingSmartEvaluering\Models\MessagingSender;
use Ramsey\Uuid\Uuid;

class SenderAuthAdapter extends SmartEvalueringSender implements \MessagingSmartEvaluering\Contracts\SmartEvalueringSenderContract
{
    /**
     * @var int
     */
    private $userId;

    public function __construct(string $authUserId)
    {
        $this->userId = $authUserId;
    }

    public function getSenderUID(): string
    {
        if (!isset($this->userId)) throw new \Exception('$authUserId is not defined');

        $uidMatch = DB::table('msg_messaging_users_senders')->select('sender_id')->where('user_id', $this->userId)->first();
        return $uidMatch
            ? $uidMatch->sender_id
            : call_user_func(function () {
                $newSender = new MessagingSender();
                $newSender->save();

                DB::table('msg_messaging_users_senders')->insert([
                    'user_id'   => $this->userId,
                    'sender_id' => $newSender->id
                ]);
                return $newSender->id;
            });
    }

    public static function messageResponseReceived(string $senderId, int $iterationId, string $phone, string $text, Carbon $receivedAt = null): void
    {
        $userId = DB::table('msg_messaging_users_senders')->select('user_id')->where('sender_id', $senderId)->first()->user_id;

        /** @var EmployeesResponsesRepository $adapter */
        $adapter = app()->make(EmployeesResponsesRepository::class);
        $adapter
            ->instantiateUserId($userId)
            ->instantiateIterationId($iterationId)
            ->storeIterationEmployeeResponse($phone, $text, $receivedAt)
        ();
    }

    public function storeIterationEmployeeResponse(int $iterationId, string $phone, string $text, Carbon $receivedAt = null)
    {
        /** @var EmployeeManager $manager */
        $manager = EmployeeManager::find($this->userId);

        $response = new EmployeeIterationResponse();

        $response->iteration_id = $iterationId;
        $response->employee()->associate($manager->employees()->where('phone', $phone)->select('id')->first()->id);
        $response->text = $text;
        $response->received_at = $receivedAt ?? Carbon::now();

        $response->save();
    }
}