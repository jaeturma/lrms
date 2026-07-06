import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import http from '@/lib/http';

type LearningResourceRow = {
    id?: number;
    resource_type: string;
    title: string;
    publisher: string;
    quantity_delivered: number;
    quantity_with_issue_defect: number;
    remarks: string;
};

type Props = {
    initialRows: LearningResourceRow[];
    learningResourceTypes: string[];
};

const emptyRow = (): LearningResourceRow => ({
    resource_type: '',
    title: '',
    publisher: '',
    quantity_delivered: 1,
    quantity_with_issue_defect: 0,
    remarks: '',
});

export default function LearningResourcesTable({ initialRows, learningResourceTypes }: Props) {
    const [rows, setRows] = useState<LearningResourceRow[]>(
        initialRows.length > 0 ? initialRows : [emptyRow()],
    );
    const [saving, setSaving] = useState(false);
    const [status, setStatus] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);

    const updateRow = (index: number, key: keyof LearningResourceRow, value: string | number) => {
        setRows((currentRows) =>
            currentRows.map((row, rowIndex) =>
                rowIndex === index ? { ...row, [key]: value } : row,
            ),
        );
    };

    const addRow = () => {
        setRows((currentRows) => [...currentRows, emptyRow()]);
    };

    const removeRow = (index: number) => {
        setRows((currentRows) => currentRows.filter((_, rowIndex) => rowIndex !== index));
    };

    const save = async () => {
        setSaving(true);
        setStatus(null);
        setError(null);

        try {
            const payload = {
                resources: rows.map((row) => ({
                    resource_type: row.resource_type,
                    title: row.title,
                    publisher: row.publisher,
                    quantity_delivered: Number(row.quantity_delivered),
                    quantity_with_issue_defect: Number(row.quantity_with_issue_defect),
                    remarks: row.remarks,
                })),
            };

            const response = await http.put('/school/resources', payload, {
                headers: {
                    Accept: 'application/json',
                },
            });

            setRows(response.data.resources ?? rows);
            setStatus(response.data.message ?? 'Saved.');
        } catch {
            setError('Unable to save learning resources. Please check your entries.');
        } finally {
            setSaving(false);
        }
    };

    return (
        <section className="space-y-3">
            <div className="overflow-x-auto rounded-xl border border-border">
                <table className="min-w-full text-sm">
                    <thead className="bg-muted text-left text-foreground">
                        <tr>
                            <th className="px-3 py-2">Type</th>
                            <th className="px-3 py-2">Title</th>
                            <th className="px-3 py-2">Publisher</th>
                            <th className="px-3 py-2">Quantity Delivered</th>
                            <th className="px-3 py-2">Quantity with Issue/Defect</th>
                            <th className="px-3 py-2">Remarks</th>
                            <th className="px-3 py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row, index) => (
                            <tr key={index} className="border-t border-border">
                                <td className="px-3 py-2">
                                    <select
                                        value={row.resource_type}
                                        onChange={(event) =>
                                            updateRow(index, 'resource_type', event.target.value)
                                        }
                                        required
                                        className="h-9 w-full rounded-md border border-input bg-background px-3"
                                    >
                                        <option value="">Select type</option>
                                        {learningResourceTypes.map((type) => (
                                            <option key={type} value={type}>
                                                {type}
                                            </option>
                                        ))}
                                    </select>
                                </td>
                                <td className="px-3 py-2">
                                    <Input
                                        value={row.title}
                                        onChange={(event) =>
                                            updateRow(index, 'title', event.target.value)
                                        }
                                        required
                                    />
                                </td>
                                <td className="px-3 py-2">
                                    <Input
                                        value={row.publisher}
                                        onChange={(event) =>
                                            updateRow(index, 'publisher', event.target.value)
                                        }
                                        required
                                    />
                                </td>
                                <td className="px-3 py-2">
                                    <Input
                                        type="number"
                                        min={1}
                                        value={row.quantity_delivered}
                                        onChange={(event) =>
                                            updateRow(index, 'quantity_delivered', Number(event.target.value))
                                        }
                                        required
                                    />
                                </td>
                                <td className="px-3 py-2">
                                    <Input
                                        type="number"
                                        min={0}
                                        value={row.quantity_with_issue_defect}
                                        onChange={(event) =>
                                            updateRow(index, 'quantity_with_issue_defect', Number(event.target.value))
                                        }
                                        required
                                    />
                                </td>
                                <td className="px-3 py-2">
                                    <Input
                                        value={row.remarks}
                                        onChange={(event) =>
                                            updateRow(index, 'remarks', event.target.value)
                                        }
                                    />
                                </td>
                                <td className="px-3 py-2">
                                    <Button
                                        variant="outline"
                                        type="button"
                                        onClick={() => removeRow(index)}
                                        disabled={rows.length === 1}
                                    >
                                        Remove
                                    </Button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="flex flex-wrap gap-2">
                <Button type="button" variant="outline" onClick={addRow} disabled={learningResourceTypes.length === 0}>
                    Add Row
                </Button>
            </div>

            {learningResourceTypes.length === 0 && (
                <p className="text-sm text-amber-700">
                    No active learning material types found. Please ask your admin to add at least one type.
                </p>
            )}

            {status && <p className="text-sm text-emerald-700">{status}</p>}
            {error && <p className="text-sm text-red-600">{error}</p>}

            <div className="sticky bottom-0 bg-card py-3 md:static md:py-0">
                <Button
                    type="button"
                    className="w-full md:w-auto"
                    onClick={save}
                    disabled={saving || learningResourceTypes.length === 0}
                >
                    {saving ? 'Saving...' : 'Save Changes'}
                </Button>
            </div>
        </section>
    );
}
