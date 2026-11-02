import { router } from '@inertiajs/react';
import { ImageUp, School, Trash2 } from 'lucide-react';
import { useRef, useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';

/**
 * The school's logo with upload / remove controls for the school account.
 */
export function SchoolLogoUploader({ logoUrl }: { logoUrl?: string | null }) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [uploading, setUploading] = useState(false);

    const upload = (file: File | undefined) => {
        if (!file) {
            return;
        }

        router.post(
            '/school/logo',
            { logo: file },
            {
                forceFormData: true,
                preserveScroll: true,
                onStart: () => setUploading(true),
                onFinish: () => {
                    setUploading(false);

                    if (inputRef.current) {
                        inputRef.current.value = '';
                    }
                },
                onSuccess: () => toast.success('School logo updated.'),
                onError: (errors) =>
                    toast.error(
                        errors.logo ?? 'The logo could not be uploaded.',
                    ),
            },
        );
    };

    const remove = () => {
        router.delete('/school/logo', {
            preserveScroll: true,
            onSuccess: () => toast.success('School logo removed.'),
        });
    };

    return (
        <div className="flex items-center gap-3">
            <div className="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-border bg-muted">
                {logoUrl ? (
                    <img
                        src={logoUrl}
                        alt="School logo"
                        className="size-full object-contain"
                    />
                ) : (
                    <School className="size-7 text-muted-foreground" />
                )}
            </div>
            <div className="flex flex-wrap gap-2">
                <input
                    ref={inputRef}
                    type="file"
                    accept="image/png,image/jpeg,image/webp"
                    className="hidden"
                    aria-label="Upload school logo"
                    onChange={(event) => upload(event.target.files?.[0])}
                />
                <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    disabled={uploading}
                    onClick={() => inputRef.current?.click()}
                >
                    <ImageUp className="mr-1 size-4" />
                    {uploading
                        ? 'Uploading...'
                        : logoUrl
                          ? 'Change logo'
                          : 'Upload logo'}
                </Button>
                {logoUrl && (
                    <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        onClick={remove}
                    >
                        <Trash2 className="mr-1 size-4" />
                        Remove
                    </Button>
                )}
            </div>
        </div>
    );
}
