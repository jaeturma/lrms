<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreResourceTitleRequest extends FormRequest
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
            'learning_resource_type_id' => [
                'required',
                'integer',
                Rule::exists('learning_resource_types', 'id')->where('is_active', true),
            ],
            'grade_level_id' => ['nullable', 'integer', 'exists:grade_levels,id'],
            'title' => ['required', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', 'string', 'max:100'],
            'subject' => ['nullable', 'string', 'max:255'],
            'volume' => ['nullable', 'string', 'max:100'],
            'edition' => ['nullable', 'string', 'max:100'],
            'copyright_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'pages' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'isbn' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'media_url' => ['nullable', 'url', 'max:500'],
            'cover_image' => ['nullable', 'image', 'max:4096'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,mp4,webm,gif', 'max:51200'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
