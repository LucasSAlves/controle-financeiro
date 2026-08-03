import { useCallback } from 'react';

export function useMobileNavigation() {
    const cleanup = useCallback(() => {
        if (typeof document !== 'undefined') {
            document.body.style.removeProperty('pointer-events');
        }

        if (typeof window !== 'undefined') {
            window.dispatchEvent(new Event('mobile-navigation'));
        }
    }, []);

    return cleanup;
}
