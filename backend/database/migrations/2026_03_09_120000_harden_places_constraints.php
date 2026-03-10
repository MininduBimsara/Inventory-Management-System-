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
        Schema::table('places', function (Blueprint $table): void {
            $table->dropForeign(['cupboard_id']);

            $table->foreign('cupboard_id')
                ->references('id')
                ->on('cupboards')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unique(['cupboard_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('places', function (Blueprint $table): void {
            $table->dropUnique(['cupboard_id', 'code']);
            $table->dropForeign(['cupboard_id']);

            $table->foreign('cupboard_id')
                ->references('id')
                ->on('cupboards')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }
};
