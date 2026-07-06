import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import http from '@/lib/http';

type LearningResourceRow = {
    id?: number;
    resource_type: string;
    issue_defect: string;
    quantity: number;
    publisher: string;
};

type Props = {
    initialRows: LearningResourceRow[];
    learningResourceTypes: string[];
};

const emptyRow = (): LearningResourceRow => ({
    resource_type: '',
    issue_defect: '',
    quantity: 1,
    publisher: '',
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
                    issue_defect: row.issue_defect,
                    quantity: Number(row.quantity),
                    publisher: row.publisher,
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
            <div className="overflow-x-auto rounded-xl border border-slate-200">
                <table className="min-w-full text-sm">
                    <thead className="bg-slate-100 text-left text-slate-700">
                        <tr>
                            <th className="px-3 py-2">Type of Learning Resource</th>
                            <th className="px-3 py-2">Issue/Defect</th>
                            <th className="px-3 py-2">Quantity</th>
                            <th className="px-3 py-2">Printer/Publisher</th>
                            <th className="px-3 py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row, index) => (
                            <tr key={index} className="border-t border-slate-200">
                                <td className="px-3 py-2">
                                    <select
                                        value={row.resource_type}
                                        onChange={(event) =>
                                            updateRow(index, 'resource_type', event.target.value)
                                        }
                                        required
                                        className="h-9 w-full rounded-md border border-slate-300 bg-white px-3"
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
                                        value={row.issue_defect}
                                        onChange={(event) =>
                                            updateRow(index, 'issue_defect', event.target.value)
                                        }
                                        required
                                    />
                                </td>
                                <td className="px-3 py-2">
                                    <Input
                                        type="number"
                                        min={1}
                                        value={row.quantity}
                                        onChange={(event) =>
                                            updateRow(index, 'quantity', Number(event.target.value))
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

            <div className="sticky bottom-0 bg-white py-3 md:static md:py-0">
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
