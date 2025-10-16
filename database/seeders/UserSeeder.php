<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create a super admin user
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password'),
        ]);
        $superAdmin->assignRole('super_admin');

        // Create my user (Admin user)
        $admin = User::create([
            'name' => env('ADMIN_NAME', 'Luis Gonzalez'),
            'email' => env('ADMIN_EMAIL', 'luisarmando1234@gmail.com'),
            'password' => Hash::make('12345678'),
        ]);
        $admin->assignRole('super_admin');

        // Create users from CSV fixtures
        $csvPath = database_path('fixtures/users.csv');
        if (($handle = fopen($csvPath, 'r')) !== false) {
            $header = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                $userData = array_combine($header, $row);
                $user = User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => Hash::make($userData['password']),
                ]);

                // Assign role from CSV
                if (isset($userData['role']) && !empty($userData['role'])) {
                    $user->assignRole($userData['role']);
                }
            }
            fclose($handle);
        }
    }
}
