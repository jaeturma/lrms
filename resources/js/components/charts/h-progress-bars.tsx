type Row = {
    label: string;
    value: number;
    max: number;
    valueText: string;
};

type Props = {
    rows: Row[];
    color?: string;
};

/**
 * Horizontal part-to-whole rows: a muted track for the whole, a colored fill
 * for the achieved part, and a direct label so no value hides behind hover.
 */
export function HProgressBars({ rows, color = 'var(--viz-series-1)' }: Props) {
    return (
        <div className="space-y-2.5">
            {rows.map((row) => {
                const percent = row.max > 0 ? (row.value / row.max) * 100 : 0;

                return (
                    <div key={row.label} className="flex items-center gap-3">
                        <span className="w-24 shrink-0 truncate text-xs text-muted-foreground" title={row.label}>
                            {row.label}
                        </span>
                        <div className="h-2.5 min-w-0 flex-1 overflow-hidden rounded-full" style={{ background: 'var(--viz-track)' }}>
                            <div
                                className="h-full rounded-full"
                                style={{ width: `${Math.min(percent, 100)}%`, background: color }}
                            />
                        </div>
                        <span className="w-24 shrink-0 text-right text-xs text-foreground tabular-nums">{row.valueText}</span>
                    </div>
                );
            })}
        </div>
    );
}
