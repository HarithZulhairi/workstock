<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_orders', function (Blueprint $table) {
            $table->id('job_orders_id'); 
            $table->string('customer_name'); 
            $table->string('customer_phone_num'); 
            $table->string('vehicle_plate');
            $table->string('vehicle_brand');
            $table->string('vehicle_model');
            $table->string('vehicle_picture')->nullable();
            $table->text('reported_issue'); 
            $table->string('status')->default('Pending'); 
            $table->decimal('total_cost', 10, 2)->default(0.00);
            $table->unsignedBigInteger('handled_by'); 
            $table->timestamps();

            $table->foreign('handled_by')->references('user_id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_orders');
    }
};
