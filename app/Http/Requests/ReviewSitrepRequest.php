<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewSitrepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('review', $this->route('sitrep'));
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['validated', 'returned'])],

            // A RETURNED report must carry the reviewer's reason (spec Section 11).
            // Validated reports may carry optional remarks.
            'remarks' => [
                $this->input('decision') === 'returned' ? 'required' : 'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
