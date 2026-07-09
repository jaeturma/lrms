import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginationProps = {
    links: PaginationLink[];
    className?: string;
};

/**
 * Renders a Laravel paginator's `links` array consistently. Mirrors the
 * pagination markup already used across catalog/report pages, centralized
 * so future adjustments only need to happen once.
 */
export function Pagination({ links, className }: PaginationProps) {
    if (links.length <= 3) {
        return null;
    }

    return (
        <div className={cn('flex flex-wrap gap-2 text-sm', className)}>
            {links.map((link, index) =>
                link.url ? (
                    <Link
                        key={index}
                        href={link.url}
                        className={cn(
                            'rounded border px-3 py-1',
                            link.active
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'border-border bg-card text-foreground',
                        )}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ) : (
                    <span
                        key={index}
                        className="rounded border border-border bg-card px-3 py-1 text-muted-foreground"
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ),
            )}
        </div>
    );
}
