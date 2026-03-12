<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            $table->foreignId('item_id')->index()->constrained('inventory_items')->cascadeOnUpdate()->restrictOnDelete();
            $table->integer('quantity_borrowed');
            $table->integer('quantity_returned')->default(0);
            $table->string('item_condition_on_return')->nullable();
            $table->timestamps();

            $table->unique(['borrow_transaction_id', 'item_id']);
            $table->index('item_condition_on_return');
            if (DB::getDriverName() !== 'sqlite') {
                $table->check('quantity_borrowed > 0');
                $table->check('quantity_returned >= 0');
                $table->check('quantity_returned <= quantity_borrowed');
                $table->check("item_condition_on_return is null or item_condition_on_return in ('good', 'damaged', 'missing')");
            }
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
