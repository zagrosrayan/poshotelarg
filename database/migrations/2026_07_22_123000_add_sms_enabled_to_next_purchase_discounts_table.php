<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('next_purchase_discounts', function (Blueprint $table) {
            if (!Schema::hasColumn('next_purchase_discounts', 'sms_enabled')) {
                $table->boolean('sms_enabled')->default(true)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('next_purchase_discounts', function (Blueprint $table) {
            if (Schema::hasColumn('next_purchase_discounts', 'sms_enabled')) {
                $table->dropColumn('sms_enabled');
            }
        });
    }
};
