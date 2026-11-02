<?php

namespace App\Http\Requests;

use App\Models\ValidLrVisit;
use App\Models\ValidLrVisitItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateValidLrVisitRequest extends FormRequest
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
        $quantity = ['nullable', 'integer', 'min:0', 'max:10000000'];
        $remarks = ['nullable', 'string', 'max:1000'];

        return [
            'status' => ['required', Rule::in(ValidLrVisit::STATUSES)],
            'visited_at' => ['nullable', 'date'],
            'monitoring_team' => ['nullable', 'string', 'max:1000'],
            'school_representative' => ['nullable', 'string', 'max:255'],
            'indicators' => ['nullable', 'array'],
            'indicators.*.answer' => ['nullable', Rule::in(['yes', 'no'])],
            'indicators.*.remarks' => $remarks,
            'lr_delivery_status' => ['nullable', Rule::in(ValidLrVisit::DELIVERY_STATUSES)],
            'sme_delivery_status' => ['nullable', Rule::in(ValidLrVisit::DELIVERY_STATUSES)],
            'tvl_delivery_status' => ['nullable', Rule::in(ValidLrVisit::DELIVERY_STATUSES)],
            'key_findings' => ['nullable', 'string', 'max:5000'],
            'recommended_actions' => ['nullable', 'string', 'max:5000'],
            'issues_concerns' => ['nullable', 'string', 'max:5000'],
            'actions_taken' => ['nullable', 'string', 'max:5000'],
            'prepared_by' => ['nullable', 'string', 'max:255'],
            'acknowledged_by' => ['nullable', 'string', 'max:255'],
            'items' => ['nullable', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.reference_quantity' => $quantity,
            'items.*.distributed_quantity' => $quantity,
            'items.*.actual_quantity' => $quantity,
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

    /**
     * Restrict indicator keys to the known checklist.
     *
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): mixed
    {
        $validated = parent::validated($key, $default);

        if ($key === null && isset($validated['indicators'])) {
            $validated['indicators'] = collect($validated['indicators'])
                ->only(array_keys(ValidLrVisit::INDICATORS))
                ->all();
        }

        return $validated;
    }
}
