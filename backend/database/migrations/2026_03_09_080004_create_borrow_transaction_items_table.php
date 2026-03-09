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
        Schema::create('borrow_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borrow_transaction_id')->index()->constrained('borrow_transactions')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->index()->constrained('inventory_items')->cascadeOnUpdate()->restrictOnDelete();
            $table->integer('quantity_borrowed');
            $table->integer('quantity_returned');
            $table->string('line_status');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borrow_transaction_items');
    }
};
