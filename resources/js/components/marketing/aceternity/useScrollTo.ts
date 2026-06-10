export function useScrollTo() {
    const scrollTo = (id: string) => {
        const elementId = id.startsWith('#') ? id.slice(1) : id;
        document.getElementById(elementId)?.scrollIntoView({ behavior: 'smooth' });
    };

    return scrollTo;
}
