export function resolveBrandingImageUrl(value?: string | null): string | null {
    if (!value) {
        return null;
    }

    const trimmed = value.trim();

    if (trimmed === '') {
        return null;
    }

    if (trimmed.startsWith('/storage/')) {
        return trimmed;
    }

    if (trimmed.startsWith('storage/')) {
        return `/${trimmed}`;
    }

    if (trimmed.startsWith('/branding/')) {
        return `/storage${trimmed}`;
    }

    if (trimmed.startsWith('branding/')) {
        return `/storage/${trimmed}`;
    }

    if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) {
        try {
            const parsed = new URL(trimmed);

            if (parsed.pathname.startsWith('/storage/')) {
                return parsed.pathname;
            }

            return trimmed;
        } catch {
            return trimmed;
        }
    }

    return trimmed;
}
