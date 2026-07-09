import {
    
    
    flexRender,
    getCoreRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    
    useReactTable
} from '@tanstack/react-table';
import type {ColumnDef, ColumnFiltersState, SortingState} from '@tanstack/react-table';
import { ChevronDown, ChevronUp, ChevronsUpDown, ChevronLeft, ChevronRight } from 'lucide-react';
import { useState } from 'react';
import { EmptyTableRow } from '@/components/empty-state';
import { SearchInput } from '@/components/search-input';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';

interface DataTableProps<TData, TValue> {
    columns: ColumnDef<TData, TValue>[];
    data: TData[];
    searchPlaceholder?: string;
    searchColumn?: string;
    pageSize?: number;
}

export function DataTable<TData, TValue>({
    columns,
    data,
    searchPlaceholder = 'Search...',
    searchColumn,
    pageSize = 15,
}: DataTableProps<TData, TValue>) {
    const [sorting, setSorting] = useState<SortingState>([]);
    const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([]);
    const [globalFilter, setGlobalFilter] = useState('');

    const table = useReactTable({
        data,
        columns,
        getCoreRowModel: getCoreRowModel(),
        getPaginationRowModel: getPaginationRowModel(),
        getSortedRowModel: getSortedRowModel(),
        getFilteredRowModel: getFilteredRowModel(),
        onSortingChange: setSorting,
        onColumnFiltersChange: setColumnFilters,
        onGlobalFilterChange: setGlobalFilter,
        initialState: {
            pagination: { pageSize },
        },
        state: {
            sorting,
            columnFilters,
            globalFilter,
        },
    });

    const searchValue = searchColumn
        ? ((table.getColumn(searchColumn)?.getFilterValue() as string) ?? '')
        : globalFilter;

    const setSearchValue = (value: string) => {
        if (searchColumn) {
            table.getColumn(searchColumn)?.setFilterValue(value);
        } else {
            setGlobalFilter(value);
        }
    };

    return (
        <div className="space-y-3">
            <div className="flex items-center justify-between gap-3">
                <SearchInput
                    placeholder={searchPlaceholder}
                    value={searchValue}
                    onChange={(e) => setSearchValue(e.target.value)}
                    containerClassName="max-w-xs w-full"
                />
                <span className="text-sm text-muted-foreground">
                    {table.getFilteredRowModel().rows.length} record
                    {table.getFilteredRowModel().rows.length !== 1 ? 's' : ''}
                </span>
            </div>

            <div className="rounded-lg border border-input bg-background">
                <Table>
                    <TableHeader className="bg-muted/50">
                        {table.getHeaderGroups().map((headerGroup) => (
                            <TableRow key={headerGroup.id} className="border-border hover:bg-transparent">
                                {headerGroup.headers.map((header) => {
                                    const canSort = header.column.getCanSort();
                                    const sorted = header.column.getIsSorted();

                                    return (
                                        <TableHead key={header.id}>
                                            {header.isPlaceholder ? null : canSort ? (
                                                <button
                                                    type="button"
                                                    onClick={header.column.getToggleSortingHandler()}
                                                    className={cn(
                                                        'flex items-center gap-1 font-medium text-foreground hover:text-foreground',
                                                    )}
                                                >
                                                    {flexRender(header.column.columnDef.header, header.getContext())}
                                                    {sorted === 'asc' ? (
                                                        <ChevronUp className="h-3.5 w-3.5" />
                                                    ) : sorted === 'desc' ? (
                                                        <ChevronDown className="h-3.5 w-3.5" />
                                                    ) : (
                                                        <ChevronsUpDown className="h-3.5 w-3.5 opacity-40" />
                                                    )}
                                                </button>
                                            ) : (
                                                <span className="font-medium text-foreground">
                                                    {flexRender(header.column.columnDef.header, header.getContext())}
                                                </span>
                                            )}
                                        </TableHead>
                                    );
                                })}
                            </TableRow>
                        ))}
                    </TableHeader>
                    <TableBody>
                        {table.getRowModel().rows.length > 0 ? (
                            table.getRowModel().rows.map((row) => (
                                <TableRow key={row.id} className="border-border">
                                    {row.getVisibleCells().map((cell) => (
                                        <TableCell key={cell.id}>
                                            {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                        </TableCell>
                                    ))}
                                </TableRow>
                            ))
                        ) : (
                            <EmptyTableRow colSpan={columns.length} message="No records found." />
                        )}
                    </TableBody>
                </Table>
            </div>

            {table.getPageCount() > 1 && (
                <div className="flex items-center justify-between gap-2 text-sm">
                    <span className="text-muted-foreground">
                        Page {table.getState().pagination.pageIndex + 1} of {table.getPageCount()}
                    </span>
                    <div className="flex items-center gap-1">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => table.previousPage()}
                            disabled={!table.getCanPreviousPage()}
                        >
                            <ChevronLeft className="h-4 w-4" />
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => table.nextPage()}
                            disabled={!table.getCanNextPage()}
                        >
                            <ChevronRight className="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            )}
        </div>
    );
}
