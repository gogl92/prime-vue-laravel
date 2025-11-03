<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some active branches
        Branch::factory()->active()->count(5)->create();

        // Create some inactive branches
        Branch::factory()->inactive()->count(2)->create();

        // Create random branches
        Branch::factory()->count(8)->create();
    }
}

