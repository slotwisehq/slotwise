<?php

namespace App\Http\Requests\Admin;

use App\Enums\AppointmentStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class CancelBookingRequest extends FormRequest
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
        return [];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $appointment = $this->route('appointment');
            $cancellable = [AppointmentStatus::Pending, AppointmentStatus::Confirmed];

            if (! in_array($appointment->status, $cancellable)) {
                $v->errors()->add('status', 'This appointment cannot be cancelled.');
            }
        });
    }
}
