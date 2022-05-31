<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRegisterCouponsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('register_coupons', function (Blueprint $table) {
            $table->id();
            $table->string('title',100)->nullable();
            $table->string('code',15)->nullable();
            $table->date('start_date')->nullable();
            $table->date('expire_date')->nullable();
            $table->decimal('min_purchase',$precision = 24, $scale = 2);
            $table->decimal('max_discount',$precision = 24, $scale = 2);
            $table->decimal('discount',$precision = 24, $scale = 2);
            $table->string('discount_type',15)->default('percentage');
            $table->string('coupon_type')->default('default');
            $table->integer('limit')->nullable();
            $table->boolean('status')->default(1);
            $table->string('data')->nullable();
            $table->decimal('min_purchase',$precision = 24, $scale = 2)->change();
            $table->decimal('max_discount',$precision = 24, $scale = 2)->change();
            $table->decimal('discount',$precision = 24, $scale = 2)->change();
            $table->bigInteger('total_uses')->nullable()->default(0);
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
        Schema::dropIfExists('register_coupons');
    }
}
