import { Head, router, useForm } from '@inertiajs/react';
import { Eye, History, Loader2, Pencil, Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import { toast } from 'sonner';
import { EmptyTableRow } from '@/components/empty-state';
import { RowActions } from '@/components/row-actions';
import { SearchInput } from '@/components/search-input';
import { StatusBadge } from '@/components/status-badge';
import type { StatusTone } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';

type EquipmentItem = {
    id: number;
    other_equipment_catalog_item_id: number | null;
    item_code: string;
    item_name: string;
    category: string;
    brand: string | null;
    model: string | null;
    specifications: string | null;
    manufacturer: string | null;
    serial_number: string | null;
    property_number: string | null;
    barcode: string | null;
    qr_code: string | null;
    acquisition_date: string | null;
    acquisition_cost: string | number | null;
    funding_source: string | null;
    supplier: string | null;
    date_delivered: string | null;
    ier_no: string | null;
    warranty_expires_on: string | null;
    useful_life_years: number | null;
    current_location: string | null;
    assigned_personnel: string | null;
    condition: string;
    status: string;
    remarks: string | null;
};

type Movement = {
    id: number;
    equipment_id: number;
    item_name: string | null;
    item_code: string | null;
    type: string;
    from_value: string | null;
    to_value: string | null;
    notes: string | null;
    recorded_by: string | null;
    created_at: string | null;
};

type CatalogEntry = {
    id: number;
    item_name: string;
    category: string;
    brand: string | null;
    model: string | null;
    specifications: string | null;
    manufacturer: string | null;
    description: string | null;
};

type Props = {
    equipment: EquipmentItem[];
    movements: Movement[];
    catalog: CatalogEntry[];
    categories: string[];
    conditions: string[];
    statuses: string[];
};

const MOVEMENT_TYPE_LABELS: Record<string, string> = {
    created: 'Registered',
    status_change: 'Status Change',
    condition_change: 'Condition Change',
    reassigned: 'Reassigned',
    relocated: 'Relocated',
    updated: 'Details Updated',
    deleted: 'Removed',
};

const emptyForm = (categories: string[], conditions: string[], statuses: string[]) => ({
    other_equipment_catalog_item_id: '',
    item_code: '',
    item_name: '',
    category: categories[0] ?? '',
    brand: '',
    model: '',
    specifications: '',
    manufacturer: '',
    serial_number: '',
    property_number: '',
    barcode: '',
    qr_code: '',
    acquisition_date: '',
    acquisition_cost: '',
    funding_source: '',
    supplier: '',
    date_delivered: '',
    ier_no: '',
    warranty_expires_on: '',
    useful_life_years: '',
    current_location: '',
    assigned_personnel: '',
    condition: conditions[1] ?? 'Good',
    status: statuses[0] ?? 'Available',
    remarks: '',
    movement_notes: '',
});

export default function SchoolOtherEquipment({ equipment, movements, catalog, categories, conditions, statuses }: Props) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [viewItem, setViewItem] = useState<EquipmentItem | null>(null);
    const [historyEquipmentId, setHistoryEquipmentId] = useState<number | null>(null);
    const [search, setSearch] = useState('');
    const [categoryFilter, setCategoryFilter] = useState('');
    const [statusFilter, setStatusFilter] = useState('');

    const { data, setData, post, put, processing, errors, clearErrors, setDefaults } = useForm(
        emptyForm(categories, conditions, statuses),
    );

    const selectedCatalogItem = useMemo(
        () => catalog.find((item) => String(item.id) === String(data.other_equipment_catalog_item_id)) ?? null,
        [catalog, data.other_equipment_catalog_item_id],
    );

    const openAddDialog = () => {
        setEditingId(null);
        clearErrors();
        setDefaults(emptyForm(categories, conditions, statuses));
        setData(emptyForm(categories, conditions, statuses));
        setDialogOpen(true);
    };

    const openEditDialog = (item: EquipmentItem) => {
        setEditingId(item.id);
        clearErrors();
        setData({
            other_equipment_catalog_item_id: item.other_equipment_catalog_item_id ? String(item.other_equipment_catalog_item_id) : '',
            item_code: item.item_code ?? '',
            item_name: item.item_name ?? '',
            category: item.category ?? categories[0] ?? '',
            brand: item.brand ?? '',
            model: item.model ?? '',
            specifications: item.specifications ?? '',
            manufacturer: item.manufacturer ?? '',
            serial_number: item.serial_number ?? '',
            property_number: item.property_number ?? '',
            barcode: item.barcode ?? '',
            qr_code: item.qr_code ?? '',
            acquisition_date: item.acquisition_date ?? '',
            acquisition_cost: item.acquisition_cost !== null ? String(item.acquisition_cost) : '',
            funding_source: item.funding_source ?? '',
            supplier: item.supplier ?? '',
            date_delivered: item.date_delivered ?? '',
            ier_no: item.ier_no ?? '',
            warranty_expires_on: item.warranty_expires_on ?? '',
            useful_life_years: item.useful_life_years !== null ? String(item.useful_life_years) : '',
            current_location: item.current_location ?? '',
            assigned_personnel: item.assigned_personnel ?? '',
            condition: item.condition,
            status: item.status,
            remarks: item.remarks ?? '',
            movement_notes: '',
        });
        setDialogOpen(true);
    };

    const chooseCatalogItem = (catalogItemId: string) => {
        const catalogItem = catalog.find((item) => String(item.id) === catalogItemId);

        if (!catalogItem) {
            setData({
                ...data,
                other_equipment_catalog_item_id: '',
                item_name: '',
                category: categories[0] ?? '',
                brand: '',
                model: '',
                specifications: '',
                manufacturer: '',
            });

            return;
        }

        setData({
            ...data,
            other_equipment_catalog_item_id: String(catalogItem.id),
            item_name: catalogItem.item_name,
            category: catalogItem.category,
            brand: catalogItem.brand ?? '',
            model: catalogItem.model ?? '',
            specifications: catalogItem.specifications ?? '',
            manufacturer: catalogItem.manufacturer ?? '',
        });
    };

    const submit = () => {
        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setDialogOpen(false);
                toast.success(editingId === null ? 'Equipment registered.' : 'Equipment updated.');
            },
        };

        if (editingId === null) {
            post('/school/other-equipment', options);
        } else {
            put(`/school/other-equipment/${editingId}`, options);
        }
    };

    const deleteEquipment = (item: EquipmentItem) => {
        if (!window.confirm(`Remove ${item.item_name} (${item.item_code}) from the equipment inventory?`)) {
            return;
        }

        router.delete(`/school/other-equipment/${item.id}`, {
            preserveScroll: true,
            onSuccess: () => toast.success('Equipment removed.'),
        });
    };

    const filteredEquipment = useMemo(() => {
        const term = search.trim().toLowerCase();

        return equipment.filter((item) => {
            if (categoryFilter && item.category !== categoryFilter) {
                return false;
            }

            if (statusFilter && item.status !== statusFilter) {
                return false;
            }

            if (term === '') {
                return true;
            }

            return [item.item_name, item.item_code, item.serial_number, item.property_number, item.brand, item.model]
                .filter(Boolean)
                .some((value) => String(value).toLowerCase().includes(term));
        });
    }, [equipment, search, categoryFilter, statusFilter]);

    const visibleMovements = useMemo(
        () =>
            historyEquipmentId === null
                ? movements
                : movements.filter((movement) => movement.equipment_id === historyEquipmentId),
        [movements, historyEquipmentId],
    );

    const historyItem = equipment.find((item) => item.id === historyEquipmentId);

    const statusTone = (status: string): StatusTone => {
        switch (status) {
            case 'Available':
                return 'success';
            case 'In Use':
                return 'info';
            case 'Borrowed':
                return 'accent';
            case 'Missing':
            case 'Lost':
                return 'danger';
            default:
                return 'warning';
        }
    };

    return (
        <>
            <Head title="Other Equipment" />

            <div className="space-y-4 bg-background/40 p-3 md:p-4">
                <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h2 className="text-lg font-semibold text-foreground">Other Equipment</h2>
                            <p className="text-sm text-muted-foreground">
                                Register and track TVL, ALS, Library, SPED, Sports, and other equipment. Status, condition,
                                assignment, and location changes are kept in the movement history.
                            </p>
                        </div>
                        <Button type="button" onClick={openAddDialog}>
                            <Plus className="mr-1 h-4 w-4" />
                            Register Equipment
                        </Button>
                    </div>

                    <div className="mb-4 flex flex-wrap gap-2">
                        <SearchInput
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search name, code, serial, property no."
                            containerClassName="w-72"
                        />
                        <select
                            value={categoryFilter}
                            onChange={(event) => setCategoryFilter(event.target.value)}
                            className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                            aria-label="Filter by category"
                        >
                            <option value="">All Categories</option>
                            {categories.map((category) => (
                                <option key={category} value={category}>
                                    {category}
                                </option>
                            ))}
                        </select>
                        <select
                            value={statusFilter}
                            onChange={(event) => setStatusFilter(event.target.value)}
                            className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                            aria-label="Filter by status"
                        >
                            <option value="">All Statuses</option>
                            {statuses.map((status) => (
                                <option key={status} value={status}>
                                    {status}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="overflow-x-auto rounded-xl border border-border">
                        <table className="min-w-full text-sm">
                            <thead className="bg-muted text-left text-foreground">
                                <tr>
                                    <th className="px-3 py-2">Item Code</th>
                                    <th className="px-3 py-2">Name</th>
                                    <th className="px-3 py-2">Category</th>
                                    <th className="px-3 py-2">Condition</th>
                                    <th className="px-3 py-2">Status</th>
                                    <th className="px-3 py-2">Assigned To</th>
                                    <th className="px-3 py-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {filteredEquipment.length === 0 && (
                                    <EmptyTableRow
                                        colSpan={7}
                                        message={
                                            equipment.length === 0
                                                ? 'No equipment registered yet. Click Register Equipment to add the first item.'
                                                : 'No equipment matches the current filters.'
                                        }
                                    />
                                )}
                                {filteredEquipment.map((item) => (
                                    <tr key={item.id} className="border-t border-border">
                                        <td className="px-3 py-2 font-mono text-xs text-muted-foreground">{item.item_code}</td>
                                        <td className="px-3 py-2 font-medium text-foreground">{item.item_name}</td>
                                        <td className="px-3 py-2 text-muted-foreground">{item.category}</td>
                                        <td className="px-3 py-2 text-muted-foreground">{item.condition}</td>
                                        <td className="px-3 py-2">
                                            <StatusBadge tone={statusTone(item.status)}>{item.status}</StatusBadge>
                                        </td>
                                        <td className="px-3 py-2 text-muted-foreground">{item.assigned_personnel ?? '-'}</td>
                                        <td className="px-3 py-2">
                                            <RowActions
                                                label={`Actions for ${item.item_name}`}
                                                actions={[
                                                    { label: 'View', icon: Eye, onSelect: () => setViewItem(item) },
                                                    { label: 'Edit', icon: Pencil, onSelect: () => openEditDialog(item) },
                                                    {
                                                        label: 'History',
                                                        icon: History,
                                                        onSelect: () =>
                                                            setHistoryEquipmentId(historyEquipmentId === item.id ? null : item.id),
                                                    },
                                                    {
                                                        label: 'Delete',
                                                        icon: Trash2,
                                                        variant: 'destructive',
                                                        onSelect: () => deleteEquipment(item),
                                                    },
                                                ]}
                                            />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>

                <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <h2 className="text-lg font-semibold text-foreground">
                            Movement History
                            {historyItem ? ` · ${historyItem.item_name} (${historyItem.item_code})` : ''}
                        </h2>
                        {historyEquipmentId !== null && (
                            <Button type="button" variant="outline" size="sm" onClick={() => setHistoryEquipmentId(null)}>
                                Show All
                            </Button>
                        )}
                    </div>

                    <div className="overflow-x-auto rounded-xl border border-border">
                        <table className="min-w-full text-sm">
                            <thead className="bg-muted text-left text-foreground">
                                <tr>
                                    <th className="px-3 py-2">Date</th>
                                    <th className="px-3 py-2">Equipment</th>
                                    <th className="px-3 py-2">Event</th>
                                    <th className="px-3 py-2">From → To</th>
                                    <th className="px-3 py-2">Notes</th>
                                    <th className="px-3 py-2">Recorded By</th>
                                </tr>
                            </thead>
                            <tbody>
                                {visibleMovements.length === 0 && (
                                    <EmptyTableRow colSpan={6} message="No equipment movements recorded yet." />
                                )}
                                {visibleMovements.map((movement) => (
                                    <tr key={movement.id} className="border-t border-border">
                                        <td className="px-3 py-2 whitespace-nowrap text-muted-foreground">
                                            {movement.created_at ? new Date(movement.created_at).toLocaleString() : '-'}
                                        </td>
                                        <td className="px-3 py-2">
                                            {movement.item_name ?? '-'}
                                            {movement.item_code && (
                                                <span className="ml-1 font-mono text-xs text-muted-foreground">
                                                    {movement.item_code}
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-3 py-2">{MOVEMENT_TYPE_LABELS[movement.type] ?? movement.type}</td>
                                        <td className="px-3 py-2 text-muted-foreground">
                                            {movement.from_value || movement.to_value
                                                ? `${movement.from_value ?? '—'} → ${movement.to_value ?? '—'}`
                                                : '—'}
                                        </td>
                                        <td className="px-3 py-2 text-muted-foreground">{movement.notes ?? '-'}</td>
                                        <td className="px-3 py-2 text-muted-foreground">{movement.recorded_by ?? '-'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-6xl">
                    <DialogHeader>
                        <DialogTitle>{editingId === null ? 'Register Equipment' : 'Edit Equipment'}</DialogTitle>
                        <DialogDescription>
                            Select from the division equipment catalog, then add the school-specific inventory details.
                            Leave the item code blank to generate one automatically.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-3">
                        <p className="text-sm font-semibold text-foreground">Identification</p>
                        <Field label="Catalog Equipment" error={errors.other_equipment_catalog_item_id}>
                            <select
                                value={data.other_equipment_catalog_item_id}
                                onChange={(event) => chooseCatalogItem(event.target.value)}
                                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option value="">Select equipment from catalog</option>
                                {catalog.map((item) => (
                                    <option key={item.id} value={item.id}>
                                        {[item.item_name, item.brand, item.model].filter(Boolean).join(' - ')}
                                    </option>
                                ))}
                            </select>
                        </Field>
                        {selectedCatalogItem && (
                            <div className="rounded-md border border-border bg-muted/40 p-3 text-sm">
                                <p className="font-medium text-foreground">{selectedCatalogItem.item_name}</p>
                                <p className="text-muted-foreground">
                                    {[selectedCatalogItem.category, selectedCatalogItem.brand, selectedCatalogItem.model]
                                        .filter(Boolean)
                                        .join(' / ')}
                                </p>
                                {selectedCatalogItem.description && (
                                    <p className="mt-1 text-muted-foreground">{selectedCatalogItem.description}</p>
                                )}
                            </div>
                        )}
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <Field label="Item Name *" error={errors.item_name}>
                                <Input
                                    value={data.item_name}
                                    onChange={(event) => setData('item_name', event.target.value)}
                                    readOnly={selectedCatalogItem !== null}
                                />
                            </Field>
                            <Field label="Category *" error={errors.category}>
                                <select
                                    value={data.category}
                                    onChange={(event) => setData('category', event.target.value)}
                                    className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                    disabled={selectedCatalogItem !== null}
                                >
                                    {categories.map((category) => (
                                        <option key={category} value={category}>
                                            {category}
                                        </option>
                                    ))}
                                </select>
                            </Field>
                            <Field label="Item Code" error={errors.item_code}>
                                <Input
                                    value={data.item_code}
                                    onChange={(event) => setData('item_code', event.target.value)}
                                    placeholder="Auto-generated when blank"
                                />
                            </Field>
                            <Field label="Brand" error={errors.brand}>
                                <Input
                                    value={data.brand}
                                    onChange={(event) => setData('brand', event.target.value)}
                                    readOnly={selectedCatalogItem !== null}
                                />
                            </Field>
                            <Field label="Model" error={errors.model}>
                                <Input
                                    value={data.model}
                                    onChange={(event) => setData('model', event.target.value)}
                                    readOnly={selectedCatalogItem !== null}
                                />
                            </Field>
                            <Field label="Manufacturer" error={errors.manufacturer}>
                                <Input
                                    value={data.manufacturer}
                                    onChange={(event) => setData('manufacturer', event.target.value)}
                                    readOnly={selectedCatalogItem !== null}
                                />
                            </Field>
                            <Field label="Serial Number" error={errors.serial_number}>
                                <Input value={data.serial_number} onChange={(event) => setData('serial_number', event.target.value)} />
                            </Field>
                            <Field label="Property Number" error={errors.property_number}>
                                <Input value={data.property_number} onChange={(event) => setData('property_number', event.target.value)} />
                            </Field>
                            <Field label="Barcode" error={errors.barcode}>
                                <Input value={data.barcode} onChange={(event) => setData('barcode', event.target.value)} />
                            </Field>
                            <Field label="QR Code" error={errors.qr_code}>
                                <Input
                                    value={data.qr_code}
                                    onChange={(event) => setData('qr_code', event.target.value)}
                                    placeholder="Defaults to item code"
                                />
                            </Field>
                        </div>
                        <Field label="Specifications" error={errors.specifications}>
                            <textarea
                                rows={2}
                                value={data.specifications}
                                onChange={(event) => setData('specifications', event.target.value)}
                                readOnly={selectedCatalogItem !== null}
                                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            />
                        </Field>

                        <p className="mt-1 text-sm font-semibold text-foreground">Acquisition</p>
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <Field label="Acquisition Date" error={errors.acquisition_date}>
                                <Input
                                    type="date"
                                    value={data.acquisition_date}
                                    onChange={(event) => setData('acquisition_date', event.target.value)}
                                />
                            </Field>
                            <Field label="Acquisition Cost (₱)" error={errors.acquisition_cost}>
                                <Input
                                    type="number"
                                    min={0}
                                    step="0.01"
                                    value={data.acquisition_cost}
                                    onChange={(event) => setData('acquisition_cost', event.target.value)}
                                />
                            </Field>
                            <Field label="Funding Source" error={errors.funding_source}>
                                <Input value={data.funding_source} onChange={(event) => setData('funding_source', event.target.value)} />
                            </Field>
                            <Field label="Supplier" error={errors.supplier}>
                                <Input value={data.supplier} onChange={(event) => setData('supplier', event.target.value)} />
                            </Field>
                            <Field label="Date Delivered" error={errors.date_delivered}>
                                <Input
                                    type="date"
                                    value={data.date_delivered}
                                    onChange={(event) => setData('date_delivered', event.target.value)}
                                />
                            </Field>
                            <Field label="IER No." error={errors.ier_no}>
                                <Input value={data.ier_no} onChange={(event) => setData('ier_no', event.target.value)} />
                            </Field>
                            <Field label="Warranty Expiration" error={errors.warranty_expires_on}>
                                <Input
                                    type="date"
                                    value={data.warranty_expires_on}
                                    onChange={(event) => setData('warranty_expires_on', event.target.value)}
                                />
                            </Field>
                            <Field label="Useful Life (years)" error={errors.useful_life_years}>
                                <Input
                                    type="number"
                                    min={0}
                                    max={100}
                                    value={data.useful_life_years}
                                    onChange={(event) => setData('useful_life_years', event.target.value)}
                                />
                            </Field>
                        </div>

                        <p className="mt-1 text-sm font-semibold text-foreground">Assignment & Status</p>
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <Field label="Current Location" error={errors.current_location}>
                                <Input
                                    value={data.current_location}
                                    onChange={(event) => setData('current_location', event.target.value)}
                                    placeholder="e.g. Computer Laboratory"
                                />
                            </Field>
                            <Field label="Assigned Personnel" error={errors.assigned_personnel}>
                                <Input
                                    value={data.assigned_personnel}
                                    onChange={(event) => setData('assigned_personnel', event.target.value)}
                                />
                            </Field>
                            <Field label="Condition *" error={errors.condition}>
                                <select
                                    value={data.condition}
                                    onChange={(event) => setData('condition', event.target.value)}
                                    className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    {conditions.map((condition) => (
                                        <option key={condition} value={condition}>
                                            {condition}
                                        </option>
                                    ))}
                                </select>
                            </Field>
                            <Field label="Inventory Status *" error={errors.status}>
                                <select
                                    value={data.status}
                                    onChange={(event) => setData('status', event.target.value)}
                                    className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    {statuses.map((status) => (
                                        <option key={status} value={status}>
                                            {status}
                                        </option>
                                    ))}
                                </select>
                            </Field>
                        </div>
                        <Field label="Remarks" error={errors.remarks}>
                            <textarea
                                rows={2}
                                value={data.remarks}
                                onChange={(event) => setData('remarks', event.target.value)}
                                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            />
                        </Field>
                        {editingId !== null && (
                            <Field label="Movement Notes (for this change)" error={errors.movement_notes}>
                                <Input
                                    value={data.movement_notes}
                                    onChange={(event) => setData('movement_notes', event.target.value)}
                                    placeholder="e.g. Issued to property custodian for the school year"
                                />
                            </Field>
                        )}
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>
                            Cancel
                        </Button>
                        <Button type="button" onClick={submit} disabled={processing}>
                            {processing ? (
                                <>
                                    <Loader2 className="mr-1 h-4 w-4 animate-spin" />
                                    Saving...
                                </>
                            ) : editingId === null ? (
                                'Register Equipment'
                            ) : (
                                'Save Changes'
                            )}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={viewItem !== null} onOpenChange={(open) => !open && setViewItem(null)}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>{viewItem?.item_name}</DialogTitle>
                        <DialogDescription className="font-mono">{viewItem?.item_code}</DialogDescription>
                    </DialogHeader>

                    {viewItem && (
                        <div className="grid gap-1.5 text-sm text-foreground sm:grid-cols-2">
                            <DetailRow label="Category" value={viewItem.category} />
                            <DetailRow label="Brand" value={viewItem.brand} />
                            <DetailRow label="Model" value={viewItem.model} />
                            <DetailRow label="Manufacturer" value={viewItem.manufacturer} />
                            <DetailRow label="Serial Number" value={viewItem.serial_number} />
                            <DetailRow label="Property Number" value={viewItem.property_number} />
                            <DetailRow label="Barcode" value={viewItem.barcode} />
                            <DetailRow label="QR Code" value={viewItem.qr_code} />
                            <DetailRow label="Acquisition Date" value={viewItem.acquisition_date} />
                            <DetailRow
                                label="Acquisition Cost"
                                value={viewItem.acquisition_cost !== null ? `₱${Number(viewItem.acquisition_cost).toLocaleString()}` : null}
                            />
                            <DetailRow label="Funding Source" value={viewItem.funding_source} />
                            <DetailRow label="Supplier" value={viewItem.supplier} />
                            <DetailRow label="Date Delivered" value={viewItem.date_delivered} />
                            <DetailRow label="IER No." value={viewItem.ier_no} />
                            <DetailRow label="Warranty Expiration" value={viewItem.warranty_expires_on} />
                            <DetailRow
                                label="Useful Life"
                                value={viewItem.useful_life_years !== null ? `${viewItem.useful_life_years} years` : null}
                            />
                            <DetailRow label="Location" value={viewItem.current_location} />
                            <DetailRow label="Assigned Personnel" value={viewItem.assigned_personnel} />
                            <DetailRow label="Condition" value={viewItem.condition} />
                            <DetailRow label="Status" value={viewItem.status} />
                            <div className="sm:col-span-2">
                                <DetailRow label="Specifications" value={viewItem.specifications} />
                            </div>
                            <div className="sm:col-span-2">
                                <DetailRow label="Remarks" value={viewItem.remarks} />
                            </div>
                        </div>
                    )}

                    <DialogFooter>
                        <Button type="button" onClick={() => setViewItem(null)}>
                            Close
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return (
        <div>
            <label className="mb-1 block text-sm font-medium text-foreground">{label}</label>
            {children}
            {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
        </div>
    );
}

function DetailRow({ label, value }: { label: string; value?: string | null }) {
    return (
        <p>
            <span className="font-semibold text-foreground">{label}:</span>{' '}
            <span className="text-muted-foreground">{value && String(value).trim() !== '' ? value : '-'}</span>
        </p>
    );
}

SchoolOtherEquipment.layout = {
    breadcrumbs: [
        {
            title: 'Other Equipment',
            href: '/school/other-equipment',
        },
    ],
};
