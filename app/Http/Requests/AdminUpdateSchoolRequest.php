<?php

namespace App\Http\Requests;

use App\Models\School;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUpdateSchoolRequest extends FormRequest
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
        /** @var School $school */
        $school = $this->route('school');

        return [
            'school_id' => ['required', 'string', 'max:255', Rule::unique('schools', 'school_id')->ignore($school->id)],
            'school_name' => ['required', 'string', 'max:255'],
            'district_id' => ['required', 'integer', Rule::exists('districts', 'id')],
            'municipality_id' => [
                'required',
                'integer',
                Rule::exists('municipalities', 'id')->where('district_id', (int) $this->input('district_id')),
            ],
            'barangay_id' => [
                'nullable',
                'integer',
                Rule::exists('barangays', 'id')->where('municipality_id', (int) $this->input('municipality_id')),
            ],
            'school_head' => ['nullable', 'string', 'max:255'],
            'librarian' => ['nullable', 'string', 'max:255'],
            'property_custodian' => ['nullable', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($school->user_id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'district_id.exists' => 'Selected district is invalid.',
            'municipality_id.exists' => 'Selected municipality is invalid.',
            'barangay_id.exists' => 'Selected barangay is invalid.',
        ];
    }
}
