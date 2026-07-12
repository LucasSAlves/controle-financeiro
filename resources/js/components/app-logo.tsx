export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-9 items-center justify-center rounded-md bg-white">
                <img
                    src="/images/logo-controle-financeiro.png"
                    alt="Controle Financeiro"
                    className="h-8 w-8 object-contain"
                />
            </div>

            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-none font-semibold">
                    Controle Financeiro
                </span>
            </div>
        </>
    );
}
