import type { SVGAttributes } from 'react';

const TILES = [
    {
        x: 32,
        y: 88,
        color: '#F59E0B',
        title: 'Learning Resources',
        subtitle: 'Books & modules',
        icon: 'book' as const,
    },
    {
        x: 248,
        y: 88,
        color: '#3B82F6',
        title: 'ICT Equipment',
        subtitle: 'Computers & devices',
        icon: 'monitor' as const,
    },
    {
        x: 32,
        y: 244,
        color: '#10B981',
        title: 'Science & Math',
        subtitle: 'SME kits & tools',
        icon: 'flask' as const,
    },
    {
        x: 248,
        y: 244,
        color: '#A855F7',
        title: 'Digital Learning',
        subtitle: 'Media & materials',
        icon: 'play' as const,
    },
];

function TileIcon({ type }: { type: 'book' | 'monitor' | 'flask' | 'play' }) {
    switch (type) {
        case 'book':
            return (
                <g stroke="white" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" fill="none">
                    <path d="M-9 -7 V7 M9 -7 V7 M-9 -7 Q0 -3 9 -7 M-9 7 Q0 3 9 7" />
                    <path d="M-6 -3 H-2 M-6 1 H-2 M2 -3 H6 M2 1 H6" strokeWidth="1.3" opacity="0.85" />
                </g>
            );
        case 'monitor':
            return (
                <g stroke="white" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" fill="none">
                    <rect x="-9" y="-8" width="18" height="12" rx="1.5" />
                    <path d="M0 4 V8 M-5 9 H5" />
                </g>
            );
        case 'flask':
            return (
                <g stroke="white" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" fill="white" fillOpacity="0.12">
                    <path d="M-3 -8 V-3 L-8 6 Q-8 8 -6 8 H6 Q8 8 8 6 L3 -3 V-8" />
                    <path d="M-3 -8 H3" fill="none" />
                    <path d="M-6 3 H6" fill="none" strokeWidth="1.3" opacity="0.85" />
                    <circle cx="-1" cy="-1" r="0.9" fill="white" stroke="none" />
                    <circle cx="2" cy="1.5" r="0.9" fill="white" stroke="none" />
                </g>
            );
        case 'play':
            return (
                <g stroke="white" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" fill="none">
                    <rect x="-8" y="-9" width="16" height="18" rx="2.5" />
                    <path d="M-2.5 -4 V4 L4 0 Z" fill="white" stroke="none" />
                </g>
            );
    }
}

export default function HeroResourcesIllustration(props: SVGAttributes<SVGSVGElement>) {
    return (
        <svg viewBox="0 0 480 400" fill="none" xmlns="http://www.w3.org/2000/svg" {...props}>
            <ellipse cx="240" cy="392" rx="190" ry="14" fill="currentColor" opacity="0.08" />

            <rect x="8" y="8" width="464" height="384" rx="24" fill="currentColor" opacity="0.05" stroke="currentColor" strokeOpacity="0.18" />

            <rect x="8" y="8" width="464" height="56" rx="24" fill="currentColor" opacity="0.1" />
            <circle cx="32" cy="36" r="5" fill="#FCA5A5" />
            <circle cx="48" cy="36" r="5" fill="#FDE68A" />
            <circle cx="64" cy="36" r="5" fill="#86EFAC" />
            <rect x="90" y="31" width="150" height="10" rx="5" fill="currentColor" opacity="0.3" />

            <g>
                <rect x="392" y="22" width="62" height="26" rx="13" fill="#FBBF24" />
                <path d="M403 35 L408 40 L419 29" stroke="#1E293B" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" fill="none" />
                <text x="427" y="39" fontFamily="system-ui, sans-serif" fontSize="9" fontWeight="700" fill="#1E293B">
                    LIVE
                </text>
            </g>

            {TILES.map((tile) => (
                <g key={tile.title}>
                    <rect x={tile.x} y={tile.y} width="200" height="140" rx="16" fill="currentColor" opacity="0.055" />
                    <rect x={tile.x} y={tile.y} width="200" height="140" rx="16" stroke="currentColor" strokeOpacity="0.12" />
                    <circle cx={tile.x + 38} cy={tile.y + 38} r="22" fill={tile.color} />
                    <g transform={`translate(${tile.x + 38}, ${tile.y + 38})`}>
                        <TileIcon type={tile.icon} />
                    </g>
                    <text
                        x={tile.x + 20}
                        y={tile.y + 90}
                        fontFamily="system-ui, sans-serif"
                        fontSize="16"
                        fontWeight="700"
                        fill="currentColor"
                    >
                        {tile.title}
                    </text>
                    <text
                        x={tile.x + 20}
                        y={tile.y + 110}
                        fontFamily="system-ui, sans-serif"
                        fontSize="12"
                        fill="currentColor"
                        opacity="0.55"
                    >
                        {tile.subtitle}
                    </text>
                </g>
            ))}

            <circle cx="16" cy="80" r="4" fill="currentColor" opacity="0.2" />
            <circle cx="466" cy="360" r="5" fill="currentColor" opacity="0.18" />
            <path d="M-2 358 h8 M2 354 v8" stroke="currentColor" strokeOpacity="0.25" strokeWidth="2" strokeLinecap="round" transform="translate(2,0)" />
        </svg>
    );
}
