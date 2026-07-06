export function GlobalFooter() {
    const year = new Date().getFullYear();

    return (
        <footer className="border-t border-border/70 bg-card/70 px-4 py-3 text-center text-xs text-muted-foreground md:px-6">
            <p>
                © {year} Learning Resource Management System. All rights reserved.
            </p>
        </footer>
    );
}
