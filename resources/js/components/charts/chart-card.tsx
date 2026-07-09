import { Table2 } from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { cn } from '@/lib/utils';

export type ChartLegendItem = {
    label: string;
    color: string;
};

type ChartTable = {
    headers: string[];
    rows: Array<Array<string | number>>;
};

type Props = {
    title: string;
    subtitle?: string;
    legend?: ChartLegendItem[];
    table?: ChartTable;
    footer?: ReactNode;
    children: ReactNode;
    className?: string;
};

/**
 * Chart container: title, optional legend, and a table-view toggle so every
 * value stays reachable without relying on color or hover alone.
 */
export function ChartCard({ title, subtitle, legend, table, footer, children, className }: Props) {
    const [showTable, setShowTable] = useState(false);

    return (
        <figure className={cn('flex flex-col rounded-2xl border border-border bg-card p-4 shadow-sm', className)}>
            <figcaption className="mb-3 flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h2 className="text-base font-semibold text-foreground">{title}</h2>
                    {subtitle && <p className="mt-0.5 text-xs text-muted-foreground">{subtitle}</p>}
                </div>
                {table && (
                    <button
                        type="button"
                        onClick={() => setShowTable((current) => !current)}
                        aria-pressed={showTable}
                        className="inline-flex items-center gap-1.5 rounded-md border border-border px-2 py-1 text-xs text-muted-foreground hover:bg-muted hover:text-foreground"
                    >
                        <Table2 className="size-3.5" />
                        {showTable ? 'Chart' : 'Table'}
                    </button>
                )}
            </figcaption>

            {legend && legend.length > 1 && !showTable && (
                <div className="mb-3 flex flex-wrap gap-x-4 gap-y-1">
                    {legend.map((item) => (
                        <span key={item.label} className="inline-flex items-center gap-1.5 text-xs text-muted-foreground">
                            <span className="size-2.5 rounded-full" style={{ background: item.color }} />
                            {item.label}
                        </span>
                    ))}
                </div>
            )}

            <div className="min-h-0 flex-1">
                {showTable && table ? (
                    <div className="overflow-x-auto rounded-lg border border-border">
                        <table className="min-w-full text-sm">
                            <thead className="bg-muted text-left text-foreground">
                                <tr>
                                    {table.headers.map((header) => (
                                        <th key={header} className="px-3 py-1.5 text-xs font-medium">
                                            {header}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {table.rows.map((row, rowIndex) => (
                                    <tr key={rowIndex} className="border-t border-border">
                                        {row.map((cell, cellIndex) => (
                                            <td
                                                key={cellIndex}
                                                className={cn(
                                                    'px-3 py-1.5 text-xs',
                                                    cellIndex === 0 ? 'text-foreground' : 'text-muted-foreground tabular-nums',
                                                )}
                                            >
                                                {typeof cell === 'number' ? cell.toLocaleString() : cell}
                                            </td>
                                        ))}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                ) : (
                    children
                )}
            </div>

            {footer && !showTable && <div className="mt-3 text-xs text-muted-foreground">{footer}</div>}
        </figure>
    );
}
