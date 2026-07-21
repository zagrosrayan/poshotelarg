<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('next_purchase_discounts', function (Blueprint $table) {
            $columns = [
                'reminder_days_before_expiration',
                'discount_sms_template',
                'reminder_sms_template',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('next_purchase_discounts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('settings', function (Blueprint $table) {
            $columns = [
                'order_complete_sms_template',
                'send_order_complete_sms',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('next_purchase_discounts', function (Blueprint $table) {
            if (!Schema::hasColumn('next_purchase_discounts', 'reminder_days_before_expiration')) {
                $table->integer('reminder_days_before_expiration')->nullable();
            }
            if (!Schema::hasColumn('next_purchase_discounts', 'discount_sms_template')) {
                $table->text('discount_sms_template')->nullable();
            }
            if (!Schema::hasColumn('next_purchase_discounts', 'reminder_sms_template')) {
                $table->text('reminder_sms_template')->nullable();
            }
        });

        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'order_complete_sms_template')) {
                $table->text('order_complete_sms_template')->nullable();
            }
            if (!Schema::hasColumn('settings', 'send_order_complete_sms')) {
                $table->boolean('send_order_complete_sms')->default(false);
            }
        });
    }
};
