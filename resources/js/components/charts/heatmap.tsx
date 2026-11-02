type Props = {
    rowLabels: string[];
    columnLabels: string[];
    /** rows × columns, same order as rowLabels/columnLabels */
    values: number[][];
    /** formatter for the number shown inside each cell and in the legend */
    format?: (value: number) => string;
    color?: string;
};

/**
 * Sequential-intensity grid: color is a supporting cue only, every cell also
 * prints its value directly so the pattern reads without relying on hue.
 */
export function Heatmap({
    rowLabels,
    columnLabels,
    values,
    format = (value) => value.toLocaleString(),
    color = 'var(--viz-series-1)',
}: Props) {
    const max = Math.max(...values.flat(), 1);

    const intensityOf = (value: number): number => (max > 0 ? value / max : 0);

    return (
        <div className="space-y-3">
            <div className="overflow-x-auto">
                <table className="w-full border-separate border-spacing-1 text-xs">
                    <thead>
                        <tr>
                            <th className="w-32 px-1 pb-1 text-left font-medium text-muted-foreground">
                                District
                            </th>
                            {columnLabels.map((column) => (
                                <th
                                    key={column}
                                    className="min-w-20 px-1 pb-1 text-center font-medium text-muted-foreground"
                                >
                                    {column}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {rowLabels.map((row, rowIndex) => (
                            <tr key={row}>
                                <th
                                    scope="row"
                                    className="truncate px-1 text-left font-medium text-foreground"
                                    title={row}
                                >
                                    {row}
                                </th>
                                {columnLabels.map((column, columnIndex) => {
                                    const value =
                                        values[rowIndex]?.[columnIndex] ?? 0;
                                    const intensity = intensityOf(value);

                                    return (
                                        <td
                                            key={column}
                                            className="p-0 text-center"
                                        >
                                            <div
                                                className="flex h-9 items-center justify-center rounded-md tabular-nums"
                                                style={{
                                                    background: `color-mix(in srgb, ${color} ${Math.round(intensity * 100)}%, var(--card))`,
                                                    color:
                                                        intensity > 0.55
                                                            ? '#fff'
                                                            : 'var(--foreground)',
                                                }}
                                                title={`${row} · ${column}: ${format(value)}`}
                                            >
                                                {format(value)}
                                            </div>
                                        </td>
                                    );
                                })}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="flex items-center gap-2 text-[11px] text-muted-foreground">
                <span>Low</span>
                <span
                    className="h-2 w-24 rounded-full"
                    style={{
                        background: `linear-gradient(to right, color-mix(in srgb, ${color} 0%, var(--card)), ${color})`,
                    }}
                />
                <span>High</span>
                <span className="ml-2">
                    Darker = more {format(max)} at the high end
                </span>
            </div>
        </div>
    );
}
