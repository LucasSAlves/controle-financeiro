import * as Dialog from '@radix-ui/react-dialog';
import {
    AlertTriangle,
    Info,
    LoaderCircle,
    ShieldAlert,
    X,
} from 'lucide-react';

type ConfirmDialogVariant = 'danger' | 'warning' | 'info';

type ConfirmDialogProps = {
    open: boolean;
    title: string;
    description: string;
    confirmLabel?: string;
    cancelLabel?: string;
    variant?: ConfirmDialogVariant;
    processing?: boolean;
    onConfirm: () => void;
    onClose: () => void;
};

const variantConfig = {
    danger: {
        icon: ShieldAlert,
        iconClasses:
            'bg-red-100 text-red-600 dark:bg-red-950 dark:text-red-400',
        buttonClasses:
            'bg-red-600 text-white hover:bg-red-700 focus-visible:ring-red-500 dark:bg-red-600 dark:hover:bg-red-700',
    },
    warning: {
        icon: AlertTriangle,
        iconClasses:
            'bg-amber-100 text-amber-600 dark:bg-amber-950 dark:text-amber-400',
        buttonClasses:
            'bg-amber-600 text-white hover:bg-amber-700 focus-visible:ring-amber-500 dark:bg-amber-600 dark:hover:bg-amber-700',
    },
    info: {
        icon: Info,
        iconClasses:
            'bg-blue-100 text-blue-600 dark:bg-blue-950 dark:text-blue-400',
        buttonClasses:
            'bg-blue-600 text-white hover:bg-blue-700 focus-visible:ring-blue-500 dark:bg-blue-600 dark:hover:bg-blue-700',
    },
};

export function ConfirmDialog({
    open,
    title,
    description,
    confirmLabel = 'Confirmar',
    cancelLabel = 'Cancelar',
    variant = 'danger',
    processing = false,
    onConfirm,
    onClose,
}: ConfirmDialogProps) {
    const config = variantConfig[variant];
    const Icon = config.icon;

    function handleOpenChange(isOpen: boolean) {
        if (!isOpen && !processing) {
            onClose();
        }
    }

    return (
        <Dialog.Root open={open} onOpenChange={handleOpenChange}>
            <Dialog.Portal>
                <Dialog.Overlay className="fixed inset-0 z-50 bg-black/50 backdrop-blur-[2px] data-[state=closed]:animate-out data-[state=closed]:fade-out data-[state=open]:animate-in data-[state=open]:fade-in" />

                <Dialog.Content className="fixed left-1/2 top-1/2 z-50 w-[calc(100%-2rem)] max-w-md -translate-x-1/2 -translate-y-1/2 rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl outline-none data-[state=closed]:animate-out data-[state=closed]:fade-out data-[state=closed]:zoom-out-95 data-[state=open]:animate-in data-[state=open]:fade-in data-[state=open]:zoom-in-95 dark:border-gray-700 dark:bg-gray-900">
                    <div className="flex items-start gap-4">
                        <div
                            className={`flex h-12 w-12 shrink-0 items-center justify-center rounded-full ${config.iconClasses}`}
                        >
                            <Icon className="h-6 w-6" />
                        </div>

                        <div className="min-w-0 flex-1">
                            <Dialog.Title className="pr-8 text-lg font-bold text-gray-900 dark:text-white">
                                {title}
                            </Dialog.Title>

                            <Dialog.Description className="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-400">
                                {description}
                            </Dialog.Description>
                        </div>
                    </div>

                    <Dialog.Close asChild>
                        <button
                            type="button"
                            disabled={processing}
                            aria-label="Fechar"
                            className="absolute right-4 top-4 inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-50 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </Dialog.Close>

                    <div className="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            onClick={onClose}
                            disabled={processing}
                            className="inline-flex cursor-pointer items-center justify-center rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                        >
                            {cancelLabel}
                        </button>

                        <button
                            type="button"
                            onClick={onConfirm}
                            disabled={processing}
                            className={`inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 ${config.buttonClasses}`}
                        >
                            {processing && (
                                <LoaderCircle className="h-4 w-4 animate-spin" />
                            )}

                            {processing ? 'Processando...' : confirmLabel}
                        </button>
                    </div>
                </Dialog.Content>
            </Dialog.Portal>
        </Dialog.Root>
    );
}
