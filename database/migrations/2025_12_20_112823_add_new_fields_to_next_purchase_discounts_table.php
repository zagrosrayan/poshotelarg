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
        Schema::table('next_purchase_discounts', function (Blueprint $table) {
            $table->integer('reminder_days_before_expiration')->nullable();
            $table->integer('discount_validity_days')->nullable();
            $table->text('discount_sms_template')->nullable();
            $table->text('reminder_sms_template')->nullable();
            $table->json('profit_manager_ids')->nullable();
            $table->json('target_customer_types')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('next_purchase_discounts', function (Blueprint $table) {
            $table->dropColumn([
                'reminder_days_before_expiration',
                'discount_validity_days',
                'discount_sms_template',
                'reminder_sms_template',
                'profit_manager_ids',
                'target_customer_types',
            ]);
        });
    }
};
