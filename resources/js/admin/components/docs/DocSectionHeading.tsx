type Props = {
    children: string;
    id?: string;
    className?: string;
};

/** Laravel-docs-style section title with accent rule */
export function DocSectionHeading({ children, id, className = '' }: Props) {
    return (
        <div className={`docs-section-heading ${className}`}>
            <h2 id={id} className="docs-h2 scroll-mt-24">
                {children}
            </h2>
        </div>
    );
}
