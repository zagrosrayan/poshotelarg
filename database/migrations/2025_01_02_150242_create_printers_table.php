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
        Schema::create('printers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('location')->nullable();
            $table->foreignId('status')->nullable()->constrained('types');
            $table->foreignId('type')->nullable()->constrained('types');
            $table->foreignId('article_id')->nullable()->constrained('articles');
            $table->foreignId('profit_manager_id')->nullable()->constrained('profit_managers');
            $table->foreignId('food_id')->nullable()->constrained('food');
            $table->timestamps();
            $table->softDeletes()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('printers');
    }
};
