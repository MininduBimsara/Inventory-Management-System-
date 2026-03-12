<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Step 10: Add quantity management and status logic infrastructure
     * 
     * Changes:
     * - Add manual_status_reason field for tracking why Damaged/Missing status was set
     * - Add quantity CHECK constraint at database level for safety (quantity >= 0)
     * - Add BEFORE UPDATE trigger to automatically reject negative quantities at DB level
     * - Index on status for efficient queries
     * - Index on quantity for efficient range queries
     */
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            // Add reason field for manual status changes (Damaged/Missing)
            // NULL means status is automatic or no reason recorded
            $table->text('manual_status_reason')->nullable()->after('status');

            // Add CHECK constraint to prevent negative quantities at database level
            // This is a safety net against application bugs
            $table->check('quantity >= 0');

            // Add indexes for common query patterns
            // Used in status filtering and availability checks
            if (!Schema::hasColumns('inventory_items', ['status'])) {
                $table->index('status');
            }
            if (!Schema::hasColumns('inventory_items', ['quantity'])) {
                $table->index('quantity');
            }
        });

        // Add database-level trigger to enforce quantity integrity
        // PostgreSQL trigger that prevents quantity from going negative
        // This acts as final safety net even if transaction safety fails
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            Schema::getConnection()->statement('
                CREATE OR REPLACE FUNCTION enforce_inventory_item_quantity_check()
                RETURNS TRIGGER AS $$
                BEGIN
                    IF NEW.quantity < 0 THEN
                        RAISE EXCEPTION \'Inventory item quantity cannot be negative. Current: %, Attempted: %\', OLD.quantity, NEW.quantity;
                    END IF;
                    RETURN NEW;
                END;
                $$ LANGUAGE plpgsql;

                DROP TRIGGER IF EXISTS inventory_item_quantity_check ON inventory_items;
                
                CREATE TRIGGER inventory_item_quantity_check
                BEFORE UPDATE ON inventory_items
                FOR EACH ROW
                EXECUTE FUNCTION enforce_inventory_item_quantity_check();
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            Schema::getConnection()->statement('DROP TRIGGER IF EXISTS inventory_item_quantity_check ON inventory_items');
            Schema::getConnection()->statement('DROP FUNCTION IF EXISTS enforce_inventory_item_quantity_check()');
        }

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn('manual_status_reason');
        });
    }
};
