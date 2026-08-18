<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('plans', 'name')->ignore($this->route('plan')),
            ],
            'price' => [
                'sometimes',
                'numeric',
                'decimal:0,2',
                'min:0',
            ],
            'max_programs' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
            ],
            'max_applicants' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
            ],
            'features' => [
                'sometimes',
                'nullable',
                'array',
            ],
            'features.*' => [
                'string',
                'max:255',
            ],
            'status' => [
                'sometimes',
                'required',
                'in:active,inactive',
            ],
        ];
    }
}
