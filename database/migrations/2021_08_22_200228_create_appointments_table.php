<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppointmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_id');
            $table->unsignedBigInteger('user_id');
            $table->string('postal_code', 100);
            $table->dateTime('reserved_at');
            $table->integer('distance');
            $table->integer('travel_time');
            $table->dateTime('departure');
            $table->dateTime('arrival');
            $table->boolean('completed')->default(false);
            $table->timestamps();

            $table->foreign('user_id')
                ->on('users')
                ->references('id')
                ->onDelete('cascade');

            $table->foreign('contact_id')
                ->on('contacts')
                ->references('id')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('appointments');
    }
}
