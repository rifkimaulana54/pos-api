<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserTmCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_tm_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('display_name');
            $table->bigInteger('parent_id')->nullable();
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
        Schema::dropIfExists('user_tm_companies');
    }
}
