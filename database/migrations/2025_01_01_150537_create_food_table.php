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
        Schema::create('food', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->bigInteger('price')->nullable();
            $table->string('slug')->nullable()->unique();
            $table->foreignId('status')->nullable()->constrained('types');
            $table->foreignId('article_id')->nullable()->constrained('articles');
            $table->foreignId('profit_manager_id')->nullable()->constrained('profit_managers');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('code')->nullable();
            $table->timestamps();
            $table->softDeletes()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food');
    }
};
