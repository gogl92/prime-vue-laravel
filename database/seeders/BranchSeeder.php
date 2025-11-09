<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::all()->toArray();
        array_walk($companies, static function ($company) {
            Branch::factory()->count(3)->create([
                'company_id' => $company['id'],
            ]);
        });

        $superAdminUser = User::where('email', 'superadmin@example.com')->first();
        $superAdminUser->current_branch_id = 1;
        $superAdminUser->current_company_id = 1;
        $superAdminUser->save();
    }
}
