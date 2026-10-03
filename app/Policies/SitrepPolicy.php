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

    /**
     * Who may SEE a SITREP and its photos:
     * - its author (reporting personnel)
     * - command center (their job: review)
     * - administrators
     */
    public function view(User $user, Sitrep $sitrep): bool
    {
        return $user->id === $sitrep->created_by
            || $user->hasAnyRole(['command-center', 'administrator']);
    }

    /**
     * Who may EDIT a SITREP: its author, and ONLY while it's in an
     * editable state. Submitted/Validated reports are locked —
     * changes after submission happen through status transitions
     * (the workflow service), never through silent edits.
     */
    public function update(User $user, Sitrep $sitrep): bool
    {
        return $user->id === $sitrep->created_by
            && in_array($sitrep->currentStatus->name, ['Draft', 'Returned for Correction']);
    }
}
