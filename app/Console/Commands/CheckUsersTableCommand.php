<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckUsersTableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:check-structure';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the structure of the users table, particularly the role column';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking users table structure...');

        if (!Schema::hasTable('users')) {
            $this->error('Users table does not exist!');
            return 1;
        }

        // Get all columns and their details
        $columnsInfo = [];
        $columns = Schema::getColumnListing('users');
        
        foreach ($columns as $column) {
            $columnsInfo[$column] = [
                'type' => DB::connection()->getDoctrineColumn('users', $column)->getType()->getName(),
                'nullable' => !DB::connection()->getDoctrineColumn('users', $column)->getNotnull(),
                'default' => DB::connection()->getDoctrineColumn('users', $column)->getDefault(),
            ];
        }
        
        $this->info('Users table columns:');
        $this->table(
            ['Column', 'Type', 'Nullable', 'Default'],
            collect($columnsInfo)->map(function ($info, $column) {
                return [
                    $column,
                    $info['type'],
                    $info['nullable'] ? 'Yes' : 'No',
                    $info['default'] ?? 'NULL',
                ];
            })->toArray()
        );

        // Get more details about the role column
        if (in_array('role', $columns)) {
            // For MySQL, check if it's an ENUM and get allowed values
            if (DB::connection()->getDriverName() === 'mysql') {
                // Get the column definition directly from MySQL
                $columnInfo = DB::select("SHOW COLUMNS FROM users WHERE Field = 'role'")[0];
                
                if (str_starts_with($columnInfo->Type, 'enum')) {
                    // Extract values from enum type definition (format: enum('val1','val2'))
                    preg_match("/enum\((.*)\)/", $columnInfo->Type, $matches);
                    $enumStr = $matches[1] ?? '';
                    $enumValues = array_map(function($value) {
                        return trim($value, "'\"");
                    }, explode(',', $enumStr));
                    
                    $this->info("Role column is an ENUM with these values: " . implode(', ', $enumValues));
                    
                    // Based on the error, suggest modifications
                    if (!in_array('member', $enumValues)) {
                        $this->warn("'member' is not a valid value for the role column.");
                        $this->info("To fix this issue, run a migration to either:");
                        $this->info("1. Add 'member' to the ENUM values, or");
                        $this->info("2. Use 'user' instead of 'member' in your seeder");
                    }
                }
            }
        } else {
            $this->warn("Role column not found in users table!");
        }

        return 0;
    }
}