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
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('equipment_name', 150);
            $table->text('description')->nullable();
            $table->integer('total_quantity');
            $table->integer('available_quantity');
            $table->check('total_quantity >= 1');
            $table->check('available_quantity >= 0');
            $table->check('available_quantity <= total_quantity');
            $table->enum('equipment_condition', ['Excellent', 'Good', 'Fair', 'Needs Repair'])->default('Good');
            $table->string('storage_location', 150)->nullable();
            $table->string('image', 255)->nullable();
            $table->enum('status', ['Available', 'Unavailable', 'Maintenance'])->default('Available');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
