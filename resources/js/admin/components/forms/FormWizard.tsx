import { AnimatePresence, motion } from 'framer-motion';
import { Check, ChevronLeft, ChevronRight } from 'lucide-react';
import { useEffect, useState, type ReactNode } from 'react';
import { useI18n } from '../../lib/i18n';
import { Button } from '../ui/Button';

export type WizardStep = {
    id: string;
    title: string;
    description?: string;
};

type Props = {
    steps: WizardStep[];
    step: number;
    onStepChange: (index: number) => void;
    onSubmit: () => void;
    canNext?: boolean;
    submitting?: boolean;
    saveLabel?: string;
    children: ReactNode;
};

const ease = [0.22, 1, 0.36, 1] as const;

export function FormWizard({ steps, step, onStepChange, onSubmit, canNext = true, submitting = false, saveLabel, children }: Props) {
    const { t, direction } = useI18n();
    const [dir, setDir] = useState(1);
    const [maxReached, setMaxReached] = useState(step);
    const last = step === steps.length - 1;
    const rtl = direction === 'rtl';
    const current = steps[step];
    const slide = (rtl ? -1 : 1) * dir;

    useEffect(() => {
        setMaxReached((prev) => Math.max(prev, step));
    }, [step]);

    const go = (next: number) => {
        if (next < 0 || next >= steps.length || next === step) return;
        const forward = next > step;
        if (forward && next > maxReached && !canNext) return;
        setDir(forward ? 1 : -1);
        onStepChange(next);
    };

    return (
        <div className="ag-wizard">
            <aside className="ag-wizard-rail">
                <ol className="relative flex gap-2 lg:flex-col lg:gap-0">
                    {steps.length > 1 && (
                        <div className="pointer-events-none absolute inset-x-8 top-[15px] hidden h-px bg-slate-200/90 lg:inset-x-auto lg:start-[15px] lg:top-4 lg:bottom-4 lg:block lg:h-auto lg:w-px">
                            <motion.div
                                className="origin-top bg-slate-950 lg:w-px"
                                initial={false}
                                animate={{
                                    height: steps.length === 1 ? '0%' : `${(step / (steps.length - 1)) * 100}%`,
                                }}
                                transition={{ type: 'spring', stiffness: 280, damping: 32 }}
                            />
                        </div>
                    )}
                    {steps.map((item, index) => {
                        const done = index < step;
                        const active = index === step;
                        const reachable = index <= maxReached || (index === step + 1 && canNext);
                        return (
                            <li key={item.id} className="relative min-w-0 flex-1 lg:flex-none">
                                <button
                                    type="button"
                                    disabled={!reachable}
                                    onClick={() => go(index)}
                                    className="group relative flex w-full items-start gap-3 rounded-2xl px-1 py-2 text-start disabled:cursor-not-allowed lg:px-2 lg:py-2.5"
                                >
                                    {active && (
                                        <motion.span
                                            layoutId="wizard-rail-active"
                                            className="absolute inset-0 hidden rounded-2xl bg-white shadow-[0_0_0_1px_rgba(15,23,42,0.06),0_8px_24px_rgba(15,23,42,0.06)] lg:block"
                                            transition={{ type: 'spring', stiffness: 380, damping: 34 }}
                                        />
                                    )}
                                    <span
                                        className={`relative z-10 mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-[12px] font-semibold transition ${
                                            done || active
                                                ? 'bg-slate-950 text-white shadow-[0_0_0_4px_rgba(15,23,42,0.06)]'
                                                : 'border border-slate-200 bg-white text-slate-400'
                                        }`}
                                    >
                                        {done ? <Check className="h-3.5 w-3.5" strokeWidth={2.5} /> : index + 1}
                                    </span>
                                    <span className="relative z-10 min-w-0 pt-0.5">
                                        <span
                                            className={`block truncate text-[13px] font-semibold tracking-tight ${
                                                active ? 'text-slate-950' : done ? 'text-slate-700' : 'text-slate-400'
                                            }`}
                                        >
                                            {item.title}
                                        </span>
                                        {item.description && (
                                            <span className="mt-0.5 hidden text-[12px] leading-snug text-slate-400 lg:line-clamp-2 lg:block">
                                                {item.description}
                                            </span>
                                        )}
                                    </span>
                                </button>
                            </li>
                        );
                    })}
                </ol>
            </aside>

            <section className="ag-wizard-panel">
                <div className="ag-wizard-progress">
                    <motion.div
                        className="h-full bg-slate-950"
                        initial={false}
                        animate={{ width: `${((step + 1) / steps.length) * 100}%` }}
                        transition={{ type: 'spring', stiffness: 260, damping: 30 }}
                    />
                </div>

                <header className="border-b border-slate-100 px-5 pb-5 pt-6 sm:px-8 sm:pt-8">
                    <p className="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">
                        {t('wizard.step_of', { current: String(step + 1), total: String(steps.length) })}
                    </p>
                    <AnimatePresence mode="wait">
                        <motion.div
                            key={current?.id ?? step}
                            initial={{ opacity: 0, y: 6 }}
                            animate={{ opacity: 1, y: 0 }}
                            exit={{ opacity: 0, y: -4 }}
                            transition={{ duration: 0.22, ease }}
                        >
                            <h2 className="mt-2 text-[1.45rem] font-semibold tracking-tight text-slate-950">{current?.title}</h2>
                            {current?.description && <p className="mt-1.5 max-w-xl text-sm leading-relaxed text-slate-500">{current.description}</p>}
                        </motion.div>
                    </AnimatePresence>
                </header>

                <div className="min-h-[22rem] overflow-hidden px-5 py-6 sm:px-8 sm:py-8">
                    <AnimatePresence mode="wait" custom={dir}>
                        <motion.div
                            key={current?.id ?? step}
                            custom={dir}
                            initial={{ opacity: 0, x: slide * 24 }}
                            animate={{ opacity: 1, x: 0 }}
                            exit={{ opacity: 0, x: slide * -16 }}
                            transition={{ duration: 0.3, ease }}
                        >
                            {children}
                        </motion.div>
                    </AnimatePresence>
                </div>

                <footer className="flex items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/70 px-5 py-4 sm:px-8">
                    <Button type="button" variant="ghost" className="px-2 text-slate-500" disabled={step === 0} onClick={() => go(step - 1)}>
                        {rtl ? <ChevronRight className="h-4 w-4" /> : <ChevronLeft className="h-4 w-4" />}
                        {t('actions.back')}
                    </Button>
                    {last ? (
                        <Button type="button" className="min-w-[8.5rem]" disabled={submitting} onClick={onSubmit}>
                            {saveLabel ?? t('actions.save')}
                        </Button>
                    ) : (
                        <Button type="button" className="min-w-[8.5rem]" disabled={!canNext} onClick={() => go(step + 1)}>
                            {t('actions.next')}
                            {rtl ? <ChevronLeft className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
                        </Button>
                    )}
                </footer>
            </section>
        </div>
    );
}
