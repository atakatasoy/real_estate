<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateFieldsToAppointmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('departure');
            $table->dropColumn('arrival_back');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->integer('departure')->after('travel_time');
            $table->integer('arrival_back')->after('departure');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('departure');
            $table->dropColumn('arrival_back');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dateTime('departure')->after('travel_time');
            $table->dateTime('arrival_back')->after('departure');            
        });
    }
}
