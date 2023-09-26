<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMessagingSendingFlowsSchedulesTable extends Migration
{
    const TABLE = 'msg_messaging_sending_flows_schedules';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('flow_id')->index();
            $table->foreign('flow_id')->references('id')->on(CreateMessagingSendingFlowsTable::TABLE)->onDelete('cascade');

            $table->unsignedInteger('iteration_id');
            $table->foreign('iteration_id')->references('id')->on('msg_messaging_sending_iterations')->onDelete('cascade');

            $table->dateTime('scheduled_time')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->unsignedSmallInteger('order')->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(self::TABLE);
    }
}
