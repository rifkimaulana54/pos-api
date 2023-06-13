<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetTmMediaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('asset_tm_media', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('company_id')->default(1);
            $table->bigInteger('user_id');
            $table->string('user_name',255);
            $table->text('media_caption')->nullable();
            $table->text('media_path')->nullable();
            $table->text('media_original_name')->nullable();
            $table->string('media_type',255)->nullable();
            $table->bigInteger('created_id');
            $table->string('created_name');
            $table->bigInteger('updated_id');
            $table->string('updated_name');
            $table->string('service',255);
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
        Schema::dropIfExists('asset_tm_media');
    }
}
