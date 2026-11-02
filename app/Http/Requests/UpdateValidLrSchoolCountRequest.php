<?php

namespace App\Http\Requests;

use App\Models\ValidLrVisitItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateValidLrSchoolCountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === 'school' && $this->user()->school !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $quantity = ['nullable', 'integer', 'min:0', 'max:10000000'];
        $remarks = ['nullable', 'string', 'max:1000'];

        return [
            'school_representative' => ['nullable', 'string', 'max:255'],
            'items' => ['nullable', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.school_quantity' => $quantity,
            'items.*.reference_quantity' => $quantity,
            'items.*.distributed_quantity' => $quantity,
            'items.*.enrolment' => $quantity,
            'items.*.year_received' => ['nullable', 'string', 'max:255'],
            'items.*.mov' => ['nullable', 'array'],
            'items.*.mov.*' => [Rule::in(ValidLrVisitItem::MOV_OPTIONS)],
            'items.*.extra' => ['nullable', 'array'],
            'items.*.extra.*' => $quantity,
            'items.*.remarks_repairs' => $remarks,
            'items.*.remarks_missing' => $remarks,
            'items.*.remarks_others' => $remarks,
        ];
    }
}
