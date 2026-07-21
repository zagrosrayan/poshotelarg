<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_sms_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
            $table->string('type', 32);
            $table->unsignedInteger('body_id');
            $table->string('recipient', 20);
            $table->string('recipient_name')->nullable();
            $table->date('scheduled_for');
            $table->string('status', 20)->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('provider_reference')->nullable();
            $table->json('last_response')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['discount_id', 'type']);
            $table->index(['status', 'scheduled_for', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_sms_deliveries');
    }
};
