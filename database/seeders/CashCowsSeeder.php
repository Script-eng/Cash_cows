<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Contribution;
use App\Models\GroupAccount;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CashCowsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        $this->clearExistingData();
        
        // Create admin user
        $admin = $this->createAdminUser();
        
        // Create main group account
        $mainAccount = $this->createMainAccount();
        
        // Create users from Excel data
        $users = $this->createUsers();
        
        // Create contributions, fines, and welfare fees
        $this->createContributions($users, $admin, $mainAccount);
        
        // Update group account balance
        $this->updateAccountBalance($mainAccount);
        
        $this->command->info('Cash Cows data seeding completed successfully!');
    }
    
    /**
     * Clear existing data
     */
    private function clearExistingData(): void
    {
        $this->command->info('Clearing existing data...');
        
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Transaction::truncate();
        Contribution::truncate();
        // Check what role values are allowed in your database
        User::where('role', 'user')->delete(); // Assuming 'user' is the correct role value
        // Don't delete group accounts - just update balance later
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
    
    /**
     * Create admin user
     */
    private function createAdminUser()
    {
        $this->command->info('Creating admin user...');
        
        return User::firstOrCreate(
            ['email' => 'admin@cashcows.co.ke'],
            [
                'name' => 'Cash Cows Admin',
                'email_verified_at' => now(),
                'password' => Hash::make('CashCows@2024'), // Strong admin password
                'role' => 'admin', // Make sure 'admin' is an allowed value in your enum
                'remember_token' => Str::random(10),
            ]
        );
    }
    
    /**
     * Create main group account
     */
    private function createMainAccount()
    {
        $this->command->info('Creating main savings account...');
        
        return GroupAccount::firstOrCreate(
            ['name' => 'Main Savings'],
            [
                'description' => 'Main group savings account for Cash Cows',
                'balance' => 0,
            ]
        );
    }
    
    /**
     * Create users from Excel data
     */
    private function createUsers()
    {
        $this->command->info('Creating users from Excel data...');
        
        $users = [];
        
        // Member data from Excel file
        $memberData = $this->getMemberData();
        
        // Default password for all members - they can change it later
        $defaultPassword = 'CashCows2024'; 
        
        foreach ($memberData as $member) {
            // Generate a unique password using part of their phone number for added security
            // For example: CashCows2024_4383 (using last 4 digits of phone)
            $phoneDigits = substr($member['phone_number'], -4);
            $memberPassword = $defaultPassword . '_' . $phoneDigits;
            
            $user = User::firstOrCreate(
                ['email' => $member['email']],
                [
                    'name' => $member['name'],
                    'email_verified_at' => now(),
                    'password' => Hash::make($memberPassword), // Unique password per member
                    'role' => 'user', // Changed from 'member' to 'user'
                    'phone_number' => $member['phone_number'],
                    'remember_token' => Str::random(10),
                ]
            );
            
            // Store the plaintext password temporarily for admin reference
            $member['temp_password'] = $memberPassword;
            
            $users[$member['name']] = [
                'model' => $user,
                'data' => $member,
            ];
            
            $this->command->info("Created user {$member['name']} with password: {$memberPassword}");
        }
        
        // Create a password reference file for admin
        $this->createPasswordReferenceFile($users);
        
        $this->command->info('Created ' . count($users) . ' users.');
        
        return $users;
    }
    
    /**
     * Create a reference file with temporary passwords for admin
     */
    private function createPasswordReferenceFile($users)
    {
        $this->command->info('Creating password reference file for admin...');
        
        $content = "CASH COWS TEMPORARY PASSWORDS\n";
        $content .= "Generated on: " . date('Y-m-d H:i:s') . "\n\n";
        $content .= "IMPORTANT: This file contains temporary passwords. Please keep it secure and delete after use.\n\n";
        $content .= str_repeat('-', 80) . "\n";
        $content .= sprintf("%-30s | %-30s | %s\n", 'NAME', 'EMAIL', 'TEMPORARY PASSWORD');
        $content .= str_repeat('-', 80) . "\n";
        
        foreach ($users as $userName => $userData) {
            $member = $userData['data'];
            $content .= sprintf("%-30s | %-30s | %s\n", 
                $member['name'],
                $member['email'],
                $member['temp_password']
            );
        }
        
        $content .= str_repeat('-', 80) . "\n\n";
        $content .= "Instructions for members:\n";
        $content .= "1. Log in using the provided temporary password\n";
        $content .= "2. Go to your profile settings\n";
        $content .= "3. Change your password immediately for security\n";
        
        // Save to storage (in a secure location)
        $filePath = storage_path('app/member_passwords.txt');
        file_put_contents($filePath, $content);
        
        $this->command->info("Password reference file created at: {$filePath}");
    }
    
    /**
     * Create contributions, fines, and welfare fees
     */
    private function createContributions($users, $admin, $mainAccount)
    {
        $this->command->info('Creating contributions, fines, and welfare fees...');
        
        $contributionCount = 0;
        $fineCount = 0;
        $welfareCount = 0;
        
        // Contribution data (includes monthly contributions and OPC)
        $contributionData = $this->getContributionData();
        
        // Fines data
        $finesData = $this->getFinesData();
        
        // Welfare data
        $welfareData = $this->getWelfareData();
        
        // Process contributions for each member
        foreach ($users as $userName => $userData) {
            $user = $userData['model'];
            
            // Find member in contribution data - name might have different formatting
            $contributionMember = null;
            foreach ($contributionData as $name => $data) {
                if (stripos($name, $userName) !== false || stripos($userName, $name) !== false) {
                    $contributionMember = $data;
                    break;
                }
            }
            
            // Skip if no contribution data found
            if (!$contributionMember) {
                $this->command->warn("No contribution data found for: $userName");
                continue;
            }
            
            // Process monthly contributions (June to December)
            $months = ['June', 'July', 'August', 'September', 'October', 'November', 'December'];
            
            foreach ($months as $monthIdx => $month) {
                $amount = $contributionMember[$month] ?? 0;
                
                if ($amount > 0) {
                    // Create contribution record
                    $date = $this->getMonthDate($month);
                    
                    $contribution = Contribution::create([
                        'user_id' => $user->id,
                        'amount' => $amount,
                        'transaction_date' => $date,
                        'description' => "Monthly contribution for $month 2024",
                        'verification_status' => 'verified',
                        'verified_by' => $admin->id,
                    ]);
                    
                    // Create transaction record
                    Transaction::create([
                        'group_account_id' => $mainAccount->id,
                        'amount' => $amount,
                        'type' => 'deposit',
                        'description' => "Monthly contribution from {$user->name} for $month 2024",
                        'performed_by' => $admin->id,
                    ]);
                    
                    $contributionCount++;
                }
                
                // Find and add fines if any
                $fineMember = null;
                foreach ($finesData as $name => $data) {
                    if (stripos($name, $userName) !== false || stripos($userName, $name) !== false) {
                        $fineMember = $data;
                        break;
                    }
                }
                
                if ($fineMember && isset($fineMember[$month]) && $fineMember[$month] > 0) {
                    $fineAmount = $fineMember[$month];
                    $fineDate = $this->getMonthDate($month, 20); // Assume fines on the 20th
                    
                    Contribution::create([
                        'user_id' => $user->id,
                        'amount' => $fineAmount,
                        'transaction_date' => $fineDate,
                        'description' => "Late payment fine for $month 2024",
                        'verification_status' => 'verified',
                        'verified_by' => $admin->id,
                    ]);
                    
                    Transaction::create([
                        'group_account_id' => $mainAccount->id,
                        'amount' => $fineAmount,
                        'type' => 'deposit',
                        'description' => "Fine payment from {$user->name} for $month 2024",
                        'performed_by' => $admin->id,
                    ]);
                    
                    $fineCount++;
                }
                
                // Find and add welfare fees if any
                $welfareMember = null;
                foreach ($welfareData as $name => $data) {
                    if (stripos($name, $userName) !== false || stripos($userName, $name) !== false) {
                        $welfareMember = $data;
                        break;
                    }
                }
                
                if ($welfareMember && isset($welfareMember[$month]) && $welfareMember[$month] > 0) {
                    $welfareAmount = $welfareMember[$month];
                    $welfareDate = $this->getMonthDate($month, 5); // Assume welfare on the 5th
                    
                    Contribution::create([
                        'user_id' => $user->id,
                        'amount' => $welfareAmount,
                        'transaction_date' => $welfareDate,
                        'description' => "Welfare fee for $month 2024",
                        'verification_status' => 'verified',
                        'verified_by' => $admin->id,
                    ]);
                    
                    Transaction::create([
                        'group_account_id' => $mainAccount->id,
                        'amount' => $welfareAmount,
                        'type' => 'deposit',
                        'description' => "Welfare payment from {$user->name} for $month 2024",
                        'performed_by' => $admin->id,
                    ]);
                    
                    $welfareCount++;
                }
            }
            
            // Add OPC contribution if applicable
            if (isset($contributionMember['OPC']) && $contributionMember['OPC'] > 0) {
                $opcAmount = $contributionMember['OPC'];
                $opcDate = Carbon::create(2024, 12, 10); // OPC payment date
                
                Contribution::create([
                    'user_id' => $user->id,
                    'amount' => $opcAmount,
                    'transaction_date' => $opcDate,
                    'description' => "Olpajeta trip contribution",
                    'verification_status' => 'verified',
                    'verified_by' => $admin->id,
                ]);
                
                Transaction::create([
                    'group_account_id' => $mainAccount->id,
                    'amount' => $opcAmount,
                    'type' => 'deposit',
                    'description' => "OPC contribution from {$user->name}",
                    'performed_by' => $admin->id,
                ]);
                
                $contributionCount++;
            }
            
            // Add registration fee
            $regFee = $userData['data']['registration_fee'] ?? 0;
            if ($regFee > 0) {
                $regDate = Carbon::create(2024, 6, 1); // Registration date
                
                Contribution::create([
                    'user_id' => $user->id,
                    'amount' => $regFee,
                    'transaction_date' => $regDate,
                    'description' => "Registration fee",
                    'verification_status' => 'verified',
                    'verified_by' => $admin->id,
                ]);
                
                Transaction::create([
                    'group_account_id' => $mainAccount->id,
                    'amount' => $regFee,
                    'type' => 'deposit',
                    'description' => "Registration fee from {$user->name}",
                    'performed_by' => $admin->id,
                ]);
                
                $contributionCount++;
            }
        }
        
        $this->command->info("Created $contributionCount contributions, $fineCount fines, and $welfareCount welfare payments.");
    }
    
    /**
     * Update account balance
     */
    private function updateAccountBalance($mainAccount)
    {
        $this->command->info('Updating group account balance...');
        
        // Calculate total balance from all transactions
        $totalBalance = Transaction::where('group_account_id', $mainAccount->id)
            ->where('type', 'deposit')
            ->sum('amount');
            
        // Update account balance
        $mainAccount->update(['balance' => $totalBalance]);
        
        $this->command->info("Updated main account balance to: $totalBalance");
    }
    
    /**
     * Get month date (15th of each month by default)
     */
    private function getMonthDate($month, $day = 15)
    {
        $monthMap = [
            'January' => 1,
            'February' => 2,
            'March' => 3,
            'April' => 4,
            'May' => 5,
            'June' => 6,
            'July' => 7,
            'August' => 8,
            'September' => 9,
            'October' => 10,
            'November' => 11,
            'December' => 12,
        ];
        
        $monthNumber = $monthMap[$month] ?? null;
        
        if (!$monthNumber) {
            throw new \Exception("Invalid month: $month");
        }
        
        return Carbon::create(2024, $monthNumber, $day);
    }
    
    /**
     * Get member data from Excel
     */
    private function getMemberData()
    {
        // This would typically come from parsing the Excel file
        // For this example, we'll use hardcoded data based on our Excel analysis
        return [
            [
                'name' => 'Benson Larpei',
                'id_number' => '33955712',
                'phone_number' => '795154383',
                'email' => 'bensonlarpei@gmail.com',
                'total_contribution' => 15060,
                'registration_fee' => 360,
            ],
            [
                'name' => 'David tanap larpei',
                'id_number' => '35384382',
                'phone_number' => '717562990',
                'email' => 'davidtanap188@gmail.com',
                'total_contribution' => 15700,
                'registration_fee' => 1000,
            ],
            [
                'name' => 'James larpei Ltimayion',
                'id_number' => '22816819',
                'phone_number' => '716882327',
                'email' => 'jameslarpei@gmail.com',
                'total_contribution' => 15700,
                'registration_fee' => 1000,
            ],
            [
                'name' => 'James Rukati Lelarpei',
                'id_number' => '33477180',
                'phone_number' => '791348416',
                'email' => 'jameslelarpei36@gmail.com',
                'total_contribution' => 15700,
                'registration_fee' => 1000,
            ],
            [
                'name' => 'Larpei Andrew Kanari',
                'id_number' => '37303066',
                'phone_number' => '726420259',
                'email' => 'Andrewlarpei@gmail.com',
                'total_contribution' => 15060,
                'registration_fee' => 360,
            ],
            [
                'name' => 'Larpei Isaac Andason ',
                'id_number' => '35695869',
                'phone_number' => '792199383',
                'email' => 'isaacandason43@gmail.com',
                'total_contribution' => 15060,
                'registration_fee' => 360,
            ],
            [
                'name' => 'Larpei Joseph Ljerina',
                'id_number' => '28718033',
                'phone_number' => '722828811',
                'email' => 'josephlarpei69@gmail.com',
                'total_contribution' => 15060,
                'registration_fee' => 360,
            ],
            [
                'name' => 'Larpei Nichol Loshumu ',
                'id_number' => '35684507',
                'phone_number' => '726644608',
                'email' => 'nichollarpeiloshumu@gmail.com',
                'total_contribution' => 15060,
                'registration_fee' => 360,
            ],
            [
                'name' => 'Larpei Samuel Semeiyan',
                'id_number' => '11765461',
                'phone_number' => '723316010',
                'email' => 'larpeisamuel100@gmail.com',
                'total_contribution' => 15060,
                'registration_fee' => 360,
            ],
            [
                'name' => 'larpei Stanely Tompoi',
                'id_number' => '30417136',
                'phone_number' => '748992773',
                'email' => 'stanleytompoi@gmail.com',
                'total_contribution' => 15060,
                'registration_fee' => 360,
            ],
            [
                'name' => 'Lelaly larpei Jacob',
                'id_number' => '30385483',
                'phone_number' => '759202017',
                'email' => 'jacoblelaly@gmail.com',
                'total_contribution' => 15700,
                'registration_fee' => 1000,
            ],
            [
                'name' => 'Lentoijoni Larpei Samuel',
                'id_number' => '11770583',
                'phone_number' => '722845423',
                'email' => 'samuellentoijoni@gmail.com',
                'total_contribution' => 15700,
                'registration_fee' => 1000,
            ],
            [
                'name' => 'Lentukunye Larpei Edwin',
                'id_number' => '32918057',
                'phone_number' => '710876522',
                'email' => 'lucelentukunye@gmail.com',
                'total_contribution' => 15700,
                'registration_fee' => 1000,
            ],
            [
                'name' => 'Lmeisiaya Larpei Paul',
                'id_number' => '28587800',
                'phone_number' => '723468894',
                'email' => 'paullmeisiaya@gmail.com',
                'total_contribution' => 15060,
                'registration_fee' => 360,
            ],
            [
                'name' => 'Lucy Larpei',
                'id_number' => '32423625',
                'phone_number' => '713003881',
                'email' => 'lucylarpei@gmail.com',
                'total_contribution' => 15060,
                'registration_fee' => 360,
            ],
            [
                'name' => 'Pesinoi Larpei Stephen',
                'id_number' => '35051254',
                'phone_number' => '708235010',
                'email' => 'stephenlarpei96@gmail.com',
                'total_contribution' => 15700,
                'registration_fee' => 1000,
            ],
            [
                'name' => 'Rokorori Larpei Joshua',
                'id_number' => '35656422',
                'phone_number' => '796644782',
                'email' => 'joshrokorori@gmail.com',
                'total_contribution' => 15700,
                'registration_fee' => 1000,
            ],
        ];
    }
    
    /**
     * Get contribution data from Excel
     */
    private function getContributionData()
    {
        // This would typically come from parsing the Excel file
        // For this example, we'll use hardcoded data based on our Excel analysis
        return [
            'Benson Malei' => [
                'Total' => 17200,
                'June' => 2000,
                'July' => 2000,
                'August' => 2050,
                'September' => 2050,
                'October' => 2200,
                'November' => 2200,
                'December' => 2200,
                'OPC' => 2500,
            ],
            'Andrew Kanari' => [
                'Total' => 17200,
                'June' => 2000,
                'July' => 2000,
                'August' => 2050,
                'September' => 2050,
                'October' => 2200,
                'November' => 2200,
                'December' => 2200,
                'OPC' => 2500,
            ],
            'Isaac Andason' => [
                'Total' => 17200,
                'June' => 2000,
                'July' => 2000,
                'August' => 2050,
                'September' => 2050,
                'October' => 2200,
                'November' => 2200,
                'December' => 2200,
                'OPC' => 2500,
            ],
            'Ljerina Joseph' => [
                'Total' => 17200,
                'June' => 2000,
                'July' => 2000,
                'August' => 2050,
                'September' => 2050,
                'October' => 2200,
                'November' => 2200,
                'December' => 2200,
                'OPC' => 2500,
            ],
            'Nicholas loshumu' => [
                'Total' => 17200,
                'June' => 2000,
                'July' => 2000,
                'August' => 2050,
                'September' => 2050,
                'October' => 2200,
                'November' => 2200,
                'December' => 2200,
                'OPC' => 2500,
            ],
            'Samuel Semeiyan' => [
                'Total' => 17200,
                'June' => 2000,
                'July' => 2000,
                'August' => 2050,
                'September' => 2050,
                'October' => 2200,
                'November' => 2200,
                'December' => 2200,
                'OPC' => 2500,
            ],
            'Stanley Tompoi' => [
                'Total' => 17200,
                'June' => 2000,
                'July' => 2000,
                'August' => 2050,
                'September' => 2050,
                'October' => 2200,
                'November' => 2200,
                'December' => 2200,
                'OPC' => 2500,
            ],
            'Joshua Rokorori' => [
                'Total' => 17200,
                'June' => 2000,
                'July' => 2000,
                'August' => 2050,
                'September' => 2050,
                'October' => 2200,
                'November' => 2200,
                'December' => 2200,
                'OPC' => 2500,
            ],
            'Jacob Lelaly' => [
                'Total' => 17200,
                'June' => 2000,
                'July' => 2000,
                'August' => 2050,
                'September' => 2050,
                'October' => 2200,
                'November' => 2200,
                'December' => 2200,
                'OPC' => 2500,
            ],
            'Samuel Lentoijoni' => [
                'Total' => 17200,
                'June' => 2000,
                'July' => 2000,
                'August' => 2050,
                'September' => 2050,
                'October' => 2200,
                'November' => 2200,
                'December' => 2200,
                'OPC' => 2500,
            ],
            'Edwin Lentukunye' => [
                'Total' => 17200,
                'June' => 2000,
                'July' => 2000,
                'August' => 2050,
                'September' => 2050,
                'October' => 2200,
                'November' => 2200,
                'December' => 2200,
                'OPC' => 2500,
            ],
            'Paul Lmeisiaya' => [
                'Total' => 17200,
                'June' => 2000,
                'July' => 2000,
                'August' => 2050,
                'September' => 2050,
                'October' => 2200,
                'November' => 2200,
                'December' => 2200,
                'OPC' => 2500,
            ],
            'Lucy Larpei' => [
                'Total' => 17200,
                'June' => 2000,
                'July' => 2000,
                'August' => 2050,
                'September' => 2050,
                'October' => 2200,
                'November' => 2200,
                'December' => 2200,
                'OPC' => 2500,
            ],
            'Stephen Pesinoi' => [
                'Total' => 17200,
                'June' => 2000,
                'July' => 2000,
                'August' => 2050,
                'September' => 2050,
                'October' => 2200,
                'November' => 2200,
                'December' => 2200,
                'OPC' => 2500,
            ],
            'David Tanap' => [
                'Total' => 17200,
                'June' => 2000,
                'July' => 2000,
                'August' => 2050,
                'September' => 2050,
                'October' => 2200,
                'November' => 2200,
                'December' => 2200,
                'OPC' => 2500,
            ],
            'James Ltimayion' => [
                'Total' => 17200,
                'June' => 2000,
                'July' => 2000,
                'August' => 2050,
                'September' => 2050,
                'October' => 2200,
                'November' => 2200,
                'December' => 2200,
                'OPC' => 2500,
            ],
            'James Rukati' => [
                'Total' => 17200,
                'June' => 2000,
                'July' => 2000,
                'August' => 2050,
                'September' => 2050,
                'October' => 2200,
                'November' => 2200,
                'December' => 2200,
                'OPC' => 2500,
            ],
        ];
    }
    
    /**
     * Get fines data from Excel
     */
    private function getFinesData()
    {
        // This would typically come from parsing the Excel file
        // For this example, we'll use hardcoded data based on our Excel analysis
        return [
            'Benson Malei' => [
                'Total' => 100,
                'August' => 0,
                'September' => 50,
                'October' => 50,
                'November' => 0,
                'December' => 0,
            ],
            'Andrew Kanari' => [
                'Total' => 250,
                'August' => 200,
                'September' => 0,
                'October' => 50,
                'November' => 0,
                'December' => 0,
            ],
            'Isaac Andason' => [
                'Total' => 600,
                'August' => 200,
                'September' => 0,
                'October' => 0,
                'November' => 200,
                'December' => 200,
            ],
            'Ljerina Joseph' => [
                'Total' => 50,
                'August' => 0,
                'September' => 0,
                'October' => 50,
                'November' => 0,
                'December' => 0,
            ],
            'Nicholas loshumu' => [
                'Total' => 450,
                'August' => 200,
                'September' => 0,
                'October' => 50,
                'November' => 0,
                'December' => 200,
            ],
            // Add more members as needed
        ];
    }
    
    /**
     * Get welfare data from Excel
     */
    /**
     * Get welfare data from Excel
     */
    private function getWelfareData()
    {
        // This would typically come from parsing the Excel file
        // For this example, we'll use hardcoded data based on our Excel analysis
        return [
            'Benson Malei' => [
                'June' => 100,
                'July' => 100,
                'August' => 100,
                'September' => 100,
                'October' => 100,
                'November' => 100,
                'December' => 100,
                'Total' => 700,
            ],
            'Andrew Kanari' => [
                'June' => 100,
                'July' => 100,
                'August' => 100,
                'September' => 100,
                'October' => 100,
                'November' => 100,
                'December' => 100,
                'Total' => 700,
            ],
            'Isaac Andason' => [
                'June' => 100,
                'July' => 100,
                'August' => 100,
                'September' => 100,
                'October' => 100,
                'November' => 100,
                'December' => 100,
                'Total' => 700,
            ],
            'Ljerina Joseph' => [
                'June' => 100,
                'July' => 100,
                'August' => 100,
                'September' => 100,
                'October' => 100,
                'November' => 100,
                'December' => 100,
                'Total' => 700,
            ],
            'Nicholas loshumu' => [
                'June' => 100,
                'July' => 100,
                'August' => 100,
                'September' => 100,
                'October' => 100,
                'November' => 100,
                'December' => 100,
                'Total' => 700,
            ],
            'Samuel Semeiyan' => [
                'June' => 100,
                'July' => 100,
                'August' => 100,
                'September' => 100,
                'October' => 100,
                'November' => 100,
                'December' => 100,
                'Total' => 700,
            ],
            'Stanley Tompoi' => [
                'June' => 100,
                'July' => 100,
                'August' => 100,
                'September' => 100,
                'October' => 100,
                'November' => 100,
                'December' => 100,
                'Total' => 700,
            ],
            'Joshua Rokorori' => [
                'June' => 100,
                'July' => 100,
                'August' => 100,
                'September' => 100,
                'October' => 100,
                'November' => 100,
                'December' => 100,
                'Total' => 700,
            ],
            'Jacob Lelaly' => [
                'June' => 100,
                'July' => 100,
                'August' => 100,
                'September' => 100,
                'October' => 100,
                'November' => 100,
                'December' => 100,
                'Total' => 700,
            ],
            'Samuel Lentoijoni' => [
                'June' => 100,
                'July' => 100,
                'August' => 100,
                'September' => 100,
                'October' => 100,
                'November' => 100,
                'December' => 100,
                'Total' => 700,
            ],
            'Edwin Lentukunye' => [
                'June' => 100,
                'July' => 100,
                'August' => 100,
                'September' => 100,
                'October' => 100,
                'November' => 100,
                'December' => 100,
                'Total' => 700,
            ],
            'Paul Lmeisiaya' => [
                'June' => 100,
                'July' => 100,
                'August' => 100,
                'September' => 100,
                'October' => 100,
                'November' => 100,
                'December' => 100,
                'Total' => 700,
            ],
            'Lucy Larpei' => [
                'June' => 100,
                'July' => 100,
                'August' => 100,
                'September' => 100,
                'October' => 100,
                'November' => 100,
                'December' => 100,
                'Total' => 700,
            ],
            'Stephen Pesinoi' => [
                'June' => 100,
                'July' => 100,
                'August' => 100,
                'September' => 100,
                'October' => 100,
                'November' => 100,
                'December' => 100,
                'Total' => 700,
            ],
            'David Tanap' => [
                'June' => 100,
                'July' => 100,
                'August' => 100,
                'September' => 100,
                'October' => 100,
                'November' => 100,
                'December' => 100,
                'Total' => 700,
            ],
            'James Ltimayion' => [
                'June' => 100,
                'July' => 100,
                'August' => 100,
                'September' => 100,
                'October' => 100,
                'November' => 100,
                'December' => 100,
                'Total' => 700,
            ],
            'James Rukati' => [
                'June' => 100,
                'July' => 100,
                'August' => 100,
                'September' => 100,
                'October' => 100,
                'November' => 100,
                'December' => 100,
                'Total' => 700,
            ],
        ];
    }
}