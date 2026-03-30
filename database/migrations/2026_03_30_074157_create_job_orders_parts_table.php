<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_orders_parts', function (Blueprint $table) {
            $table->id('job_order_parts_id');
            $table->unsignedBigInteger('job_orders_id');
            $table->unsignedBigInteger('automotive_parts_id'); 
            $table->integer('quantity_used'); 
            $table->decimal('subtotal', 10, 2); 
            $table->timestamps();

            $table->foreign('job_orders_id')->references('job_orders_id')->on('job_orders')->onDelete('cascade');
            $table->foreign('automotive_parts_id')->references('automotive_parts_id')->on('automotive_parts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_orders_parts');
    }
};