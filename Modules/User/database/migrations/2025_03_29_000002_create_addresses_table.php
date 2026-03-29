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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('label')->default('Home'); // Home, Work, Other
            $table->boolean('is_default')->default(false);
            $table->string('line_1'); // Street address
            $table->string('line_2')->nullable(); // Apartment, suite, unit
            $table->string('city');
            $table->string('state', 2); // 2-letter state code (CA, NY, TX...)
            $table->string('postal_code', 10); // ZIP: 12345 or 12345-6789
            $table->string('country_code', 2)->default('US');
            $table->timestamps();

            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
