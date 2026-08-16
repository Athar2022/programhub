<?php

namespace App\Http\Requests;

use App\Models\Application;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewApplicationRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $application = $this->route('application');

        if (! $application instanceof Application) {
            return false;
        }

        return $this->user()?->can('review', $application) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in(['under_review', 'accepted', 'rejected']),
            ],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
