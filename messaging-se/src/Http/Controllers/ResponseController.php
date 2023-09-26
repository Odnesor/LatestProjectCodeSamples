<?php

namespace MessagingSmartEvaluering\Http\Controllers;


use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use MessagingSmartEvaluering\Contracts\SmartEvalueringSenderContract;
use MessagingSmartEvaluering\Models\MessagingSendingIterationMessage;

class ResponseController
{
    use AuthorizesRequests;
    use ValidatesRequests;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    public function incomingResponse(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|int',
            'text'  => 'required|string',
        ]);

        /** @var MessagingSendingIterationMessage $relatedMessage */
        $relatedMessage = MessagingSendingIterationMessage::where('phone', $data['phone'])->latest()->first();

        app(SmartEvalueringSenderContract::class)::messageResponseReceived($relatedMessage, $data['text']);
    }
}
