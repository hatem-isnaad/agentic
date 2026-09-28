import { motion } from 'framer-motion';
import type { ReactNode } from 'react';

type Props = {
    title: string;
    description?: string;
    actions?: ReactNode;
};

export function PageHeader({ title, description, actions }: Props) {
    return (
        <motion.header
            initial={{ opacity: 0, y: -4 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.28, ease: [0.22, 1, 0.36, 1] }}
            className="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <h1 className="text-[1.35rem] font-semibold tracking-tight text-slate-950 sm:text-[1.5rem]">{title}</h1>
                {description && <p className="mt-1 max-w-2xl text-[13px] leading-relaxed text-slate-500">{description}</p>}
            </div>
            {actions && <div className="flex shrink-0 flex-wrap gap-2">{actions}</div>}
        </motion.header>
    );
}
