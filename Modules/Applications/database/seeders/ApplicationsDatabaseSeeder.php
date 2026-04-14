<?php

namespace Modules\Applications\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Applications\Models\Applications;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ApplicationsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a test user if none exists
        $user = User::where('email', 'test@example.com')->first();
        if (!$user) {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        }

        // Create sample applications
        Applications::create([
            'name' => 'Sample Application 1',
            'description' => 'This is a sample application for testing purposes.',
            'status' => 'pending',
            'user_id' => $user->id,
            'metadata' => [
                'priority' => 'high',
                'category' => 'development',
            ],
            'submitted_at' => now(),
        ]);

        Applications::create([
            'name' => 'Approved Application',
            'description' => 'This application has been approved.',
            'status' => 'approved',
            'user_id' => $user->id,
            'metadata' => [
                'priority' => 'medium',
                'category' => 'design',
            ],
            'submitted_at' => now()->subDays(5),
        ]);

        Applications::create([
            'name' => 'Rejected Application',
            'description' => 'This application was rejected.',
            'status' => 'rejected',
            'user_id' => $user->id,
            'metadata' => [
                'priority' => 'low',
                'category' => 'maintenance',
            ],
            'submitted_at' => now()->subDays(10),
        ]);
    }
}
