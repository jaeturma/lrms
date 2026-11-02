<?php

namespace App\Support;

use App\Models\ValidLrSchool;
use App\Models\ValidLrVisit;
use App\Models\ValidLrVisitItem;

/**
 * Shapes a VALID-LR visit for the monitoring team, school and print pages.
 */
class ValidLrVisitPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function visit(ValidLrVisit $visit): array
    {
        $visit->loadMissing(['validLrSchool.school', 'items.lrmisAdjuster:id,name', 'user:id,name', 'teamLeader:id,name', 'schoolSubmitter:id,name', 'overrider:id,name']);

        return [
            'id' => $visit->id,
            'status' => $visit->status,
            'stage' => $visit->stage(),
            'visited_at' => $visit->visited_at?->format('Y-m-d\TH:i'),
            'scheduled_at' => $visit->scheduled_at?->format('Y-m-d\TH:i'),
            'monitoring_team' => $visit->monitoring_team,
            'school_representative' => $visit->school_representative,
            'indicators' => $visit->indicators ?? [],
            'lr_delivery_status' => $visit->lr_delivery_status,
            'sme_delivery_status' => $visit->sme_delivery_status,
            'tvl_delivery_status' => $visit->tvl_delivery_status,
            'key_findings' => $visit->key_findings,
            'recommended_actions' => $visit->recommended_actions,
            'issues_concerns' => $visit->issues_concerns,
            'actions_taken' => $visit->actions_taken,
            'prepared_by' => $visit->prepared_by,
            'acknowledged_by' => $visit->acknowledged_by,
            'monitor' => $visit->user?->name,
            'team_leader' => $visit->teamLeader ? ['id' => $visit->teamLeader->id, 'name' => $visit->teamLeader->name] : null,
            'school_submitted_at' => $visit->school_submitted_at?->format('M j, Y g:i A'),
            'school_submitted_by' => $visit->schoolSubmitter?->name,
            'override_reason' => $visit->override_reason,
            'overridden_by' => $visit->overrider?->name,
            'overridden_at' => $visit->overridden_at?->format('M j, Y g:i A'),
            'validation_started_at' => $visit->validation_started_at?->format('M j, Y g:i A'),
            'updated_at' => $visit->updated_at?->format('M j, Y g:i A'),
            'personnel' => $visit->personnel(),
            'items' => $visit->items->map(fn (ValidLrVisitItem $item): array => self::item($item))->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function item(ValidLrVisitItem $item): array
    {
        return [
            'id' => $item->id,
            'section' => $item->section,
            'group_label' => $item->group_label,
            'label' => $item->label,
            'title' => $item->title,
            'publisher' => $item->publisher,
            'unit' => $item->unit,
            'procurement_source' => $item->procurement_source,
            'reference_quantity' => $item->reference_quantity,
            'lrmis_quantity' => $item->lrmis_quantity,
            'lrmis_original_quantity' => $item->lrmis_original_quantity,
            'lrmis_adjusted_by' => $item->relationLoaded('lrmisAdjuster') ? $item->lrmisAdjuster?->name : null,
            'lrmis_adjusted_at' => $item->lrmis_adjusted_at?->format('M j, Y g:i A'),
            'lrms_quantity' => $item->lrms_quantity,
            'distributed_quantity' => $item->distributed_quantity,
            'school_quantity' => $item->school_quantity,
            'actual_quantity' => $item->actual_quantity,
            'enrolment' => $item->enrolment,
            'year_received' => $item->year_received,
            'mov' => $item->mov ?? [],
            'extra' => $item->extra ?? [],
            'remarks_repairs' => $item->remarks_repairs,
            'remarks_missing' => $item->remarks_missing,
            'remarks_others' => $item->remarks_others,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function school(ValidLrSchool $school): array
    {
        return [
            'beis_school_id' => $school->beis_school_id,
            'school_name' => $school->school_name,
            'region' => $school->region,
            'division' => $school->division,
            'district' => $school->district,
            'municipality' => $school->municipality,
            'school_type' => $school->school_type,
            'curricular_offering' => $school->curricular_offering,
            'logo_url' => $school->school?->logoUrl(),
        ];
    }

    /**
     * Shared option lists the visit pages need.
     *
     * @return array<string, mixed>
     */
    public static function options(): array
    {
        return [
            'indicatorQuestions' => ValidLrVisit::INDICATORS,
            'movOptions' => ValidLrVisitItem::MOV_OPTIONS,
            'aralPrograms' => ValidLrVisitItem::ARAL_PROGRAMS,
            'deliveryStatuses' => ValidLrVisit::DELIVERY_STATUSES,
            'personnelLabels' => ValidLrVisit::PERSONNEL,
            'stages' => ValidLrVisit::STAGES,
        ];
    }
}
