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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('status')->nullable()->constrained('types');
            $table->string('reserve_number')->nullable();
            $table->integer('rate_service')->nullable();
            $table->string('desc_number')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('payment_method')->nullable()->constrained('types');
            $table->bigInteger('price')->nullable();
            $table->bigInteger('total_price')->nullable();
            $table->bigInteger('discounted_price')->nullable();
            $table->integer('tax')->nullable();
            $table->integer('quantity')->nullable();
            $table->foreignId('discount_id')->nullable()->constrained('discounts');
            $table->foreignId('food_id')->nullable()->constrained('food');
            $table->timestamp('order_date')->nullable();
            $table->integer('invoice_number');
            $table->foreignId('parent_id')->nullable()->constrained('orders');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
