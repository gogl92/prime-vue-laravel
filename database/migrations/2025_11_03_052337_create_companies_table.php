<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();

            // Main columns
            $table->string('name', 200);

            // Address fields
            $table->string('street_1', 100);
            $table->string('street_2', 45)->nullable();
            $table->string('city', 100);
            $table->string('state', 60);
            $table->string('zip', 11);
            $table->string('country', 100);

            // Contact information
            $table->string('phone', 45);
            $table->string('email', 100);

            // Tax information
            $table->string('tax_id', 45);
            $table->string('tax_name', 255);

            // Timestamp columns
            $table->timestamps();
            $table->softDeletes();

            // Blameable/Audit columns
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
