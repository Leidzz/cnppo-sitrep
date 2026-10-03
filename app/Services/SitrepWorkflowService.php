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
    public function create(User $user, ReportType $reportType, array $dynamicFields, string $action, array $location = []): Sitrep
    {
        return DB::transaction(function () use ($user, $reportType, $dynamicFields, $action, $location) {
            // Filter out nulls AND empty strings so untouched hidden fields
            // never hit the database as '' (which would fail datetime columns).
            $location = array_filter($location, fn ($v) => $v !== null && $v !== '');

            $sitrep = Sitrep::create(array_merge([
                'reference_number' => $this->generateReferenceNumber(),
                'unit_id' => $user->unit_id,
                'report_type_id' => $reportType->id,
                'current_status_id' => $this->statusId('Draft'),
                'created_by' => $user->id,
                'dynamic_fields' => $dynamicFields,
            ], $location));

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
        // FRESH read via the relation query. Property access
        // ($sitrep->currentStatus) can return a STALE cached copy when this
        // model already transitioned earlier in the same request — e.g.
        // review() claims 'Under Review' first, then decides. The gate
        // must always validate against what the database actually says.
        $from = $sitrep->currentStatus()->firstOrFail();
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

    /**
     * Update an editable SITREP (Draft or Returned for Correction).
     *
     * - Guards the editable state (defense in depth on top of the policy)
     * - Writes an audit entry for the edit itself
     * - On submit: performs the correct transition (Draft -> Submitted, or
     *   Returned for Correction -> Submitted after corrections) — still
     *   validated against status_transitions inside transition()
     */
    public function updateDraft(Sitrep $sitrep, User $user, array $dynamicFields, array $location, string $action): Sitrep
    {
        return DB::transaction(function () use ($sitrep, $user, $dynamicFields, $location, $action) {
            $statusName = $sitrep->currentStatus->name;

            abort_unless(
                in_array($statusName, ['Draft', 'Returned for Correction']),
                403,
                'Only drafts and returned reports may be edited.'
            );

            $location = array_filter($location, fn ($v) => $v !== null && $v !== '');

            $sitrep->update(array_merge(['dynamic_fields' => $dynamicFields], $location));

            $this->log($sitrep, $user, 'updated', $statusName, $statusName, 'Report content updated.');

            if ($action === 'submit') {
                $remarks = $statusName === 'Returned for Correction'
                    ? 'Corrected and resubmitted by reporting personnel.'
                    : 'Draft submitted by reporting personnel.';

                $this->transition($sitrep, 'Submitted', $user, $remarks);
            }

            return $sitrep;
        });
    }

    /**
     * Record a review decision: 'validated' or 'returned'.
     *
     * Maps the decision to its workflow status, stores the reviewer's
     * remarks on the report, then routes through transition() so the
     * status_transitions role check + audit entry still apply exactly
     * once per move (rule 7). Policy + Form Request have already gated access.
     */
    public function review(Sitrep $sitrep, User $user, string $decision, ?string $remarks = null): Sitrep
    {
        return DB::transaction(function () use ($sitrep, $user, $decision, $remarks) {
            $statusName = match ($decision) {
                'validated' => 'Validated',
                'returned' => 'Returned for Correction',
                default => throw new \InvalidArgumentException("Unknown review decision [{$decision}]."),
            };

            // The seeded workflow requires Submitted -> Under Review before
            // any decision. Claim the review first, then decide.
            if ($sitrep->currentStatus->name === 'Submitted') {
                $this->transition($sitrep, 'Under Review', $user, 'Review started.');
            }

            $sitrep->reviewed_by = $user->id;
            $sitrep->review_remarks = $remarks;
            $sitrep->save();

            return $this->transition($sitrep, $statusName, $user, $remarks);
        });
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
