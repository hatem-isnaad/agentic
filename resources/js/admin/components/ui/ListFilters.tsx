import { ListFilter, Search, X } from 'lucide-react';
import { NativeSelect, type SelectOption } from './NativeSelect';
import { Input } from './Input';
import { ALL_FILTER } from '../../lib/listFilters';

export type ListFilterControl = {
    id: string;
    label: string;
    value: string;
    onChange: (value: string) => void;
    options: SelectOption[];
};

type Props = {
    search: string;
    onSearch: (value: string) => void;
    searchPlaceholder?: string;
    filters?: ListFilterControl[];
    resultCount: number;
    totalCount: number;
    countLabel: string;
    allLabel: string;
    clearLabel: string;
};

export function ListFilters({
    search,
    onSearch,
    searchPlaceholder,
    filters = [],
    resultCount,
    totalCount,
    countLabel,
    allLabel,
    clearLabel,
}: Props) {
    const active = search.trim() !== '' || filters.some((filter) => filter.value !== ALL_FILTER);

    const clear = () => {
        onSearch('');
        filters.forEach((filter) => filter.onChange(ALL_FILTER));
    };

    return (
        <div className="space-y-3">
            <div className="flex flex-col gap-3 lg:flex-row lg:items-center">
                <div className="relative min-w-0 flex-1">
                    <Search className="pointer-events-none absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <Input
                        className="h-10 ps-10"
                        placeholder={searchPlaceholder}
                        value={search}
                        onChange={(e) => onSearch(e.target.value)}
                        aria-label={searchPlaceholder}
                    />
                </div>
                {filters.length > 0 && (
                    <div className="flex min-w-0 flex-wrap items-center gap-2">
                        <span className="hidden items-center gap-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400 sm:inline-flex">
                            <ListFilter className="h-3.5 w-3.5" />
                        </span>
                        {filters.map((filter) => (
                            <NativeSelect
                                key={filter.id}
                                value={filter.value}
                                onValueChange={filter.onChange}
                                placeholder={filter.label}
                                className="h-10 w-[9.75rem] min-w-[9.75rem]"
                                options={[{ value: ALL_FILTER, label: `${filter.label}: ${allLabel}` }, ...filter.options]}
                            />
                        ))}
                    </div>
                )}
                <div className="flex shrink-0 items-center justify-between gap-3 lg:justify-end">
                    <p className="text-xs tabular-nums text-slate-400">{countLabel}</p>
                    {active && (
                        <button
                            type="button"
                            onClick={clear}
                            className="inline-flex h-8 items-center gap-1 rounded-lg px-2 text-xs font-semibold text-slate-500 hover:bg-slate-100 hover:text-slate-900"
                        >
                            <X className="h-3.5 w-3.5" />
                            {clearLabel}
                        </button>
                    )}
                </div>
            </div>
            {active && resultCount !== totalCount && (
                <p className="text-[11px] text-slate-400">
                    {resultCount}/{totalCount}
                </p>
            )}
        </div>
    );
}
