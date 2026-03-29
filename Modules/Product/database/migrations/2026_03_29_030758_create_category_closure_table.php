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
        Schema::create('category_closure', function (Blueprint $table) {
            $table->foreignId('ancestor_id')
                ->constrained('categories');
            $table->foreignId('descendant_id')
                ->constrained('categories');
            $table->unsignedInteger('depth')->default(0);
            $table->timestamps();

            $table->primary(['ancestor_id', 'descendant_id']);
            $table->index(['descendant_id', 'depth']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_closure');
    }
};
