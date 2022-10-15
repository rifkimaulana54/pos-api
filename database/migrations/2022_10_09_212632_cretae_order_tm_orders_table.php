<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CretaeOrderTmOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_tm_orders', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->default(1);
            $table->bigInteger('store_id');
            $table->string('order_code');
            $table->string('customer_name')->nullable();
            $table->integer('total_order')->nullable();
            $table->integer('order_number');
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
        Schema::dropIfExists('order_tm_orders');
    }
}
