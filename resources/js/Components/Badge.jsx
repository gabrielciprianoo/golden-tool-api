export default function Badge({ children, variant = 'default' }) {
    const variants = {
        default: 'bg-gray-100 text-gray-800',
        montage: 'bg-green-100 text-green-800',
        assembly: 'bg-purple-100 text-purple-800',
    };

    const variantClass = children?.toLowerCase().includes('montaje')
        ? variants.montage
        : children?.toLowerCase().includes('armado')
            ? variants.assembly
            : variants.default;

    return (
        <span className={`px-2 py-1 text-xs font-semibold rounded-full ${variantClass}`}>
            {children}
        </span>
    );
}
