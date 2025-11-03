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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            // Customer personal information
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 100)->unique();

            // Contact information
            $table->string('phone', 45);

            // Address fields
            $table->string('street_1', 100)->nullable();
            $table->string('street_2', 45)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 60)->nullable();
            $table->string('zip', 11)->nullable();
            $table->string('country', 100)->nullable();

            // Stripe Connect Customer ID
            $table->string('stripe_id')->nullable()->index();
            
            // Additional customer details
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);

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
        Schema::dropIfExists('customers');
    }
};
