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
            'resources.*.id' => ['nullable', 'integer', Rule::exists('learning_resources', 'id')],
            'resources.*.resource_title_id' => [
                'nullable',
                'integer',
                Rule::exists('resource_titles', 'id')->where('is_active', true),
            ],
            'resources.*.learning_resource_type_id' => [
                'required_without:resources.*.resource_title_id',
                'nullable',
                'integer',
                Rule::exists('learning_resource_types', 'id')->where('is_active', true),
            ],
            'resources.*.title' => ['required_without:resources.*.resource_title_id', 'nullable', 'string', 'max:255'],
            'resources.*.publisher' => ['required_without:resources.*.resource_title_id', 'nullable', 'string', 'max:255'],
            'resources.*.quantity_delivered' => ['required', 'integer', 'min:1'],
            'resources.*.quantity_with_issue_defect' => ['required', 'integer', 'min:0'],
            'resources.*.remarks' => ['nullable', 'string', 'max:255'],
            'resources.*.source' => ['nullable', 'string', 'max:255'],
            'resources.*.supplier' => ['nullable', 'string', 'max:255'],
            'resources.*.date_delivered' => ['nullable', 'date'],
            'resources.*.ier_no' => ['nullable', 'string', 'max:255'],
        ];
    }
}
