<?php

namespace App\Http\Requests;

use App\Models\Sitrep;
use App\Models\TemplateField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSitrepRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($sitrep = $this->route('sitrep')) {
            return $this->user()->can('update', $sitrep);
        }

        return $this->user()->can('create', Sitrep::class);
    }

    public function rules(): array
    {
        $rules = [
            'report_type_id' => ['required', 'exists:report_types,id'],
            'action' => ['required', 'in:draft,submit'],
            'dynamic_fields' => ['nullable', 'array'],
            'unit_id' => ['nullable', 'exists:units,id'],

            // Checkpoint Team Composition
            'personnel' => ['nullable', 'array', 'max:15'],
            'personnel.*.name' => ['required', 'string', 'max:255'],
            'personnel.*.position' => ['required', Rule::in(config('sitrep.positions'))],

            // GPS (all optional at this stage)
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'gps_accuracy_meters' => ['nullable', 'numeric', 'gt:0', 'lt:100000'],
            'location_captured_at' => ['nullable', 'date'],
            'location_description' => ['nullable', 'string', 'max:255'],

            // Photos: client-confirmed 3-5. On submit the request must reach
            // 3 total; photos already attached to a draft count toward it.
            'photos' => [
                $this->input('action') === 'submit' && $this->minPhotosNeeded() > 0 ? 'required' : 'nullable',
                'array',
                'min:'.$this->minPhotosNeeded(),
                'max:5',
            ],
            'photos.*' => ['file', 'mimetypes:image/jpeg,image/png,image/webp',
                'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];

        $reportTypeId = $this->input('report_type_id');

        if ($reportTypeId) {
            TemplateField::where('report_type_id', $reportTypeId)
                ->orderBy('sort_order')
                ->get()
                ->each(function (TemplateField $field) use (&$rules) {
                    $isRequired = $this->input('action') === 'submit' && $field->is_required;

                    $typeRules = match ($field->field_type) {
                        'date' => ['date'],
                        'time' => ['date_format:H:i'],
                        default => ['string', 'max:5000'],
                    };

                    $rules["dynamic_fields.{$field->field_key}"] = array_merge(
                        [$isRequired ? 'required' : 'nullable'],
                        $typeRules
                    );
                });
        }

        return $rules;
    }

    private function minPhotosNeeded(): int
    {
        $existing = 0;

        if ($sitrep = $this->route('sitrep')) {
            $existing = $sitrep->attachments()->count();
        }

        return max(0, 3 - $existing);
    }
}
