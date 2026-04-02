<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automotive_parts', function (Blueprint $table) {
            $table->id('automotive_parts_id'); 
            $table->string('part_serial_number')->unique();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('warranty')->nullable(); 
            $table->string('dimensions')->nullable(); 
            $table->tinyInteger('condition')->nullable(); 
            $table->json('part_images')->nullable(); 
            $table->text('part_description')->nullable();
            $table->unsignedBigInteger('category_id'); 
            $table->decimal('price', 10, 2); 
            $table->integer('stock_quantity')->default(0); 
            $table->boolean('is_visible_to_public')->default(true);
            $table->timestamps();

            $table->foreign('category_id')->references('category_id')->on('categories')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automotive_parts');
    }
};
