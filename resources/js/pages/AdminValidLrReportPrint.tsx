import {
    PrintPage,
    PrintSection,
    printCell,
    printHead,
    printTable,
} from '@/components/print-page';
import type {
    ValidLrReport,
    ValidLrReportFilters,
} from '@/pages/AdminValidLrReport';

type Props = {
    filters: ValidLrReportFilters;
    report: ValidLrReport;
    division: string | null;
    indicatorQuestions: Record<string, string>;
    personnelLabels: Record<string, string>;
    generatedAt: string;
    generatedBy: string | null;
};

const answerMark = (answer: 'yes' | 'no' | null | undefined) =>
    answer === 'yes' ? 'Y' : answer === 'no' ? 'N' : '–';

export default function AdminValidLrReportPrint({
    filters,
    report,
    division,
    indicatorQuestions,
    personnelLabels,
    generatedAt,
    generatedBy,
}: Props) {
    const indicatorKeys = Object.keys(indicatorQuestions);
    const scope = [
        filters.district ? `District: ${filters.district}` : 'All districts',
        filters.status
            ? `${filters.status === 'completed' ? 'Completed' : 'Draft'} visits`
            : 'All visits',
        filters.from || filters.to
            ? `Visited ${filters.from ?? '…'} to ${filters.to ?? '…'}`
            : null,
    ]
        .filter(Boolean)
        .join(' · ');

    return (
        <PrintPage
            title="LR Monitoring Report"
            backHref="/app/admin/valid-lr/report"
            backLabel="Back to report"
        >
            <header className="text-center">
                <p className="text-[10px] tracking-wide uppercase">
                    Department of Education
                    {division ? ` · Schools Division of ${division}` : ''}
                </p>
                <h1 className="text-[15px] font-bold uppercase">
                    Project VALID-LR Monitoring Report
                </h1>
                <p className="text-[10px]">{scope}</p>
            </header>

            <div className="mt-3 grid grid-cols-6 border border-neutral-500 text-center">
                {[
                    ['Schools', report.summary.schools],
                    ['Monitored', report.summary.schools_monitored],
                    ['Coverage', `${report.summary.coverage}%`],
                    ['Visits', report.summary.visits],
                    ['Completed', report.summary.completed],
                    ['Drafts', report.summary.drafts],
                ].map(([label, value]) => (
                    <div
                        key={label}
                        className="border-r border-neutral-500 py-1 last:border-r-0"
                    >
                        <p className="text-[10px] text-neutral-600">{label}</p>
                        <p className="text-[14px] font-bold">
                            {typeof value === 'number'
                                ? value.toLocaleString()
                                : value}
                        </p>
                    </div>
                ))}
            </div>

            <div className="grid grid-cols-2 gap-4">
                <PrintSection title="Monitoring Indicators">
                    <table className={printTable}>
                        <thead>
                            <tr>
                                <th className={printHead}>Indicator</th>
                                <th className={`${printHead} text-right`}>
                                    Yes
                                </th>
                                <th className={`${printHead} text-right`}>
                                    No
                                </th>
                                <th className={`${printHead} text-right`}>
                                    Blank
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {report.indicators.map((indicator, index) => (
                                <tr key={indicator.key}>
                                    <td className={printCell}>
                                        {index + 1}. {indicator.question}
                                    </td>
                                    <td className={`${printCell} text-right`}>
                                        {indicator.yes}
                                    </td>
                                    <td className={`${printCell} text-right`}>
                                        {indicator.no}
                                    </td>
                                    <td className={`${printCell} text-right`}>
                                        {indicator.unanswered}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>

                    <table className={`${printTable} mt-2`}>
                        <thead>
                            <tr>
                                <th className={printHead}>Delivery</th>
                                <th className={`${printHead} text-right`}>
                                    Complete
                                </th>
                                <th className={`${printHead} text-right`}>
                                    Incomplete
                                </th>
                                <th className={`${printHead} text-right`}>
                                    Not recorded
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {report.delivery.map((row) => (
                                <tr key={row.area}>
                                    <td className={printCell}>{row.area}</td>
                                    <td className={`${printCell} text-right`}>
                                        {row.complete}
                                    </td>
                                    <td className={`${printCell} text-right`}>
                                        {row.incomplete}
                                    </td>
                                    <td className={`${printCell} text-right`}>
                                        {row.unrecorded}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </PrintSection>

                <PrintSection title="Coverage by District">
                    <table className={printTable}>
                        <thead>
                            <tr>
                                <th className={printHead}>District</th>
                                <th className={`${printHead} text-right`}>
                                    Schools
                                </th>
                                <th className={`${printHead} text-right`}>
                                    Monitored
                                </th>
                                <th className={`${printHead} text-right`}>
                                    Completed
                                </th>
                                <th className={`${printHead} text-right`}>
                                    Coverage
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {report.districts.map((row) => (
                                <tr key={row.district}>
                                    <td className={printCell}>
                                        {row.district}
                                    </td>
                                    <td className={`${printCell} text-right`}>
                                        {row.schools}
                                    </td>
                                    <td className={`${printCell} text-right`}>
                                        {row.monitored}
                                    </td>
                                    <td className={`${printCell} text-right`}>
                                        {row.completed}
                                    </td>
                                    <td className={`${printCell} text-right`}>
                                        {row.coverage}%
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </PrintSection>
            </div>

            <PrintSection title={`Monitoring Visits (${report.visits.length})`}>
                <p className="mb-1 text-[10px] text-neutral-600">
                    Indicators:{' '}
                    {indicatorKeys
                        .map(
                            (key, index) =>
                                `${index + 1} ${key.replaceAll('_', ' ')}`,
                        )
                        .join(' · ')}
                    . Y = yes, N = no.
                </p>
                <table className={printTable}>
                    <thead>
                        <tr>
                            <th className={printHead}>School</th>
                            <th className={printHead}>District</th>
                            <th className={printHead}>Visited</th>
                            <th className={printHead}>Team</th>
                            <th className={printHead}>LR / SME / TVL</th>
                            {indicatorKeys.map((key, index) => (
                                <th
                                    key={key}
                                    className={`${printHead} text-center`}
                                >
                                    {index + 1}
                                </th>
                            ))}
                            <th className={`${printHead} text-right`}>
                                Counted
                            </th>
                            <th className={`${printHead} text-right`}>
                                Discrep.
                            </th>
                            <th className={printHead}>Status</th>
                            <th className={`${printHead} w-1/5`}>
                                Key Findings
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {report.visits.length === 0 && (
                            <tr>
                                <td
                                    colSpan={9 + indicatorKeys.length}
                                    className={`${printCell} text-center`}
                                >
                                    No monitoring visits match these filters.
                                </td>
                            </tr>
                        )}
                        {report.visits.map((visit) => (
                            <tr key={visit.id}>
                                <td className={printCell}>
                                    {visit.school_name}
                                    <br />
                                    <span className="text-neutral-600">
                                        {visit.beis_school_id}
                                    </span>
                                </td>
                                <td className={printCell}>{visit.district}</td>
                                <td
                                    className={`${printCell} whitespace-nowrap`}
                                >
                                    {visit.visited_at}
                                </td>
                                <td className={printCell}>
                                    {visit.monitoring_team ?? visit.monitor}
                                </td>
                                <td className={printCell}>
                                    {[
                                        visit.lr_delivery_status,
                                        visit.sme_delivery_status,
                                        visit.tvl_delivery_status,
                                    ]
                                        .map((status) => status ?? '–')
                                        .join(' / ')}
                                </td>
                                {indicatorKeys.map((key) => (
                                    <td
                                        key={key}
                                        className={`${printCell} text-center`}
                                    >
                                        {answerMark(visit.indicators[key])}
                                    </td>
                                ))}
                                <td className={`${printCell} text-right`}>
                                    {visit.items_counted}/{visit.items_total}
                                </td>
                                <td
                                    className={`${printCell} text-right font-semibold`}
                                >
                                    {visit.discrepancies}
                                </td>
                                <td className={`${printCell} capitalize`}>
                                    {visit.status}
                                </td>
                                <td
                                    className={`${printCell} whitespace-pre-line`}
                                >
                                    {visit.key_findings}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </PrintSection>

            <PrintSection title="School Personnel of Monitored Schools">
                <table className={printTable}>
                    <thead>
                        <tr>
                            <th className={printHead}>School</th>
                            <th className={printHead}>Team Leader</th>
                            {Object.values(personnelLabels).map((label) => (
                                <th key={label} className={printHead}>
                                    {label}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {report.visits.map((visit) => (
                            <tr key={visit.id}>
                                <td className={printCell}>
                                    {visit.school_name}
                                    <br />
                                    <span className="text-neutral-600">
                                        {visit.beis_school_id}
                                    </span>
                                </td>
                                <td className={printCell}>
                                    {visit.team_leader}
                                </td>
                                {Object.keys(personnelLabels).map((field) => (
                                    <td key={field} className={printCell}>
                                        {visit.personnel[field]}
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </PrintSection>

            <p className="mt-4 text-[9px] text-neutral-500">
                Printed from LRMS on {generatedAt}
                {generatedBy ? ` by ${generatedBy}` : ''}.
            </p>
        </PrintPage>
    );
}
