export function EmptyChart({ message }: { message: string }) {
    return (
        <div className="flex h-40 items-center justify-center rounded-lg border border-dashed border-border">
            <p className="px-4 text-center text-sm text-muted-foreground">{message}</p>
        </div>
    );
}
