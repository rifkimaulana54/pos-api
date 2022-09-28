<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserTmStoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_tm_stores', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->default(1);
            $table->string('store_name');
            $table->string('store_address');
            $table->string('no_telepone')->nullable();
            $table->string('store_description')->nullable();
            $table->bigInteger('created_id');
            $table->string('created_name');
            $table->bigInteger('updated_id');
            $table->string('updated_name');
            $table->timestamps();
            $table->integer('status')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_tm_stores');
    }
}
