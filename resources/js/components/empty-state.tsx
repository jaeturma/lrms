import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type EmptyStateProps = {
    message: ReactNode;
    icon?: LucideIcon;
    action?: ReactNode;
    className?: string;
};

/** Shared empty-state block: optional icon, message, optional action/CTA. */
export function EmptyState({ message, icon: Icon, action, className }: EmptyStateProps) {
    return (
        <div
            className={cn(
                'flex flex-col items-center justify-center gap-2 px-3 py-10 text-center text-sm text-muted-foreground',
                className,
            )}
        >
            {Icon && <Icon className="size-6 text-muted-foreground/70" aria-hidden="true" />}
            <p>{message}</p>
            {action}
        </div>
    );
}

type EmptyTableRowProps = {
    colSpan: number;
    message: ReactNode;
    icon?: LucideIcon;
};

/** Drop-in replacement for a hand-rolled "No records" <tr><td colSpan> row. */
export function EmptyTableRow({ colSpan, message, icon }: EmptyTableRowProps) {
    return (
        <tr>
            <td colSpan={colSpan} className="p-0">
                <EmptyState message={message} icon={icon} />
            </td>
        </tr>
    );
}
