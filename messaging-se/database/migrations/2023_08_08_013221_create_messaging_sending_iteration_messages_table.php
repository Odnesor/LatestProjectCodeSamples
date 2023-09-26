<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMessagingSendingIterationMessagesTable extends Migration
{
    const TABLE = 'msg_messaging_sending_iteration_messages';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('iteration_id')->index();
            $table->foreign('iteration_id')->references('id')->on('msg_messaging_sending_iterations');

            $table->string('phone')->required();
            $table->boolean('is_sent')->default(false);

            $table->string('response')->nullable();
            $table->dateTime('respond_at')->nullable();

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
