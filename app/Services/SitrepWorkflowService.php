<?php

namespace App\Services;

use App\Exceptions\InvalidStatusTransition;
use App\Models\AuditLog;
use App\Models\ReportType;
use App\Models\Sitrep;
use App\Models\Status;
use App\Models\StatusTransition;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SitrepWorkflowService
{
    /**
     * Create a SITREP in Draft status (with reference number + audit entry).
     * If $action is 'submit', immediately transition Draft -> Submitted.
     * Wrapped in a DB transaction: either the SITREP + audit entries all
     * persist, or nothing does — no half-saved reports.
     */
    public function create(User $user, ReportType $reportType, array $dynamicFields, string $action): Sitrep
    {
        return DB::transaction(function () use ($user, $reportType, $dynamicFields, $action) {
            $sitrep = Sitrep::create([
                'reference_number' => $this->generateReferenceNumber(),
                'unit_id' => $user->unit_id,          // officer's own station, always
                'report_type_id' => $reportType->id,
                'current_status_id' => $this->statusId('Draft'),
                'created_by' => $user->id,
                'dynamic_fields' => $dynamicFields,
            ]);

            $this->log($sitrep, $user, 'created', null, 'Draft', 'SITREP created.');

            if ($action === 'submit') {
                $this->transition($sitrep, 'Submitted', $user, 'Submitted by reporting personnel.');
            }

            return $sitrep;
        });
    }

    /**
     * THE ONLY place a SITREP's status may change. Controllers never touch
     * current_status_id directly (rule 7). Three jobs:
     * 1. Prove the transition exists in status_transitions for one of the user's roles
     * 2. Move the status + stamp the matching workflow timestamp
     * 3. Write the audit entry
     */
    public function transition(Sitrep $sitrep, string $toStatusName, User $user, ?string $remarks = null): Sitrep
    {
        $from = $sitrep->currentStatus;
        $to = Status::where('name', $toStatusName)->firstOrFail();

        $isAllowed = StatusTransition::query()
            ->where('from_status_id', $from->id)
            ->where('to_status_id', $to->id)
            ->whereIn('allowed_role', $user->getRoleNames())
            ->exists();

        if (! $isAllowed) {
            throw new InvalidStatusTransition(
                "Transition [{$from->name}] -> [{$to->name}] is not allowed for your role."
            );
        }

        $sitrep->current_status_id = $to->id;

        // Stamp the workflow timestamp that belongs to THIS transition,
        // using the server clock (Asia/Manila) — never the device clock.
        match ($to->name) {
            'Submitted' => $sitrep->submitted_at = now(),
            'Validated', 'Returned for Correction' => $sitrep->reviewed_at = now(),
            default => null,
        };

        $sitrep->save();
        $this->log($sitrep, $user, 'status_changed', $from->name, $to->name, $remarks);

        return $sitrep;
    }

    private function statusId(string $name): int
    {
        return Status::where('name', $name)->firstOrFail()->id;
    }

    private function generateReferenceNumber(): string
    {
        $sequence = str_pad((string) ((Sitrep::max('id') ?? 0) + 1), 4, '0', STR_PAD_LEFT);

        return 'SITREP-'.now()->format('Y').'-'.$sequence;
    }

    private function log(Sitrep $sitrep, User $user, string $action, ?string $from, ?string $to, ?string $remarks): void
    {
        AuditLog::create([
            'user_id' => $user->id,
            'sitrep_id' => $sitrep->id,
            'action' => $action,
            'previous_status' => $from,
            'new_status' => $to,
            'remarks' => $remarks,
        ]);
    }
}
