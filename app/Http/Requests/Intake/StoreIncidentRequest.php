<?php

namespace App\Http\Requests\Intake;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public intake; anti-abuse handled by guest quota + throttle (S10 adds strikes).
    }

    public function rules(): array
    {
        $detailed = $this->input('request_type') === 'detailed';

        return [
            'request_type' => ['required', 'in:one_tap,detailed'],
            'pickup_lat' => ['required', 'numeric', 'between:-90,90'],
            'pickup_lng' => ['required', 'numeric', 'between:-180,180'],
            'pickup_address' => ['required', 'string', 'max:500'],
            'pickup_landmark' => ['nullable', 'string', 'max:255'],
            // Detailed-only fields.
            'patient_name' => [$detailed ? 'required' : 'nullable', 'string', 'max:150'],
            'contact_number' => [$detailed ? 'required' : 'nullable', 'string', 'max:50'],
            'incident_type' => ['nullable', 'string', 'max:100'],
            'severity' => ['nullable', 'integer', 'between:1,5'],
            'patient_count' => ['nullable', 'integer', 'min:1', 'max:99'],
            'request_summary' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
