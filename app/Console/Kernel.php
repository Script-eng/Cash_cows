<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    // Your existing code...
    
    protected $commands = [
        \App\Console\Commands\CheckUsersTableCommand::class,
        // Other commands...
    ];
    
    // Add this property if it doesn't exist
    protected $middlewareAliases = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        // Any other middleware you already have...
        
        // Add this line:
        'role' => \App\Http\Middleware\CheckRole::class,
    ];
}