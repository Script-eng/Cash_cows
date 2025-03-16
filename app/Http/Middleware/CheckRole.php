<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    // public function handle(Request $request, Closure $next, string $role): Response
    // {
    //     if ($request->user()->role !== $role) {
    //         abort(403, 'Unauthorized action.');
    //     }

    //     return $next($request);
    // }

    /**
     * Handle an incoming request.
     *
    //  * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if ($request->user() && $request->user()->role === $role) {
            return $next($request);
        }

        return redirect()->route('home')->with('error', 'You do not have permission to access this area.');
    }
}