<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('part_variations', function (Blueprint $table) {
            $table->id('variation_id');
            $table->unsignedBigInteger('automotive_parts_id'); 
            $table->string('name');
            $table->decimal('price', 10, 2); 
            $table->integer('stock_quantity')->default(0); 
            $table->string('picture')->nullable(); 
            
            $table->timestamps();

            $table->foreign('automotive_parts_id')
                  ->references('automotive_parts_id')
                  ->on('automotive_parts')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('part_variations');
    }
};
