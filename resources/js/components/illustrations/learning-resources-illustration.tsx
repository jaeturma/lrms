import type { SVGAttributes } from 'react';

export default function LearningResourcesIllustration(props: SVGAttributes<SVGSVGElement>) {
    return (
        <svg viewBox="0 0 420 300" fill="none" xmlns="http://www.w3.org/2000/svg" {...props}>
            <ellipse cx="205" cy="262" rx="150" ry="16" fill="currentColor" opacity="0.08" />

            {/* book stack */}
            <g>
                <rect x="42" y="206" width="130" height="20" rx="4" fill="currentColor" opacity="0.18" />
                <rect x="52" y="184" width="110" height="20" rx="4" fill="currentColor" opacity="0.28" />
                <rect x="38" y="162" width="118" height="20" rx="4" fill="#FBBF24" opacity="0.9" />
                <rect x="38" y="162" width="118" height="6" rx="3" fill="#FDE68A" opacity="0.9" />
            </g>

            {/* open book */}
            <g>
                <path
                    d="M60 152 L118 140 L118 76 C118 72 115 69 111 70 L60 80 Z"
                    fill="currentColor"
                    opacity="0.22"
                />
                <path
                    d="M176 152 L118 140 L118 76 C118 72 121 69 125 70 L176 80 Z"
                    fill="currentColor"
                    opacity="0.32"
                />
                <path d="M72 90 L106 84" stroke="currentColor" strokeOpacity="0.55" strokeWidth="2.5" strokeLinecap="round" />
                <path d="M72 100 L106 94" stroke="currentColor" strokeOpacity="0.55" strokeWidth="2.5" strokeLinecap="round" />
                <path d="M72 110 L100 105" stroke="currentColor" strokeOpacity="0.55" strokeWidth="2.5" strokeLinecap="round" />
                <path d="M130 84 L164 90" stroke="currentColor" strokeOpacity="0.4" strokeWidth="2.5" strokeLinecap="round" />
                <path d="M130 94 L164 100" stroke="currentColor" strokeOpacity="0.4" strokeWidth="2.5" strokeLinecap="round" />
                <path d="M130 105 L158 110" stroke="currentColor" strokeOpacity="0.4" strokeWidth="2.5" strokeLinecap="round" />
            </g>

            {/* monitoring dashboard card */}
            <g>
                <rect x="176" y="46" width="204" height="152" rx="16" fill="currentColor" opacity="0.12" />
                <rect x="176" y="46" width="204" height="152" rx="16" stroke="currentColor" strokeOpacity="0.35" strokeWidth="1.5" />
                <rect x="176" y="46" width="204" height="34" rx="16" fill="currentColor" opacity="0.2" />
                <circle cx="194" cy="63" r="4" fill="#FCA5A5" />
                <circle cx="208" cy="63" r="4" fill="#FDE68A" />
                <circle cx="222" cy="63" r="4" fill="#86EFAC" />

                {/* checklist rows */}
                {[104, 132, 160].map((y, i) => (
                    <g key={y}>
                        <rect x="196" y={y} width="164" height="20" rx="6" fill="currentColor" opacity="0.08" />
                        <circle cx="210" cy={y + 10} r="7" fill={i === 2 ? '#FDE68A' : '#86EFAC'} opacity="0.95" />
                        <path
                            d={i === 2 ? `M206 ${y + 10} h8` : `M206.5 ${y + 10} l3 3 l5.5 -6`}
                            stroke="#0f172a"
                            strokeWidth="1.6"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        />
                        <rect x="226" y={y + 6} width={i === 1 ? 90 : 116} height="8" rx="4" fill="currentColor" opacity="0.35" />
                    </g>
                ))}
            </g>

            {/* verified badge */}
            <g transform="translate(322, 8)">
                <circle cx="26" cy="26" r="26" fill="#FBBF24" />
                <path
                    d="M15 26 L22 33 L37 17"
                    stroke="#1E293B"
                    strokeWidth="3.5"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    fill="none"
                />
            </g>

            {/* floating accents */}
            <circle cx="30" cy="60" r="5" fill="currentColor" opacity="0.25" />
            <circle cx="392" cy="230" r="6" fill="currentColor" opacity="0.2" />
            <path d="M28 232 l8 0 M32 228 l0 8" stroke="currentColor" strokeOpacity="0.3" strokeWidth="2.5" strokeLinecap="round" />
        </svg>
    );
}
