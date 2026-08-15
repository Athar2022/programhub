<?php

namespace App\Http\Requests;

use App\Models\Organization;
use App\Models\Program;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProgramRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $organization = $this->route('organization');

        if (! $organization instanceof Organization) {
            return false;
        }

        return $this->user()?->can('create', [Program::class, $organization]) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'delivery_mode' => ['nullable', 'string', 'max:100'],
            'application_start' => ['nullable', 'date'],
            'application_deadline' => ['nullable', 'date', 'after_or_equal:application_start'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'capacity' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
