<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMessagingSendingIterationsTable extends Migration
{
    const TABLE = 'msg_messaging_sending_iterations';

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

            $table->string('text', 600);

            $table->boolean('is_finished')->default(false);

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
