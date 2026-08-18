<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
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
                'required',
                'string',
                'max:255',
                'unique:plans,name',
            ],
            'price' => [
                'nullable',
                'numeric',
                'decimal:0,2',
                'min:0',
            ],
            'max_programs' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'max_applicants' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'features' => [
                'nullable',
                'array',
            ],
            'features.*' => [
                'string',
                'max:255',
            ],
        ];
    }
}
