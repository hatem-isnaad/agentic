import * as RadixSelect from '@radix-ui/react-select';
import { Check, ChevronDown, ChevronUp } from 'lucide-react';
import { clsx } from 'clsx';

export type SelectOption = { value: string; label: string };

type Props = {
    value: string;
    onValueChange: (value: string) => void;
    options: SelectOption[];
    placeholder?: string;
    id?: string;
    className?: string;
};

export function NativeSelect({ value, onValueChange, options, placeholder, id, className }: Props) {
    const selected = options.find((o) => o.value === value);

    return (
        <RadixSelect.Root value={value} onValueChange={onValueChange}>
            <RadixSelect.Trigger
                id={id}
                className={clsx(
                    'inline-flex h-11 w-full items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-800 shadow-sm outline-none transition hover:border-slate-300 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/15 data-[placeholder]:text-slate-400',
                    className,
                )}
                aria-label={placeholder}
            >
                <RadixSelect.Value placeholder={placeholder}>{selected?.label}</RadixSelect.Value>
                <RadixSelect.Icon>
                    <ChevronDown className="h-4 w-4 text-slate-400" />
                </RadixSelect.Icon>
            </RadixSelect.Trigger>
            <RadixSelect.Portal>
                <RadixSelect.Content
                    className="z-[200] overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-xl"
                    position="popper"
                    sideOffset={6}
                >
                    <RadixSelect.ScrollUpButton className="flex items-center justify-center py-1 text-slate-500">
                        <ChevronUp className="h-4 w-4" />
                    </RadixSelect.ScrollUpButton>
                    <RadixSelect.Viewport className="p-1 min-w-[var(--radix-select-trigger-width)]">
                        {options.map((opt) => (
                            <RadixSelect.Item
                                key={opt.value}
                                value={opt.value}
                                className="relative flex cursor-pointer select-none items-center rounded-lg py-2.5 pl-8 pr-3 text-sm font-medium text-slate-700 outline-none data-[highlighted]:bg-slate-100 data-[state=checked]:text-brand-700"
                            >
                                <RadixSelect.ItemIndicator className="absolute left-2 inline-flex items-center">
                                    <Check className="h-4 w-4 text-brand-600" />
                                </RadixSelect.ItemIndicator>
                                <RadixSelect.ItemText>{opt.label}</RadixSelect.ItemText>
                            </RadixSelect.Item>
                        ))}
                    </RadixSelect.Viewport>
                    <RadixSelect.ScrollDownButton className="flex items-center justify-center py-1 text-slate-500">
                        <ChevronDown className="h-4 w-4" />
                    </RadixSelect.ScrollDownButton>
                </RadixSelect.Content>
            </RadixSelect.Portal>
        </RadixSelect.Root>
    );
}
