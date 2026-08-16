<?php

namespace App\Http\Requests;

use App\Models\Application;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateApplicationRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $application = $this->route('application');
        $user = $this->user();

        return $application instanceof Application
            && $user !== null
            && $application->applicant_id === $user->id
            && $application->status === 'draft';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
