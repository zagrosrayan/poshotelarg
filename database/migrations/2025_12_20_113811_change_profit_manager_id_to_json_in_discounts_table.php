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
        Schema::table('discounts', function (Blueprint $table) {
 $table->dropForeign(['profit_manager_id']);
    $table->dropColumn('profit_manager_id');
                $table->json('profit_manager_ids')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->dropColumn('profit_manager_ids');
            $table->unsignedBigInteger('profit_manager_id')->nullable();
        });
    }
};
