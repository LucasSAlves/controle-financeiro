import { router } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { Toaster, toast } from 'sonner';


export type FlashMessages = {
    success?: string | null;
    error?: string | null;
    warning?: string | null;
    info?: string | null;
};

type GlobalToasterProps = {
    initialFlash?: FlashMessages;
};

function showFlashMessages(flash?: FlashMessages) {
    if (!flash) {
        return;
    }

    if (flash.success) {
        toast.success(flash.success);
    }

    if (flash.error) {
        toast.error(flash.error);
    }

    if (flash.warning) {
        toast.warning(flash.warning);
    }

    if (flash.info) {
        toast.info(flash.info);
    }
}

export function GlobalToaster({
    initialFlash,
}: GlobalToasterProps) {
    const initialFlashDisplayed = useRef(false);

    useEffect(() => {
        if (!initialFlashDisplayed.current) {
            showFlashMessages(initialFlash);
            initialFlashDisplayed.current = true;
        }

        const removeListener = router.on('success', (event) => {
            const flash = event.detail.page.props.flash as
                | FlashMessages
                | undefined;

            showFlashMessages(flash);
        });

        return removeListener;
    }, [initialFlash]);

    return (
        <Toaster
            position="top-right"
            richColors
            closeButton
            duration={4000}
            visibleToasts={4}
        />
    );
}
