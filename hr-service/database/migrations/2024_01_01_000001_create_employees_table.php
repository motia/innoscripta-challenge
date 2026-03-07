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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('last_name');
            $table->decimal('salary', 12, 2);
            $table->string('country', 50);

            // USA-specific fields
            $table->string('ssn')->nullable();
            $table->text('address')->nullable();

            // Germany-specific fields
            $table->text('goal')->nullable();
            $table->string('tax_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('country');
            $table->index(['country', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
