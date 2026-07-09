import { MoreHorizontal } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';

export type RowAction = {
    label: string;
    icon?: LucideIcon;
    onSelect: () => void;
    variant?: 'default' | 'destructive';
    disabled?: boolean;
};

type RowActionsProps = {
    /** Accessible name for the trigger button, e.g. "Actions for Dell Laptop". */
    label: string;
    actions: RowAction[];
};

/**
 * Collapses several per-row icon-only buttons into one accessible kebab
 * menu: a single labeled trigger plus text-labeled menu items, instead of
 * multiple icon buttons with no accessible name.
 */
export function RowActions({ label, actions }: RowActionsProps) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button type="button" variant="outline" size="icon" aria-label={label}>
                    <MoreHorizontal className="h-4 w-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                {actions.map((action) => (
                    <DropdownMenuItem
                        key={action.label}
                        variant={action.variant}
                        disabled={action.disabled}
                        onSelect={action.onSelect}
                    >
                        {action.icon && <action.icon className="mr-2 h-4 w-4" />}
                        {action.label}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
