<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReportPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Report $report)
    {
        return $user->id === $report->user_id || $user->isAdmin();
    }

    public function create(User $user)
    {
        return true; // All users can create reports
    }

    public function delete(User $user, Report $report)
    {
        return $user->id === $report->user_id || $user->isAdmin();
    }
}