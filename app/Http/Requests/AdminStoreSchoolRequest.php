<?php

namespace App\Http\Requests;

use App\Models\School;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminStoreSchoolRequest extends FormRequest
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
            'school_id' => ['required', 'string', 'max:255', Rule::unique('schools', 'school_id')],
            'school_name' => ['required', 'string', 'max:255'],
            'school_type' => ['required', 'string', Rule::in(School::SCHOOL_TYPES)],
            'municipality_id' => ['required', 'integer', Rule::exists('municipalities', 'id')],
            'district_id' => [
                'required',
                'integer',
                Rule::exists('districts', 'id')->where('municipality_id', (int) $this->input('municipality_id')),
            ],
            'barangay_id' => [
                'nullable',
                'integer',
                Rule::exists('barangays', 'id')->where('municipality_id', (int) $this->input('municipality_id')),
            ],
            'school_head' => ['nullable', 'string', 'max:255'],
            'librarian' => ['nullable', 'string', 'max:255'],
            'property_custodian' => ['nullable', 'string', 'max:255'],
            'primary_mobile_no' => ['nullable', 'string', 'max:30'],
            'secondary_mobile_no' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
        ];
    }
}
