import type { LucideIcon } from 'lucide-react';
import { Monitor, Moon, Sun } from 'lucide-react';
import type { HTMLAttributes } from 'react';
import type { Appearance, ColorTheme } from '@/hooks/use-appearance';
import { useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';

export default function AppearanceToggleTab({
    className = '',
    ...props
}: HTMLAttributes<HTMLDivElement>) {
    const { appearance, colorTheme, updateAppearance, updateColorTheme } =
        useAppearance();

    const tabs: { value: Appearance; icon: LucideIcon; label: string }[] = [
        { value: 'light', icon: Sun, label: 'Light' },
        { value: 'dark', icon: Moon, label: 'Dark' },
        { value: 'system', icon: Monitor, label: 'System' },
    ];

    const themes: {
        value: ColorTheme;
        label: string;
        previewClassName: string;
    }[] = [
        {
            value: 'slate',
            label: 'Slate',
            previewClassName: 'bg-[linear-gradient(135deg,#64748b,#334155)]',
        },
        {
            value: 'ocean',
            label: 'Ocean',
            previewClassName: 'bg-[linear-gradient(135deg,#0ea5e9,#155e75)]',
        },
        {
            value: 'forest',
            label: 'Forest',
            previewClassName: 'bg-[linear-gradient(135deg,#22c55e,#166534)]',
        },
        {
            value: 'sunset',
            label: 'Sunset',
            previewClassName: 'bg-[linear-gradient(135deg,#fb923c,#b91c1c)]',
        },
        {
            value: 'rose',
            label: 'Rose',
            previewClassName: 'bg-[linear-gradient(135deg,#fb7185,#be185d)]',
        },
    ];

    return (
        <div className={cn('space-y-6', className)} {...props}>
            <section className="space-y-3">
                <p className="text-sm font-medium">Mode</p>

                <div className="inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800">
                    {tabs.map(({ value, icon: Icon, label }) => (
                        <button
                            key={value}
                            onClick={() => updateAppearance(value)}
                            className={cn(
                                'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
                                appearance === value
                                    ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                                    : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
                            )}
                        >
                            <Icon className="-ml-1 h-4 w-4" />
                            <span className="ml-1.5 text-sm">{label}</span>
                        </button>
                    ))}
                </div>
            </section>

            <section className="space-y-3">
                <p className="text-sm font-medium">Color theme</p>

                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    {themes.map((theme) => (
                        <button
                            key={theme.value}
                            type="button"
                            onClick={() => updateColorTheme(theme.value)}
                            className={cn(
                                'overflow-hidden rounded-xl border p-0 text-left transition-colors',
                                colorTheme === theme.value
                                    ? 'border-primary ring-2 ring-primary/20'
                                    : 'border-border hover:border-primary/40 hover:shadow-sm',
                            )}
                        >
                            <span className={cn('block h-16 w-full', theme.previewClassName)} />
                            <span className="flex items-center justify-between px-3 py-2 text-xs font-semibold">
                                <span>{theme.label}</span>
                                <span className="text-muted-foreground">
                                    {colorTheme === theme.value ? 'Selected' : 'Preview'}
                                </span>
                            </span>
                        </button>
                    ))}
                </div>
            </section>
        </div>
    );
}
