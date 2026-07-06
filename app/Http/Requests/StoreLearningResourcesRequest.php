<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLearningResourcesRequest extends FormRequest
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
            'resources' => ['required', 'array', 'min:1'],
            'resources.*.resource_type' => [
                'required',
                'string',
                'max:255',
                Rule::exists('learning_resource_types', 'name')->where('is_active', true),
            ],
            'resources.*.issue_defect' => ['required', 'string', 'max:255'],
            'resources.*.quantity' => ['required', 'numeric', 'min:1'],
            'resources.*.publisher' => ['required', 'string', 'max:255'],
        ];
    }
}
