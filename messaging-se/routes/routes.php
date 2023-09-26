<?php

use Illuminate\Support\Facades\Route;


Route::group(['prefix' => 'messaging', 'as' => 'msg.'], function () {
    Route::post('/incoming', function () {
        \MessagingSmartEvaluering\Models\MessagingSendingIteration::latest()->first()->update(['text' => 'sanole21']);
    });
});
