type Row = {
    label: string;
    negative: number;
    neutral: number;
    positive: number;
};

type Props = {
    rows: Row[];
    negativeColor?: string;
    neutralColor?: string;
    positiveColor?: string;
};

/**
 * Likert-style diverging rows centered on the neutral midpoint: the negative
 * arm grows left, the positive arm grows right, neutral straddles the center.
 */
export function DivergingStackedBar({
    rows,
    negativeColor = 'var(--viz-bad)',
    neutralColor = 'var(--viz-neutral)',
    positiveColor = 'var(--viz-series-1)',
}: Props) {
    const maxSide = Math.max(
        ...rows.map((row) => Math.max(row.negative + row.neutral / 2, row.positive + row.neutral / 2)),
        1,
    );
    const scale = 46 / maxSide; // percent of row width per unit, per arm

    return (
        <div className="space-y-3">
            {rows.map((row) => {
                const total = row.negative + row.neutral + row.positive;
                const neutralHalf = (row.neutral / 2) * scale;

                return (
                    <div key={row.label} className="group">
                        <div className="mb-1 flex items-baseline justify-between gap-2">
                            <span className="truncate text-xs text-muted-foreground">{row.label}</span>
                            <span className="shrink-0 text-xs text-muted-foreground tabular-nums">
                                {total.toLocaleString()} units
                            </span>
                        </div>
                        <div className="relative h-4">
                            <div className="absolute inset-y-0 left-1/2 w-px" style={{ background: 'var(--viz-grid)' }} />
                            {row.negative > 0 && (
                                <div
                                    className="absolute inset-y-0 rounded-l-[4px]"
                                    style={{
                                        right: `calc(50% + ${neutralHalf}% + ${row.neutral > 0 ? 2 : 1}px)`,
                                        width: `${row.negative * scale}%`,
                                        background: negativeColor,
                                    }}
                                    title={`Needs attention: ${row.negative.toLocaleString()}`}
                                />
                            )}
                            {row.neutral > 0 && (
                                <div
                                    className="absolute inset-y-0"
                                    style={{
                                        left: `calc(50% - ${neutralHalf}%)`,
                                        width: `${row.neutral * scale}%`,
                                        background: neutralColor,
                                    }}
                                    title={`Fair: ${row.neutral.toLocaleString()}`}
                                />
                            )}
                            {row.positive > 0 && (
                                <div
                                    className="absolute inset-y-0 rounded-r-[4px]"
                                    style={{
                                        left: `calc(50% + ${neutralHalf}% + ${row.neutral > 0 ? 2 : 1}px)`,
                                        width: `${row.positive * scale}%`,
                                        background: positiveColor,
                                    }}
                                    title={`Good: ${row.positive.toLocaleString()}`}
                                />
                            )}
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
