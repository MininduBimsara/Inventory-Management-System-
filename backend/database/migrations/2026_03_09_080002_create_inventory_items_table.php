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
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->index()->constrained('places')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('item_name');
            $table->string('code')->unique();
            $table->integer('quantity');
            $table->string('serial_number')->nullable();
            $table->string('image_path')->nullable();
            $table->string('description')->nullable();
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
