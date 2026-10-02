<?php

namespace App\Http\Requests;

use App\Models\Sitrep;
use App\Models\TemplateField;
use Illuminate\Foundation\Http\FormRequest;

class StoreSitrepRequest extends FormRequest
{
    /**
     * Authorize via the policy — validation and permission in one door.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Sitrep::class);
    }

    /**
     * Static rules first, then rules built FROM THE DATABASE for whichever
     * report type was chosen. This is rule 5 applied to validation:
     * the seeder/admin decides what's required; this code only reads it.
     */
    public function rules(): array
    {
        $rules = [
            'report_type_id' => ['required', 'exists:report_types,id'],
            'action' => ['required', 'in:draft,submit'],
            'dynamic_fields' => ['nullable', 'array'],

            // GPS / location documentation (spec Section 7 — all OPTIONAL)
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'gps_accuracy_meters' => ['nullable', 'numeric', 'gt:0', 'lt:100000'],
            'location_captured_at' => ['nullable', 'date'],
            'location_description' => ['nullable', 'string', 'max:255'],
        ];

        $reportTypeId = $this->input('report_type_id');

        if ($reportTypeId) {
            TemplateField::where('report_type_id', $reportTypeId)
                ->orderBy('sort_order')
                ->get()
                ->each(function (TemplateField $field) use (&$rules) {
                    // Required ONLY when: submitting AND the admin marked it required.
                    // Drafts may be incomplete; optional fields stay optional.
                    $isRequired = $this->input('action') === 'submit' && $field->is_required;

                    $rules["dynamic_fields.{$field->field_key}"] = [
                        $isRequired ? 'required' : 'nullable',
                        'string',
                        'max:5000',
                    ];
                });
        }

        return $rules;
    }
}
