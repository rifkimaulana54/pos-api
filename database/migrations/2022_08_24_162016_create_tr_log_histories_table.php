<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrLogHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tr_log_histories', function (Blueprint $table) {
            $table->id();
            $table->string('user_name',255);
            $table->longText('action');
            $table->string('action_type',255)->nullable();
            $table->string('ip_address',255);
            $table->string('service',255);
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
        Schema::dropIfExists('tr_log_histories');
    }
}
