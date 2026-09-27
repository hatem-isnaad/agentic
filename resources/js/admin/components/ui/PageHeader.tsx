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
            initial={{ opacity: 0, y: -6 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.35, ease: [0.22, 1, 0.36, 1] }}
            className="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <h1 className="text-3xl font-bold tracking-tight text-slate-900">{title}</h1>
                {description && <p className="mt-2 max-w-2xl text-base text-slate-500">{description}</p>}
            </div>
            {actions && <div className="flex shrink-0 flex-wrap gap-2">{actions}</div>}
        </motion.header>
    );
}
