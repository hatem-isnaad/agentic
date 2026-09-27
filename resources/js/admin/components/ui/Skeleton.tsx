import { clsx } from 'clsx';

export function Skeleton({ className }: { className?: string }) {
    return <div className={clsx('ag-skeleton', className)} aria-hidden />;
}

export function TableSkeleton({ cols = 4, rows = 5 }: { cols?: number; rows?: number }) {
    return (
        <>
            {Array.from({ length: rows }).map((_, r) => (
                <tr key={r}>
                    {Array.from({ length: cols }).map((_, c) => (
                        <td key={c} className="px-6 py-4">
                            <Skeleton className="h-4 w-full max-w-[12rem]" />
                        </td>
                    ))}
                </tr>
            ))}
        </>
    );
}
