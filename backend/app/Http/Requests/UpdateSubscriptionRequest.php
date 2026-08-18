<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class UpdateSubscriptionRequest extends FormRequest
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
            'status' => [
                'sometimes',
                'required',
                'in:active,expired,cancelled',
            ],
            'start_date' => [
                'sometimes',
                'required',
                'date',
            ],
            'end_date' => [
                'sometimes',
                'nullable',
                'date',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value === null) {
                        return;
                    }

                    $subscription = $this->route('subscription');
                    $startDate = $this->input('start_date')
                        ?? $subscription?->start_date;

                    if ($startDate !== null
                        && Carbon::parse($value)->lt(Carbon::parse($startDate))) {
                        $fail('The end date must be on or after the start date.');
                    }
                },
            ],
        ];
    }
}
