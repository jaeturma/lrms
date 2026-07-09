import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

export function PageHeaderIcon({ icon: Icon, className }: { icon: LucideIcon; className?: string }) {
    return (
        <span className={cn('flex size-12 shrink-0 items-center justify-center rounded-xl shadow-sm', className)}>
            <Icon className="size-6" />
        </span>
    );
}
