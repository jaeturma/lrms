import { useEffect, useState } from 'react';

function formatPstDateTime(now: Date): string {
    return new Intl.DateTimeFormat('en-US', {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true,
        timeZone: 'Asia/Manila',
    }).format(now);
}

export function PstDateTime() {
    const [now, setNow] = useState(() => new Date());

    useEffect(() => {
        const intervalId = window.setInterval(() => {
            setNow(new Date());
        }, 1000);

        return () => window.clearInterval(intervalId);
    }, []);

    return (
        <div className="text-right leading-tight">
            <p className="text-[11px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">
                PST
            </p>
            <p className="text-xs font-medium text-foreground md:text-sm">{formatPstDateTime(now)}</p>
        </div>
    );
}
