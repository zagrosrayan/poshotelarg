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
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // عنوان تخفیف
            $table->string('code')->unique(); // کد تخفیف
            $table->decimal('discount_value', 10, 2); // مقدار تخفیف (درصد یا عدد ثابت)
            $table->decimal('minimum_price', 10, 2)->nullable(); // حداقل مبلغ سفارش
            $table->boolean('is_active'); // فعال بودن تخفیف
            $table->integer('usage_limit')->nullable(); // حداکثر تعداد استفاده از تخفیف
            $table->integer('usage_count')->default(0); // تعداد دفعات استفاده‌شده
            $table->dateTime('starts_at')->nullable(); // تاریخ شروع
            $table->dateTime('expires_at')->nullable(); // تاریخ انقضا
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
