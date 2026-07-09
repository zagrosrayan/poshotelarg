<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('next_purchase_discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('minimum_purchase_amount', 15, 2);
            $table->decimal('discount_percentage', 5, 2);
            $table->decimal('apply_on_orders_above', 15, 2);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('next_purchase_discounts');
    }
};