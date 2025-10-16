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
            'name' => config('app.admin_name', 'Admin'),
            'email' => config('app.admin_email', 'admin@example.com'),
            'password' => Hash::make('12345678'),
        ]);
        $admin->assignRole('super_admin');

        // Create users from CSV fixtures
        $csvPath = database_path('fixtures/users.csv');
        if (($handle = fopen($csvPath, 'r')) !== false) {
            $header = fgetcsv($handle);

            // Ensure header is valid
            if ($header === false || empty($header)) {
                fclose($handle);
                return;
            }

            // Convert header values from string|null to string, filtering out nulls
            $header = array_map(fn($value) => $value ?? '', $header);

            while (($row = fgetcsv($handle)) !== false) {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Ensure row has same length as header
                if (count($row) !== count($header)) {
                    continue;
                }

                // Convert row values from string|null to string, filtering out nulls
                $row = array_map(fn($value) => $value ?? '', $row);

                /** @var array<string, string> $userData */
                $userData = array_combine($header, $row);

                // Validate required fields exist and are not empty
                if (!isset($userData['name'], $userData['email'], $userData['password']) ||
                    $userData['name'] === '' ||
                    $userData['email'] === '' ||
                    $userData['password'] === '') {
                    continue;
                }

                $user = User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => Hash::make($userData['password']),
                ]);

                // Assign role from CSV
                if (isset($userData['role']) && $userData['role'] !== '') {
                    $user->assignRole($userData['role']);
                }
            }
            fclose($handle);
        }
    }
}
