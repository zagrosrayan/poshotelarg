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

            $table->foreignId('profit_manager_id')
                ->nullable()
                ->constrained('profit_managers');

            $table->boolean('is_special')->default(false);

            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers');

            $table->foreignId('status_id')
                ->nullable()
                ->constrained('types');


            $table->string('reserve_number')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->dropForeign(['profit_manager_id']);
            $table->dropForeign(['customer_id']);

            $table->dropColumn([
                'profit_manager_id',
                'is_special',
                'customer_id',
                'reserve_number',
            ]);
        });
    }

};
