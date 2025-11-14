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
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->unique()->constrained()->onDelete('cascade');
            $table->boolean('is_enabled')->default(false);
            $table->string('slug')->unique();
            $table->string('business_name')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('primary_color')->default('#3B82F6');
            $table->string('secondary_color')->default('#1E40AF');
            $table->json('available_product_ids')->nullable();
            $table->json('available_service_ids')->nullable();
            $table->json('available_subscription_ids')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->text('success_message')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
