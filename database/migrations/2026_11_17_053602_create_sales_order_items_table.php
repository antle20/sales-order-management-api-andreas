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
        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            // FK SO_id
            $table->foreignId('sales_order_id')->constrained('sales_orders')->onDelete('cascade');
            // FK product_id
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->integer('quantity'); 
            $table->decimal('price', 12, 2); 
            $table->decimal('subtotal', 12, 2); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_order_items');
    }
};
