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
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('status')->nullable()->constrained('types');
            $table->foreignId('operation')->nullable()->constrained('types');
            $table->string('loggable_type');
            $table->unsignedBigInteger('loggable_id')->nullable();
            $table->text('message')->nullable();
            $table->dateTime('date')->nullable();
            $table->ipAddress('ip')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
