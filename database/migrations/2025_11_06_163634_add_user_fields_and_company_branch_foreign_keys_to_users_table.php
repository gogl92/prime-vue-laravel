<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Check if columns don't exist before adding
            if (!Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->after('id')->nullable();
            }
            if (!Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->after('first_name')->nullable();
            }
            if (!Schema::hasColumn('users', 'second_last_name')) {
                $table->string('second_last_name')->after('last_name')->nullable();
            }
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->after('second_last_name')->unique()->nullable();
            }
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->after('username')->nullable();
            }
            if (!Schema::hasColumn('users', 'current_company_id')) {
                $table->foreignId('current_company_id')->after('password')->nullable()->constrained('companies')->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'current_branch_id')) {
                $table->foreignId('current_branch_id')->after('current_company_id')->nullable()->constrained('branches')->nullOnDelete();
            }
        });

        // Update existing users to have required fields
        DB::table('users')->whereNull('first_name')->update([
            'first_name' => DB::raw('name'),
            'last_name' => 'User',
            'username' => DB::raw('LOWER(REPLACE(email, "@", "_"))'),
        ]);

        // Make first_name, last_name, and username NOT NULL after updating
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable(false)->change();
            $table->string('last_name')->nullable(false)->change();
            $table->string('username')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'current_branch_id')) {
                $table->dropForeign(['current_branch_id']);
                $table->dropColumn('current_branch_id');
            }
            if (Schema::hasColumn('users', 'current_company_id')) {
                $table->dropForeign(['current_company_id']);
                $table->dropColumn('current_company_id');
            }
            if (Schema::hasColumn('users', 'phone')) {
                $table->dropColumn('phone');
            }
            if (Schema::hasColumn('users', 'username')) {
                $table->dropColumn('username');
            }
            if (Schema::hasColumn('users', 'second_last_name')) {
                $table->dropColumn('second_last_name');
            }
            if (Schema::hasColumn('users', 'last_name')) {
                $table->dropColumn('last_name');
            }
            if (Schema::hasColumn('users', 'first_name')) {
                $table->dropColumn('first_name');
            }
        });
    }
};
