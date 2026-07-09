<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms', function (Blueprint $table) {
            $table->id();
            $table->text('text')->nullable();
            $table->json('to')->nullable();
            $table->string('from')->nullable();
            $table->json('response')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms');
    }
};