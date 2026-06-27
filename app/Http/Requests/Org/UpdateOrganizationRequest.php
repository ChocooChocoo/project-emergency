<?php

namespace App\Http\Requests\Org;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Gated by route middleware can.perm:manage-organizations.
    }

    public function rules(): array
    {
        $orgId = $this->route('organization')->id;

        return [
            'name' => ['required', 'string', 'max:150'],
            'org_type' => ['required', 'string', 'max:50'],
            'org_acronym' => ['nullable', 'string', 'max:80'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('organizations', 'code')->ignore($orgId)],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'dispatch_hotline_ops' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'service_city' => ['nullable', 'string', 'max:100'],
            'service_type' => ['nullable', 'string', 'max:100'],
            'is_24_7' => ['nullable', 'boolean'],
            'admin_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'covered_barangays_json' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
