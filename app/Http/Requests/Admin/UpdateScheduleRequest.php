<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateScheduleRequest extends FormRequest
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
            'days' => ['required', 'array', 'size:7'],
            'days.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'days.*.enabled' => ['required', 'boolean'],
            'days.*.start_time' => ['nullable', 'date_format:H:i'],
            'days.*.end_time' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            foreach ($this->input('days', []) as $index => $day) {
                if (! ($day['enabled'] ?? false)) {
                    continue;
                }

                if (empty($day['start_time'])) {
                    $v->errors()->add("days.$index.start_time", 'Start time is required when the day is enabled.');
                }

                if (empty($day['end_time'])) {
                    $v->errors()->add("days.$index.end_time", 'End time is required when the day is enabled.');
                }

                if (! empty($day['start_time']) && ! empty($day['end_time']) && $day['end_time'] <= $day['start_time']) {
                    $v->errors()->add("days.$index.end_time", 'End time must be after start time.');
                }
            }
        });
    }
}
