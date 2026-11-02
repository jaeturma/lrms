<?php

namespace App\Http\Requests;

use App\Services\ValidLrReferenceImportService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportValidLrFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['admin', 'superadmin', 'sysadmin', 'ito'], true);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(ValidLrReferenceImportService::TYPES)],
            'file' => ['required', 'file', 'mimes:xlsx', 'max:204800'],
            'division' => [
                Rule::requiredIf($this->input('type') === ValidLrReferenceImportService::TYPE_REFERENCE_DATA),
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}
