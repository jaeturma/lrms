import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { LibraryBig, Pencil, Plus, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useMemo, useState } from 'react';
import { DataTable } from '@/components/data-table';
import { PageHeaderIcon } from '@/components/page-header-icon';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import http from '@/lib/http';

type Summary = {
    total_rows: number;
    imported: number;
    skipped: number;
    errors: Array<{ row: number; message: string }>;
};

type District = {
    id: number;
    municipality_id: number;
    municipality: string | null;
    name: string;
    schools_count: number;
};

type Municipality = {
    id: number;
    name: string;
    districts_count: number;
    barangays_count: number;
    schools_count: number;
};

type Barangay = {
    id: number;
    name: string;
    municipality_id: number;
    municipality: string | null;
    district: string | null;
    schools_count: number;
};

type Props = {
    activeModule?: 'all' | 'districts' | 'municipalities' | 'barangays';
    summary?: Summary | null;
    districts: District[];
    municipalities: Municipality[];
    barangays: Barangay[];
};

type PageProps = {
    flash?: {
        status?: string;
        importSummary?: Summary;
    };
};

export default function AdminLocationsPage({
    activeModule = 'all',
    summary,
    districts,
    municipalities,
    barangays,
}: Props) {
    const page = usePage<PageProps>();
    const status = page.props.flash?.status;

    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [importError, setImportError] = useState<string | undefined>();
    const [importing, setImporting] = useState(false);
    const [importSummary, setImportSummary] = useState<Summary | null>(
        summary ?? page.props.flash?.importSummary ?? null,
    );

    const districtForm = useForm({
        municipality_id: municipalities[0]?.id?.toString() ?? '',
        name: '',
    });
    const [isAddDistrictOpen, setIsAddDistrictOpen] = useState(false);
    const [editingDistrict, setEditingDistrict] = useState<District | null>(null);
    const [editDistrictName, setEditDistrictName] = useState('');
    const [editDistrictMunicipalityId, setEditDistrictMunicipalityId] = useState('');

    const municipalityForm = useForm({ name: '' });
    const [isAddMunicipalityOpen, setIsAddMunicipalityOpen] = useState(false);
    const [editingMunicipality, setEditingMunicipality] = useState<Municipality | null>(null);
    const [editMunicipalityName, setEditMunicipalityName] = useState('');

    const barangayForm = useForm({
        municipality_id: municipalities[0]?.id?.toString() ?? '',
        name: '',
    });
    const [isAddBarangayOpen, setIsAddBarangayOpen] = useState(false);
    const [editingBarangay, setEditingBarangay] = useState<Barangay | null>(null);
    const [editBarangayName, setEditBarangayName] = useState('');
    const [editBarangayMunicipalityId, setEditBarangayMunicipalityId] = useState('');

    const municipalityOptions = useMemo(
        () => municipalities.map((municipality) => ({
            value: municipality.id.toString(),
            label: municipality.name,
        })),
        [municipalities],
    );

    const showDistricts = activeModule === 'all' || activeModule === 'districts';
    const showMunicipalities = activeModule === 'all' || activeModule === 'municipalities';
    const showBarangays = activeModule === 'all' || activeModule === 'barangays';

    const submitImport = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!selectedFile) {
            setImportError('Please select a CSV file to upload.');

            return;
        }

        setImporting(true);
        setImportError(undefined);
        const formData = new FormData();
        formData.append('csv', selectedFile);

        try {
            const response = await http.post('/app/admin/locations/import', formData, {
                headers: { 'Content-Type': 'multipart/form-data', Accept: 'application/json' },
            });
            setImportSummary(response.data.summary);
            setSelectedFile(null);
            router.reload();
        } catch {
            setImportError('Import failed. Confirm CSV format and try again.');
        } finally {
            setImporting(false);
        }
    };

    const districtColumns: ColumnDef<District>[] = [
        { accessorKey: 'name', header: 'Name' },
        { accessorKey: 'municipality', header: 'Municipality' },
        { accessorKey: 'schools_count', header: 'Schools' },
        {
            id: 'actions',
            header: 'Actions',
            enableSorting: false,
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => {
                            setEditingDistrict(row.original);
                            setEditDistrictName(row.original.name);
                            setEditDistrictMunicipalityId(row.original.municipality_id.toString());
                        }}
                    >
                        <Pencil className="h-3.5 w-3.5" />
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="border-red-300 text-red-700 hover:bg-red-50"
                        onClick={() =>
                            router.delete(`/app/admin/locations/districts/${row.original.id}`, {
                                preserveScroll: true,
                            })
                        }
                    >
                        <Trash2 className="h-3.5 w-3.5" />
                    </Button>
                </div>
            ),
        },
    ];

    const municipalityColumns: ColumnDef<Municipality>[] = [
        { accessorKey: 'name', header: 'Name' },
        { accessorKey: 'districts_count', header: 'Districts' },
        { accessorKey: 'barangays_count', header: 'Barangays' },
        { accessorKey: 'schools_count', header: 'Schools' },
        {
            id: 'actions',
            header: 'Actions',
            enableSorting: false,
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => {
                            setEditingMunicipality(row.original);
                            setEditMunicipalityName(row.original.name);
                        }}
                    >
                        <Pencil className="h-3.5 w-3.5" />
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="border-red-300 text-red-700 hover:bg-red-50"
                        onClick={() =>
                            router.delete(`/app/admin/locations/municipalities/${row.original.id}`, {
                                preserveScroll: true,
                            })
                        }
                    >
                        <Trash2 className="h-3.5 w-3.5" />
                    </Button>
                </div>
            ),
        },
    ];

    const barangayColumns: ColumnDef<Barangay>[] = [
        { accessorKey: 'name', header: 'Name' },
        { accessorKey: 'municipality', header: 'Municipality' },
        { accessorKey: 'district', header: 'District' },
        { accessorKey: 'schools_count', header: 'Schools' },
        {
            id: 'actions',
            header: 'Actions',
            enableSorting: false,
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => {
                            setEditingBarangay(row.original);
                            setEditBarangayName(row.original.name);
                            setEditBarangayMunicipalityId(row.original.municipality_id.toString());
                        }}
                    >
                        <Pencil className="h-3.5 w-3.5" />
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="border-red-300 text-red-700 hover:bg-red-50"
                        onClick={() =>
                            router.delete(`/app/admin/locations/barangays/${row.original.id}`, {
                                preserveScroll: true,
                            })
                        }
                    >
                        <Trash2 className="h-3.5 w-3.5" />
                    </Button>
                </div>
            ),
        },
    ];

    const pageTitle = {
        districts: 'Districts',
        municipalities: 'Municipalities',
        barangays: 'Barangays',
        all: 'Location Management',
    }[activeModule];

    return (
        <>
            <Head title={pageTitle} />

            <div className="space-y-6 bg-muted/50 p-4 md:p-6">
                <header className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-input bg-background p-5 shadow-sm">
                    <div className="flex items-center gap-4">
                        <PageHeaderIcon
                            icon={LibraryBig}
                            className="bg-violet-950 text-violet-400 dark:bg-violet-900/60 dark:text-violet-300"
                        />
                        <div>
                            <h1 className="text-2xl font-bold text-foreground">{pageTitle}</h1>
                            <p className="text-sm text-muted-foreground">
                                Manage municipalities, districts, and barangays.
                            </p>
                        </div>
                    </div>
                    <a
                        href="/app/admin/locations/template"
                        className="rounded-md border border-input bg-background px-4 py-2 text-sm text-foreground"
                    >
                        Download CSV Template
                    </a>
                </header>

                <nav className="flex flex-wrap gap-2 text-sm">
                    {(['all', 'districts', 'municipalities', 'barangays'] as const).map((module) => (
                        <Link
                            key={module}
                            href={module === 'all' ? '/app/admin/locations' : `/app/admin/${module}`}
                            className={`rounded-md border px-3 py-1.5 capitalize transition-colors ${
                                activeModule === module
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'border-input bg-background text-foreground hover:bg-muted/50'
                            }`}
                        >
                            {module === 'all' ? 'All' : module}
                        </Link>
                    ))}
                </nav>

                {status && (
                    <p className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {status}
                    </p>
                )}

                <div className="grid gap-6 xl:grid-cols-[1fr,340px]">
                    <div className="space-y-6">
                        {showDistricts && (
                            <Panel
                                title="Districts"
                                action={
                                    <Button type="button" onClick={() => setIsAddDistrictOpen(true)}>
                                        <Plus className="h-4 w-4" />
                                        Add District
                                    </Button>
                                }
                            >
                                <DataTable
                                    columns={districtColumns}
                                    data={districts}
                                    searchPlaceholder="Search districts..."
                                    searchColumn="name"
                                />
                            </Panel>
                        )}

                        {showMunicipalities && (
                            <Panel
                                title="Municipalities"
                                action={
                                    <Button type="button" onClick={() => setIsAddMunicipalityOpen(true)}>
                                        <Plus className="h-4 w-4" />
                                        Add Municipality
                                    </Button>
                                }
                            >
                                <DataTable
                                    columns={municipalityColumns}
                                    data={municipalities}
                                    searchPlaceholder="Search municipalities..."
                                    searchColumn="name"
                                />
                            </Panel>
                        )}

                        {showBarangays && (
                            <Panel
                                title="Barangays"
                                action={
                                    <Button type="button" onClick={() => setIsAddBarangayOpen(true)}>
                                        <Plus className="h-4 w-4" />
                                        Add Barangay
                                    </Button>
                                }
                            >
                                <DataTable
                                    columns={barangayColumns}
                                    data={barangays}
                                    searchPlaceholder="Search barangays..."
                                    searchColumn="name"
                                />
                            </Panel>
                        )}
                    </div>

                    <Panel title="CSV Import">
                        <form
                            onSubmit={submitImport}
                            className="space-y-3 rounded-xl border border-border bg-muted/50 p-4"
                        >
                            <div className="space-y-1">
                                <Label htmlFor="csv">CSV File</Label>
                                <input
                                    id="csv"
                                    type="file"
                                    accept=".csv,text/csv"
                                    onChange={(event) => setSelectedFile(event.target.files?.[0] ?? null)}
                                />
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Required columns: municipality, district, barangay
                            </p>
                            <InputError message={importError} />
                            <Button type="submit" disabled={importing}>
                                {importing ? 'Importing...' : 'Import Locations CSV'}
                            </Button>
                        </form>

                        {importSummary && (
                            <section className="mt-4 rounded-xl border border-border bg-muted/50 p-4">
                                <h3 className="font-semibold text-foreground">Import Summary</h3>
                                <div className="mt-2 grid gap-2 text-sm text-foreground md:grid-cols-3">
                                    <p>Total Rows: {importSummary.total_rows}</p>
                                    <p>Imported: {importSummary.imported}</p>
                                    <p>Skipped: {importSummary.skipped}</p>
                                </div>
                                {importSummary.errors.length > 0 && (
                                    <div className="mt-3 rounded-md bg-red-50 p-3 text-sm text-red-700">
                                        <p className="font-semibold">Validation Report</p>
                                        <ul className="mt-1 list-disc pl-5">
                                            {importSummary.errors.slice(0, 10).map((item, index) => (
                                                <li key={index}>Row {item.row}: {item.message}</li>
                                            ))}
                                        </ul>
                                    </div>
                                )}
                            </section>
                        )}
                    </Panel>
                </div>
            </div>

            <Dialog open={isAddDistrictOpen} onOpenChange={setIsAddDistrictOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Add District</DialogTitle>
                    </DialogHeader>
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            districtForm.post('/app/admin/locations/districts', {
                                preserveScroll: true,
                                onSuccess: () => {
                                    districtForm.reset('name');
                                    setIsAddDistrictOpen(false);
                                },
                            });
                        }}
                        className="space-y-3"
                    >
                        <div className="space-y-1">
                            <Label htmlFor="add-district-municipality">Municipality</Label>
                            <select
                                id="add-district-municipality"
                                value={districtForm.data.municipality_id}
                                onChange={(event) => districtForm.setData('municipality_id', event.target.value)}
                                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                            >
                                {municipalityOptions.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="space-y-1">
                            <Label htmlFor="add-district-name">District name</Label>
                            <Input
                                id="add-district-name"
                                value={districtForm.data.name}
                                onChange={(event) => districtForm.setData('name', event.target.value)}
                                placeholder="District name"
                            />
                        </div>
                        <InputError message={districtForm.errors.name || districtForm.errors.municipality_id} />
                        <div className="flex justify-end gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsAddDistrictOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={districtForm.processing || municipalityOptions.length === 0}
                            >
                                {districtForm.processing ? 'Saving...' : 'Save'}
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={isAddMunicipalityOpen} onOpenChange={setIsAddMunicipalityOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Add Municipality</DialogTitle>
                    </DialogHeader>
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            municipalityForm.post('/app/admin/locations/municipalities', {
                                preserveScroll: true,
                                onSuccess: () => {
                                    municipalityForm.reset('name');
                                    setIsAddMunicipalityOpen(false);
                                },
                            });
                        }}
                        className="space-y-3"
                    >
                        <div className="space-y-1">
                            <Label htmlFor="add-municipality-name">Municipality name</Label>
                            <Input
                                id="add-municipality-name"
                                value={municipalityForm.data.name}
                                onChange={(event) => municipalityForm.setData('name', event.target.value)}
                                placeholder="Municipality name"
                            />
                        </div>
                        <InputError message={municipalityForm.errors.name} />
                        <div className="flex justify-end gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsAddMunicipalityOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button type="submit" disabled={municipalityForm.processing}>
                                {municipalityForm.processing ? 'Saving...' : 'Save'}
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={isAddBarangayOpen} onOpenChange={setIsAddBarangayOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Add Barangay</DialogTitle>
                    </DialogHeader>
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            barangayForm.post('/app/admin/locations/barangays', {
                                preserveScroll: true,
                                onSuccess: () => {
                                    barangayForm.reset('name');
                                    setIsAddBarangayOpen(false);
                                },
                            });
                        }}
                        className="space-y-3"
                    >
                        <div className="space-y-1">
                            <Label htmlFor="add-barangay-municipality">Municipality</Label>
                            <select
                                id="add-barangay-municipality"
                                value={barangayForm.data.municipality_id}
                                onChange={(event) => barangayForm.setData('municipality_id', event.target.value)}
                                className="h-10 w-full rounded-md border border-input px-3 text-sm"
                            >
                                {municipalityOptions.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="space-y-1">
                            <Label htmlFor="add-barangay-name">Barangay name</Label>
                            <Input
                                id="add-barangay-name"
                                value={barangayForm.data.name}
                                onChange={(event) => barangayForm.setData('name', event.target.value)}
                                placeholder="Barangay name"
                            />
                        </div>
                        <InputError message={barangayForm.errors.name || barangayForm.errors.municipality_id} />
                        <div className="flex justify-end gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsAddBarangayOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={barangayForm.processing || municipalityOptions.length === 0}
                            >
                                {barangayForm.processing ? 'Saving...' : 'Save'}
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog
                open={editingDistrict !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setEditingDistrict(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit District</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-3">
                        <div className="space-y-1">
                            <Label>Municipality</Label>
                            <select
                                value={editDistrictMunicipalityId}
                                onChange={(event) => setEditDistrictMunicipalityId(event.target.value)}
                                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                            >
                                {municipalityOptions.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="space-y-1">
                            <Label>Name</Label>
                            <Input
                                value={editDistrictName}
                                onChange={(event) => setEditDistrictName(event.target.value)}
                            />
                        </div>
                        <div className="flex justify-end gap-2">
                            <Button variant="outline" onClick={() => setEditingDistrict(null)}>
                                Cancel
                            </Button>
                            <Button
                                onClick={() => {
                                    if (!editingDistrict) {
                                        return;
                                    }

                                    router.put(
                                        `/app/admin/locations/districts/${editingDistrict.id}`,
                                        {
                                            municipality_id: editDistrictMunicipalityId,
                                            name: editDistrictName,
                                        },
                                        {
                                            preserveScroll: true,
                                            onSuccess: () => setEditingDistrict(null),
                                        },
                                    );
                                }}
                            >
                                Save
                            </Button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>

            <Dialog
                open={editingMunicipality !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setEditingMunicipality(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit Municipality</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-3">
                        <div className="space-y-1">
                            <Label>Name</Label>
                            <Input
                                value={editMunicipalityName}
                                onChange={(event) => setEditMunicipalityName(event.target.value)}
                            />
                        </div>
                        <div className="flex justify-end gap-2">
                            <Button variant="outline" onClick={() => setEditingMunicipality(null)}>
                                Cancel
                            </Button>
                            <Button
                                onClick={() => {
                                    if (!editingMunicipality) {
                                        return;
                                    }

                                    router.put(
                                        `/app/admin/locations/municipalities/${editingMunicipality.id}`,
                                        { name: editMunicipalityName },
                                        {
                                            preserveScroll: true,
                                            onSuccess: () => setEditingMunicipality(null),
                                        },
                                    );
                                }}
                            >
                                Save
                            </Button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>

            <Dialog
                open={editingBarangay !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setEditingBarangay(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit Barangay</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-3">
                        <div className="space-y-1">
                            <Label>Municipality</Label>
                            <select
                                value={editBarangayMunicipalityId}
                                onChange={(event) => setEditBarangayMunicipalityId(event.target.value)}
                                className="h-10 w-full rounded-md border border-input px-3 text-sm"
                            >
                                {municipalityOptions.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="space-y-1">
                            <Label>Name</Label>
                            <Input
                                value={editBarangayName}
                                onChange={(event) => setEditBarangayName(event.target.value)}
                            />
                        </div>
                        <div className="flex justify-end gap-2">
                            <Button variant="outline" onClick={() => setEditingBarangay(null)}>
                                Cancel
                            </Button>
                            <Button
                                onClick={() => {
                                    if (!editingBarangay) {
                                        return;
                                    }

                                    router.put(
                                        `/app/admin/locations/barangays/${editingBarangay.id}`,
                                        {
                                            municipality_id: editBarangayMunicipalityId,
                                            name: editBarangayName,
                                        },
                                        {
                                            preserveScroll: true,
                                            onSuccess: () => setEditingBarangay(null),
                                        },
                                    );
                                }}
                            >
                                Save
                            </Button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
}

function Panel({
    title,
    action,
    children,
}: {
    title: string;
    action?: React.ReactNode;
    children: React.ReactNode;
}) {
    return (
        <section className="rounded-2xl border border-input bg-background p-5 shadow-sm">
            <div className="mb-4 flex items-center justify-between gap-3">
                <h2 className="text-lg font-semibold text-foreground">{title}</h2>
                {action}
            </div>
            {children}
        </section>
    );
}
