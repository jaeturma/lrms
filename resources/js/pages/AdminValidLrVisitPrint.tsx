import type { ReactNode } from 'react';
import {
    PrintPage,
    PrintSection,
    printCell,
    printHead,
    printTable,
} from '@/components/print-page';

type VisitItem = {
    id: number;
    section: 'lr' | 'sme_co' | 'sme_dl' | 'tvl' | 'im_lwd' | 'aral';
    group_label: string | null;
    label: string;
    title: string | null;
    publisher: string | null;
    unit: string | null;
    procurement_source: string | null;
    reference_quantity: number | null;
    lrmis_quantity: number | null;
    lrms_quantity: number | null;
    distributed_quantity: number | null;
    school_quantity: number | null;
    actual_quantity: number | null;
    enrolment: number | null;
    year_received: string | null;
    mov: string[];
    extra: Record<string, string | number | null>;
    remarks_repairs: string | null;
    remarks_missing: string | null;
    remarks_others: string | null;
};

type Indicator = { answer: 'yes' | 'no' | null; remarks: string | null };

type Props = {
    visit: {
        id: number;
        status: 'draft' | 'completed';
        visited_at: string | null;
        monitoring_team: string | null;
        school_representative: string | null;
        indicators: Record<string, Indicator>;
        lr_delivery_status: string | null;
        sme_delivery_status: string | null;
        tvl_delivery_status: string | null;
        key_findings: string | null;
        recommended_actions: string | null;
        issues_concerns: string | null;
        actions_taken: string | null;
        prepared_by: string | null;
        acknowledged_by: string | null;
        monitor: string | null;
        updated_at: string | null;
        team_leader: { id: number; name: string } | null;
        personnel: Record<string, string | null>;
        items: VisitItem[];
    };
    school: {
        beis_school_id: string;
        school_name: string;
        region: string | null;
        division: string | null;
        district: string | null;
        municipality: string | null;
        school_type: string | null;
        curricular_offering: string | null;
        logo_url?: string | null;
    };
    indicatorQuestions: Record<string, string>;
    movOptions: string[];
    aralPrograms: Record<string, string>;
    deliveryStatuses: string[];
    personnelLabels: Record<string, string>;
    generatedAt: string;
    generatedBy: string | null;
};

const quantity = (value: number | null) => value?.toLocaleString() ?? '';

const formatVisitedAt = (value: string | null) =>
    value
        ? new Date(value).toLocaleString('en-PH', {
              dateStyle: 'long',
              timeStyle: 'short',
          })
        : '';

const differs = (item: VisitItem) => {
    const expected =
        item.section === 'lr' ? item.lrmis_quantity : item.reference_quantity;

    return (
        item.actual_quantity !== null &&
        expected !== null &&
        item.actual_quantity !== expected
    );
};

function Box({ checked }: { checked: boolean }) {
    return (
        <span className="inline-block w-3 text-center font-bold">
            {checked ? '☑' : '☐'}
        </span>
    );
}

function groupBy(items: VisitItem[]) {
    const groups = new Map<string, VisitItem[]>();

    items.forEach((item) => {
        const key = item.group_label ?? '';
        groups.set(key, [...(groups.get(key) ?? []), item]);
    });

    return [...groups.entries()];
}

function Remarks({ item }: { item: VisitItem }) {
    const parts = [
        item.remarks_repairs && `Repairs: ${item.remarks_repairs}`,
        item.remarks_missing && `Missing: ${item.remarks_missing}`,
        item.remarks_others && `Others: ${item.remarks_others}`,
    ].filter(Boolean);

    return <>{parts.join(' · ')}</>;
}

function InfoRow({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div className="flex gap-1 border-b border-neutral-400 py-0.5">
            <span className="shrink-0 font-semibold">{label}:</span>
            <span className="min-h-[1.25em] flex-1">{value}</span>
        </div>
    );
}

function Signature({ label, name }: { label: string; name: string | null }) {
    return (
        <div className="print-avoid-break text-center">
            <p className="mb-6 text-left font-semibold">{label}</p>
            <p className="min-h-[1.25em] border-b border-black font-semibold uppercase">
                {name}
            </p>
            <p className="text-[10px] text-neutral-600">
                Signature over printed name / Date
            </p>
        </div>
    );
}

