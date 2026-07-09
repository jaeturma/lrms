type Series = {
    name: string;
    color: string;
};

type Datum = {
    label: string;
    values: number[];
};

type Props = {
    data: Datum[];
    series: Series[];
    height?: number;
};

/** Round a maximum up to a clean axis ceiling (1/2/2.5/5 × 10^n). */
function niceCeiling(value: number): number {
    if (value <= 0) {
        return 1;
    }

    const magnitude = Math.pow(10, Math.floor(Math.log10(value)));

    for (const step of [1, 2, 2.5, 5, 10]) {
        if (value <= step * magnitude) {
            return step * magnitude;
        }
    }

    return 10 * magnitude;
}

/**
 * Grouped columns rendered as HTML flex bars against hairline gridlines —
 * responsive without SVG text distortion. Hover shows a per-group tooltip.
 */
export function GroupedColumnChart({ data, series, height = 176 }: Props) {
    const max = niceCeiling(Math.max(...data.map((datum) => Math.max(...datum.values)), 1));
    const ticks = [1, 0.75, 0.5, 0.25, 0];

    return (
        <div className="flex gap-2">
            <div className="flex shrink-0 flex-col justify-between text-right text-[10px] text-muted-foreground tabular-nums" style={{ height }}>
                {ticks.map((tick) => (
                    <span key={tick} className="-translate-y-1/2 first:translate-y-0 last:translate-y-0">
                        {(max * tick).toLocaleString()}
                    </span>
                ))}
            </div>

            <div className="min-w-0 flex-1">
                <div className="relative" style={{ height }}>
                    {ticks.map((tick) => (
                        <div
                            key={tick}
                            className="absolute inset-x-0 h-px"
                            style={{ top: `${(1 - tick) * 100}%`, background: tick === 0 ? 'var(--border)' : 'var(--viz-grid)' }}
                        />
                    ))}

                    <div className="absolute inset-0 flex items-end justify-around gap-2">
                        {data.map((datum) => {
                            const total = datum.values.reduce((sum, value) => sum + value, 0);

                            return (
                                <div key={datum.label} className="group relative flex h-full max-w-14 flex-1 items-end justify-center gap-[2px]">
                                    <div className="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 hidden -translate-x-1/2 rounded-md border border-border bg-popover px-2.5 py-1.5 text-[11px] whitespace-nowrap shadow-md group-hover:block">
                                        <p className="font-medium text-foreground">{datum.label}</p>
                                        {series.map((s, index) => (
                                            <p key={s.name} className="flex items-center gap-1.5 text-muted-foreground">
                                                <span className="size-2 rounded-full" style={{ background: s.color }} />
                                                {s.name}: <span className="tabular-nums">{datum.values[index].toLocaleString()}</span>
                                            </p>
                                        ))}
                                        <p className="text-muted-foreground">
                                            Total: <span className="tabular-nums">{total.toLocaleString()}</span>
                                        </p>
                                    </div>

                                    {datum.values.map((value, index) => (
                                        <div
                                            key={series[index].name}
                                            className="w-full max-w-6 rounded-t-[4px]"
                                            style={{
                                                height: `${Math.max((value / max) * 100, value > 0 ? 1 : 0)}%`,
                                                background: series[index].color,
                                            }}
                                        />
                                    ))}
                                </div>
                            );
                        })}
                    </div>
                </div>

                <div className="mt-1.5 flex justify-around gap-2">
                    {data.map((datum) => (
                        <span key={datum.label} className="max-w-14 flex-1 truncate text-center text-[10px] text-muted-foreground" title={datum.label}>
                            {datum.label}
                        </span>
                    ))}
                </div>
            </div>
        </div>
    );
}
