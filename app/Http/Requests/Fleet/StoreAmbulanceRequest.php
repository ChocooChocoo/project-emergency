<?php

namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAmbulanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Gated by route middleware can.perm:manage-fleet.
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'plate_no' => [
                'required', 'string', 'max:50',
                Rule::unique('ambulances', 'plate_no')->where('organization_id', $this->input('organization_id')),
            ],
            'unit_code' => ['nullable', 'string', 'max:50'],
            'vehicle_name' => ['nullable', 'string', 'max:100'],
            'vehicle_type' => ['nullable', 'string', 'max:100'],
            'tier' => ['nullable', 'in:bls,als'],
            'doh_credential_ref' => ['nullable', 'string', 'max:100'],
            'capacity_patients' => ['nullable', 'integer', 'min:1', 'max:20'],
            'equipment_notes' => ['nullable', 'string', 'max:1000'],
            'current_driver_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', 'string', 'max:50'],
            'is_serviceable' => ['nullable', 'boolean'],
            // Equipment flag checkboxes validated loosely (cast to bool in controller).
            'has_ventilator' => ['nullable', 'boolean'],
            'has_oxygen' => ['nullable', 'boolean'],
            'has_aed' => ['nullable', 'boolean'],
            'has_spine_board' => ['nullable', 'boolean'],
            'has_ob_kit' => ['nullable', 'boolean'],
            'has_stretcher' => ['nullable', 'boolean'],
        ];
    }
}
