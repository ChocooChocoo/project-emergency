<?php

namespace App\Http\Requests\Dispatch;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Gated by route middleware can.perm:dispatch-incidents.
    }

    public function rules(): array
    {
        return [
            'ambulance_id' => ['required', 'integer', 'exists:ambulances,id'],
            'driver_user_id' => ['required', 'integer', 'exists:users,id'],
            'dispatch_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
