<?php

namespace App\Http\Requests\Medical;

use Illuminate\Foundation\Http\FormRequest;

class StoreVitalsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Gated by route middleware can.perm:record-care.
    }

    public function rules(): array
    {
        return [
            'bp_systolic' => ['nullable', 'integer', 'between:40,300'],
            'bp_diastolic' => ['nullable', 'integer', 'between:20,200'],
            'pulse_rate' => ['nullable', 'integer', 'between:20,260'],
            'respiratory_rate' => ['nullable', 'integer', 'between:4,80'],
            'temperature_c' => ['nullable', 'numeric', 'between:25,45'],
            'oxygen_saturation' => ['nullable', 'integer', 'between:50,100'],
            'blood_glucose' => ['nullable', 'numeric', 'between:0,1000'],
            'gcs_score' => ['nullable', 'integer', 'between:3,15'],
            'pain_score' => ['nullable', 'integer', 'between:0,10'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
