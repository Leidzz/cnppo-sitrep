<?php

namespace App\Policies;

use App\Models\Sitrep;
use App\Models\User;

class SitrepPolicy
{
    /**
     * Only reporting personnel may create SITREPs.
     * (The route middleware already gates the section; this is the
     * record-level check used by the Form Request, so the rule is
     * enforced even if the middleware is ever removed.)
     */
    public function create(User $user): bool
    {
        return $user->hasRole('reporting-personnel');
    }

    public function view(User $user, Sitrep $sitrep): bool
    {
        return $user->id === $sitrep->created_by
            || $user->hasAnyRole(['command-center', 'administrator']);
    }
}
