<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CretaeOrderTrMappingOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_tr_mapping_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('order_qty')->default(1);
            $table->integer('default_price');
            $table->integer('order_subtotal');
            $table->bigInteger('created_id');
            $table->string('created_name');
            $table->bigInteger('updated_id');
            $table->string('updated_name');
            $table->timestamps();
            $table->integer('status')->default(1);

            $table->foreign('order_id')->references('id')->on('order_tm_orders')
            ->onUpdate('cascade')->onDelete('cascade');

            $table->foreign('product_id')->references('id')->on('product_tm_products')
            ->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_tr_mapping_orders');
    }
}
