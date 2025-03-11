<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Contribution;
use App\Models\GroupAccount;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create main group account
        $mainAccount = GroupAccount::firstOrCreate(
            ['name' => 'Main Savings'],
            [
                'description' => 'Main group savings account',
                'balance' => 0,
            ]
        );

        // Create 10 regular users
        $users = [];
        for ($i = 1; $i <= 10; $i++) {
            $users[] = User::create([
                'name' => "User $i",
                'email' => "user$i@example.com",
                'password' => Hash::make('password'),
                'role' => 'user',
                'phone_number' => "07" . str_pad(mt_rand(1000000, 9999999), 8, '0', STR_PAD_LEFT),
                'email_verified_at' => now(),
            ]);
        }

        // Create contributions for the past 10 months
        // 2/3 (7 users) will have inconsistent savings
        $inconsistentUsers = array_slice($users, 0, 7);
        $consistentUsers = array_slice($users, 7, 3);

        // Get admin user for verification
        $admin = User::where('role', 'admin')->first();
        
        if (!$admin) {
            // Create admin if not exists
            $admin = User::create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);
        }

        $totalBalance = 0;

        // Generate contributions for the past 10 months
        for ($month = 9; $month >= 0; $month--) {
            $date = Carbon::now()->subMonths($month)->setDay(rand(1, 28));
            
            // Contributions for consistent users (always 2200)
            foreach ($consistentUsers as $user) {
                $amount = 2200;
                
                $contribution = Contribution::create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'transaction_date' => $date,
                    'description' => "Monthly contribution for " . $date->format('F Y'),
                    'verification_status' => 'verified',
                    'verified_by' => $admin->id,
                ]);
                
                $totalBalance += $amount;
            }
            
            // Contributions for inconsistent users (varying amounts, some missing months)
            foreach ($inconsistentUsers as $user) {
                // 20% chance of missing a month
                if (rand(1, 100) > 20) {
                    // Varying amounts: 70% of the time between 1000-2000, 30% full amount
                    $amount = (rand(1, 100) <= 70) 
                        ? rand(1000, 2000) 
                        : 2200;
                    
                    $contribution = Contribution::create([
                        'user_id' => $user->id,
                        'amount' => $amount,
                        'transaction_date' => $date,
                        'description' => "Monthly contribution for " . $date->format('F Y'),
                        'verification_status' => 'verified',
                        'verified_by' => $admin->id,
                    ]);
                    
                    $totalBalance += $amount;
                }
            }
        }
        
        // Create some pending contributions for the current month
        foreach ($users as $user) {
            // 60% chance of having a pending contribution
            if (rand(1, 100) <= 60) {
                $amount = rand(1500, 2200);
                
                Contribution::create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'transaction_date' => Carbon::now()->setDay(rand(1, Carbon::now()->day)),
                    'description' => "Monthly contribution for " . Carbon::now()->format('F Y'),
                    'verification_status' => 'pending',
                ]);
            }
        }
        
        // Update main account balance
        $mainAccount->update(['balance' => $totalBalance]);
    }
}