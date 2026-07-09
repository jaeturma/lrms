import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { PageHeaderIcon } from '@/components/page-header-icon';
import { cn } from '@/lib/utils';

type PageHeaderProps = {
    icon: LucideIcon;
    iconClassName: string;
    title: string;
    description?: ReactNode;
    actions?: ReactNode;
    children?: ReactNode;
    align?: 'center' | 'start';
    className?: string;
};

/**
 * Standard page header: icon badge + title + description, with an optional
 * right-aligned actions slot and an optional children slot for extra content
 * under the description (e.g. summary chips).
 */
export function PageHeader({
    icon,
    iconClassName,
    title,
    description,
    actions,
    children,
    align = 'center',
    className,
}: PageHeaderProps) {
    return (
        <header
            className={cn(
                'flex flex-wrap gap-4 rounded-2xl border border-border bg-card p-5 shadow-sm',
                align === 'start' ? 'items-start' : 'items-center',
                className,
            )}
        >
            <PageHeaderIcon icon={icon} className={iconClassName} />
            <div className="min-w-0 flex-1">
                <h1 className="text-2xl font-bold text-foreground">{title}</h1>
                {description && <p className="text-sm text-muted-foreground">{description}</p>}
                {children}
            </div>
            {actions && <div className="flex flex-wrap items-center gap-2">{actions}</div>}
        </header>
    );
}
