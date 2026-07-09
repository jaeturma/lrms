import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

type Props = {
    label: string;
    value: number;
    context: string;
    icon: LucideIcon;
    colorClassName: string;
};

export function StatTile({ label, value, context, icon: Icon, colorClassName }: Props) {
    return (
        <article className={cn('rounded-xl p-3 shadow-sm', colorClassName)}>
            <div className="flex items-start justify-between gap-2">
                <p className="text-xs font-medium tracking-wide uppercase opacity-80">{label}</p>
                <Icon className="size-8 shrink-0 opacity-90" />
            </div>
            <p className="mt-1 text-2xl font-bold">{value.toLocaleString()}</p>
            <p className="mt-0.5 truncate text-xs opacity-75" title={context}>
                {context}
            </p>
        </article>
    );
}
