<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserTmUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_tm_users', function (Blueprint $table) {
            $table->id();
            $table->string('fullname');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('password');
            $table->longText('picture');
            // $table->string('user_code');
            $table->bigInteger('user_number');
            $table->string('active_token')->nullable();
            $table->string('reset_token')->nullable();
            $table->string('type')->default('personal');
            $table->bigInteger('created_id');
            $table->string('created_name');
            $table->bigInteger('updated_id');
            $table->string('updated_name');
            $table->timestamps();
            $table->bigInteger('company_id')->default(1);
            $table->bigInteger('store_id')->default(1);
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
        Schema::dropIfExists('user_tm_users');
    }
}