export default function AdminValidLrVisitPrint({
    visit,
    school,
    indicatorQuestions,
    movOptions,
    aralPrograms,
    deliveryStatuses,
    personnelLabels,
    generatedAt,
    generatedBy,
}: Props) {
    const itemsFor = (section: VisitItem['section']) =>
        visit.items.filter((item) => item.section === section);

    const learningResources = itemsFor('lr');
    const equipmentSections: Array<{
        title: string;
        section: VisitItem['section'];
    }> = [
        { title: 'SME Packages — Central Office', section: 'sme_co' },
        { title: 'SME Packages — Downloaded to Division', section: 'sme_dl' },
        { title: 'TVL Tools and Equipment', section: 'tvl' },
    ];

    return (
        <PrintPage
            title={`VALID-LR Visit · ${school.school_name}`}
            backHref={`/app/admin/valid-lr/visits/${visit.id}/edit`}
            backLabel="Back to visit"
        >
            <header className="relative text-center">
                {school.logo_url && (
                    <img
                        src={school.logo_url}
                        alt=""
                        className="absolute top-0 left-0 h-14 w-14 object-contain"
                    />
                )}
                <p className="text-[10px] tracking-wide uppercase">
                    Department of Education · {school.region ?? ''}
                    {school.division
                        ? ` · Schools Division of ${school.division}`
                        : ''}
                </p>
                <h1 className="text-[15px] font-bold uppercase">
                    Project VALID-LR Monitoring Tool
                </h1>
                <p className="text-[10px]">
                    Validation and inventory of learning resources, SME and TVL
                    packages ·{' '}
                    <strong>
                        {visit.status === 'completed'
                            ? 'COMPLETED RECORD'
                            : 'DRAFT / FIELD COPY'}
                    </strong>
                </p>
            </header>

            <div className="mt-3 grid grid-cols-2 gap-x-6">
                <div>
                    <InfoRow label="School ID" value={school.beis_school_id} />
                    <InfoRow label="School Name" value={school.school_name} />
                    <InfoRow
                        label="District / Municipality"
                        value={[school.district, school.municipality]
                            .filter(Boolean)
                            .join(' / ')}
                    />
                    <InfoRow
                        label="Curricular Offering"
                        value={school.curricular_offering}
                    />
                </div>
                <div>
                    <InfoRow
                        label="Date and Time of Visit"
                        value={formatVisitedAt(visit.visited_at)}
                    />
                    <InfoRow
                        label="Monitoring Team"
                        value={visit.monitoring_team}
                    />
                    <InfoRow
                        label="School Representative"
                        value={visit.school_representative}
                    />
                    <InfoRow
                        label="Team Leader"
                        value={visit.team_leader?.name}
                    />
                </div>
            </div>

            <div className="mt-2 grid grid-cols-3 gap-x-6">
                {Object.entries(personnelLabels).map(([field, label]) => (
                    <InfoRow
                        key={field}
                        label={label}
                        value={visit.personnel[field]}
                    />
                ))}
            </div>

            <PrintSection title="I. Monitoring Indicators">
                <table className={printTable}>
                    <thead>
                        <tr>
                            <th className={`${printHead} w-6`}>#</th>
                            <th className={printHead}>Indicator</th>
                            <th className={`${printHead} w-10 text-center`}>
                                Yes
                            </th>
                            <th className={`${printHead} w-10 text-center`}>
                                No
                            </th>
                            <th className={`${printHead} w-1/4`}>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        {Object.entries(indicatorQuestions).map(
                            ([key, question], index) => {
                                const indicator = visit.indicators[key];

                                return (
                                    <tr key={key}>
                                        <td className={printCell}>
                                            {index + 1}
                                        </td>
                                        <td className={printCell}>
                                            {question}
                                        </td>
                                        <td
                                            className={`${printCell} text-center`}
                                        >
                                            <Box
                                                checked={
                                                    indicator?.answer === 'yes'
                                                }
                                            />
                                        </td>
                                        <td
                                            className={`${printCell} text-center`}
                                        >
                                            <Box
                                                checked={
                                                    indicator?.answer === 'no'
                                                }
                                            />
                                        </td>
                                        <td className={printCell}>
                                            {indicator?.remarks}
                                        </td>
                                    </tr>
                                );
                            },
                        )}
                    </tbody>
                </table>
            </PrintSection>

            {learningResources.length > 0 && (
                <PrintSection title="II. Learning Resources (Textbooks and Teacher's Manuals)">
                    <p className="mb-1 text-[10px] text-neutral-600">
                        ≠ marks a validated count that differs from LRMIS. MOV:{' '}
                        {movOptions
                            .map((option, index) => `${index + 1} ${option}`)
                            .join(' · ')}
                    </p>
                    <table className={printTable}>
                        <thead>
                            <tr>
                                <th className={printHead}>Grade</th>
                                <th className={printHead}>
                                    Learning Area / Title / Publisher
                                </th>
                                <th className={`${printHead} text-right`}>
                                    Allocated
                                </th>
                                <th className={`${printHead} text-right`}>
                                    Distributed
                                </th>
                                <th className={`${printHead} text-right`}>
                                    LRMIS
                                </th>
                                <th className={`${printHead} w-14 text-right`}>
                                    School Count
                                </th>
                                <th className={`${printHead} w-14 text-right`}>
                                    Validated
                                </th>
                                <th className={`${printHead} w-12 text-right`}>
                                    Enrolment
                                </th>
                                <th className={`${printHead} w-16`}>MOV</th>
                                <th className={`${printHead} w-1/5`}>
                                    Remarks
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {learningResources.map((item) => (
                                <tr key={item.id}>
                                    <td
                                        className={`${printCell} whitespace-nowrap`}
                                    >
                                        {item.group_label}
                                    </td>
                                    <td className={printCell}>
                                        <strong>{item.label}</strong>
                                        {item.title && (
                                            <span>
                                                {' '}
                                                — {item.title}
                                                {item.publisher &&
                                                    ` (${item.publisher})`}
                                            </span>
                                        )}
                                    </td>
                                    <td className={`${printCell} text-right`}>
                                        {quantity(item.reference_quantity)}
                                    </td>
                                    <td className={`${printCell} text-right`}>
                                        {quantity(item.distributed_quantity)}
                                    </td>
                                    <td className={`${printCell} text-right`}>
                                        {quantity(item.lrmis_quantity)}
                                    </td>
                                    <td className={`${printCell} text-right`}>
                                        {quantity(item.school_quantity)}
                                    </td>
                                    <td
                                        className={`${printCell} text-right font-semibold`}
                                    >
                                        {differs(item) && '≠ '}
                                        {quantity(item.actual_quantity)}
                                    </td>
                                    <td className={`${printCell} text-right`}>
                                        {quantity(item.enrolment)}
                                    </td>
                                    <td className={printCell}>
                                        {item.mov
                                            .map(
                                                (option) =>
                                                    movOptions.indexOf(option) +
                                                    1,
                                            )
                                            .filter((number) => number > 0)
                                            .join(', ')}
                                    </td>
                                    <td className={printCell}>
                                        <Remarks item={item} />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    <p className="mt-1">
                        Delivery status:{' '}
                        {deliveryStatuses.map((status) => (
                            <span key={status} className="mr-3">
                                <Box
                                    checked={
                                        visit.lr_delivery_status === status
                                    }
                                />{' '}
                                {status}
                            </span>
                        ))}
                    </p>
                </PrintSection>
            )}

            {equipmentSections.map(({ title, section }, sectionIndex) => {
                const items = itemsFor(section);
                const status =
                    section === 'tvl'
                        ? visit.tvl_delivery_status
                        : visit.sme_delivery_status;

                if (items.length === 0) {
                    return null;
                }

                return (
                    <PrintSection
                        key={section}
                        title={`${['III', 'IV', 'V'][sectionIndex]}. ${title}`}
                    >
                        <table className={printTable}>
                            <thead>
                                <tr>
                                    <th className={printHead}>Item</th>
                                    <th className={`${printHead} w-10`}>
                                        Unit
                                    </th>
                                    <th className={`${printHead} text-right`}>
                                        Package Qty
                                    </th>
                                    <th className={printHead}>Year Received</th>
                                    <th
                                        className={`${printHead} w-14 text-right`}
                                    >
                                        School Count
                                    </th>
                                    <th
                                        className={`${printHead} w-14 text-right`}
                                    >
                                        Validated
                                    </th>
                                    <th className={`${printHead} w-1/4`}>
                                        Remarks (repairs / missing / others)
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {groupBy(items).map(([group, rows]) => [
                                    group && (
                                        <tr key={`${section}-${group}`}>
                                            <td
                                                colSpan={7}
                                                className={`${printCell} bg-neutral-100 font-semibold`}
                                            >
                                                {group}
                                            </td>
                                        </tr>
                                    ),
                                    ...rows.map((item) => (
                                        <tr key={item.id}>
                                            <td className={printCell}>
                                                {item.label}
                                                {item.procurement_source &&
                                                    ` (${item.procurement_source})`}
                                            </td>
                                            <td className={printCell}>
                                                {item.unit}
                                            </td>
                                            <td
                                                className={`${printCell} text-right`}
                                            >
                                                {quantity(
                                                    item.reference_quantity,
                                                )}
                                            </td>
                                            <td className={printCell}>
                                                {item.year_received}
                                            </td>
                                            <td
                                                className={`${printCell} text-right`}
                                            >
                                                {quantity(item.school_quantity)}
                                            </td>
                                            <td
                                                className={`${printCell} text-right font-semibold`}
                                            >
                                                {differs(item) && '≠ '}
                                                {quantity(item.actual_quantity)}
                                            </td>
                                            <td className={printCell}>
                                                <Remarks item={item} />
                                            </td>
                                        </tr>
                                    )),
                                ])}
                            </tbody>
                        </table>
                        {section !== 'sme_co' && (
                            <p className="mt-1">
                                {section === 'tvl' ? 'TVL' : 'SME'} delivery
                                status:{' '}
                                {deliveryStatuses.map((option) => (
                                    <span key={option} className="mr-3">
                                        <Box checked={status === option} />{' '}
                                        {option}
                                    </span>
                                ))}
                            </p>
                        )}
                    </PrintSection>
                );
            })}

            <PrintSection title="VI. Instructional Materials for Learners with Disabilities">
                <table className={printTable}>
                    <thead>
                        <tr>
                            <th className={printHead}>Material</th>
                            <th className={`${printHead} w-20 text-right`}>
                                Quantity Received
                            </th>
                            <th className={`${printHead} w-20 text-right`}>
                                Actual
                            </th>
                            <th className={`${printHead} w-1/3`}>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        {itemsFor('im_lwd').map((item) => (
                            <tr key={item.id}>
                                <td className={printCell}>{item.label}</td>
                                <td className={`${printCell} text-right`}>
                                    {quantity(item.reference_quantity)}
                                </td>
                                <td className={`${printCell} text-right`}>
                                    {quantity(item.actual_quantity)}
                                </td>
                                <td className={printCell}>
                                    <Remarks item={item} />
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </PrintSection>

            <PrintSection title="VII. ARAL Program Materials">
                <table className={printTable}>
                    <thead>
                        <tr>
                            <th className={printHead}>Material</th>
                            {Object.entries(aralPrograms).map(
                                ([key, label]) => (
                                    <th
                                        key={key}
                                        className={`${printHead} w-24 text-right`}
                                    >
                                        {label}
                                    </th>
                                ),
                            )}
                        </tr>
                    </thead>
                    <tbody>
                        {groupBy(itemsFor('aral')).map(([group, rows]) => [
                            group && (
                                <tr key={`aral-${group}`}>
                                    <td
                                        colSpan={
                                            Object.keys(aralPrograms).length + 1
                                        }
                                        className={`${printCell} bg-neutral-100 font-semibold`}
                                    >
                                        {group}
                                    </td>
                                </tr>
                            ),
                            ...rows.map((item) => (
                                <tr key={item.id}>
                                    <td className={printCell}>{item.label}</td>
                                    {Object.keys(aralPrograms).map((key) => (
                                        <td
                                            key={key}
                                            className={`${printCell} text-right`}
                                        >
                                            {item.extra[key] ?? ''}
                                        </td>
                                    ))}
                                </tr>
                            )),
                        ])}
                    </tbody>
                </table>
            </PrintSection>

            <PrintSection title="VIII. Findings and Actions">
                <div className="grid grid-cols-2 gap-2">
                    {[
                        ['Key Findings', visit.key_findings],
                        ['Recommended Actions', visit.recommended_actions],
                        ['Issues and Concerns', visit.issues_concerns],
                        ['Actions Taken', visit.actions_taken],
                    ].map(([label, value]) => (
                        <div
                            key={label}
                            className="print-avoid-break min-h-24 border border-neutral-500 p-1.5"
                        >
                            <p className="font-semibold">{label}</p>
                            <p className="whitespace-pre-line">{value}</p>
                        </div>
                    ))}
                </div>
            </PrintSection>

            <div className="mt-6 grid grid-cols-2 gap-12">
                <Signature label="Prepared by:" name={visit.prepared_by} />
                <Signature
                    label="Acknowledged by (School Head):"
                    name={visit.acknowledged_by}
                />
            </div>

            <p className="mt-4 text-[9px] text-neutral-500">
                Printed from LRMS on {generatedAt}
                {generatedBy ? ` by ${generatedBy}` : ''}
                {visit.updated_at ? ` · last saved ${visit.updated_at}` : ''}.
            </p>
        </PrintPage>
    );
}
