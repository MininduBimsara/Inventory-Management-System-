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
            $table->string('name');
            $table->string('code')->unique();
            $table->integer('quantity')->default(0);
            $table->string('serial_number')->nullable()->unique();
            $table->string('image_path')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('available');
            $table->timestamps();
            $table->softDeletes();
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
