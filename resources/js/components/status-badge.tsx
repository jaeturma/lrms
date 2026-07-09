import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export type StatusTone = 'success' | 'warning' | 'danger' | 'info' | 'neutral';

const toneClasses: Record<StatusTone, string> = {
    success: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    warning: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
    danger: 'bg-red-500/10 text-red-600 dark:text-red-400',
    info: 'bg-sky-500/10 text-sky-600 dark:text-sky-400',
    neutral: 'bg-muted text-muted-foreground',
};

type StatusBadgeProps = {
    tone: StatusTone;
    children: ReactNode;
    className?: string;
};

/**
 * Shared status pill. Callers map their own domain status (e.g. "pending",
 * "active") to one of the five tones below — this component only owns the
 * visual recipe, not any page's status vocabulary.
 */
export function StatusBadge({ tone, children, className }: StatusBadgeProps) {
    return (
        <span className={cn('inline-flex rounded-full px-2 py-0.5 text-xs font-medium', toneClasses[tone], className)}>
            {children}
        </span>
    );
}
