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
        Schema::create('offices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // The owner of the office
            $table->boolean('hidden')->default(false);
            $table->string('approval_status')->default('pending'); // e.g., 'approved', 'pending', 'rejected'
            $table->decimal('price_per_day', 10, 2);
            $table->decimal('monthly_discount', 5, 2)->nullable(); // discount in percentage
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offices');
    }
};
