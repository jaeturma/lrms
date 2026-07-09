<?php

namespace App\Http\Requests;

use App\Models\School;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchoolActivationRequest extends FormRequest
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
        /** @var School|null $school */
        $school = $this->route('school');

        return [
            'school_head' => ['required', 'string', 'max:80'],
            'librarian' => ['nullable', 'string', 'max:50'],
            'property_custodian' => ['nullable', 'string', 'max:50'],
            'primary_mobile_no' => ['nullable', 'string', 'max:15'],
            'secondary_mobile_no' => ['nullable', 'string', 'max:15'],
            'email' => [
                'required',
                'email',
                'max:50',
                Rule::unique('users', 'email')->ignore($school?->user_id),
            ],
            'municipality_id' => ['nullable', 'integer', 'exists:municipalities,id'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'barangay_id' => ['nullable', 'integer', 'exists:barangays,id'],
        ];
    }
}
