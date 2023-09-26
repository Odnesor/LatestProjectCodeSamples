<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMessagingSendingFlowsTable extends Migration
{
    const TABLE = 'msg_messaging_sending_flows';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->increments('id');

            $table->uuid('sender_id')->index();
            $table->foreign('sender_id')->references('id')->on('msg_messaging_senders');

            $table->boolean('is_active')->default(false);

            $table->string('name')->nullable();
            $table->string('description')->nullable();


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
