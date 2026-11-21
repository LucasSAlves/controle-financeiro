import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Plus } from 'lucide-react';

import { useId, useState } from 'react';

type Opcao = { id: number; nome: string };

type Props = {
    tipo: 'entrada' | 'despesa' | string;
    campo: 'categoria' | 'forma_pagamento';
    onCriado: (opcao: Opcao) => void;
};

export function CadastroRapido({ tipo, campo, onCriado }: Props) {
    const campoId = useId();
    const erroId = campoId + '-erro';
    const [aberto, setAberto] = useState(false);
    const [nome, setNome] = useState('');
    const [erro, setErro] = useState('');
    const [salvando, setSalvando] = useState(false);

    const categoria = campo === 'categoria';
    const titulo = categoria ? 'Nova categoria' : 'Nova forma de pagamento';
    const rotulo = categoria ? 'Nome da categoria' : 'Nome da forma de pagamento';
    const textoSalvar = categoria ? 'Salvar categoria' : 'Salvar forma de pagamento';
    const descricao = categoria
        ? 'Cadastre uma categoria de ' + tipo + ' para esta movimentação.'
        : 'Cadastre uma forma de pagamento para esta despesa.';

    async function cadastrar() {
        if (salvando) return;
        const nomeNormalizado = nome.trim();
        if (!nomeNormalizado) {
            setErro(categoria ? 'Informe o nome da categoria.' : 'Informe o nome da forma de pagamento.');
            return;
        }
        setSalvando(true);
        setErro('');

        try {
            const token = document.cookie
                .split('; ')
                .find((cookie) => cookie.startsWith('XSRF-TOKEN='))
                ?.slice('XSRF-TOKEN='.length);
            const resposta = await fetch(categoria ? '/categorias' : '/formas-pagamento', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': token ? decodeURIComponent(token) : '',
                },
                body: JSON.stringify(categoria ? { nome: nomeNormalizado, tipo } : { nome: nomeNormalizado }),
            });
            if (resposta.status === 422) {
                const resultado = await resposta.json();
                setErro(resultado.errors?.nome?.[0] ?? 'Confira os dados do cadastro.');
                return;
            }
            if (resposta.status !== 201) {
                setErro('Não foi possível concluir o cadastro. Confira se a sessão está ativa.');
                return;
            }
            onCriado(await resposta.json());
            setAberto(false);
        } catch {
            setErro('Não foi possível concluir o cadastro. Confira sua conexão.');
        } finally {
            setSalvando(false);
        }
    }

    return (
    <Dialog
        open={aberto}
        onOpenChange={(proximoAberto) => {
            if (salvando) return;

            setAberto(proximoAberto);
            setNome('');
            setErro('');
        }}
    >
        <DialogTrigger asChild>
            <Button type="button" variant="outline" size="sm">
                <Plus aria-hidden="true" />
                {titulo}
            </Button>
        </DialogTrigger>

        <DialogContent className="w-[calc(100%-2rem)] max-w-md rounded-xl">
            <DialogHeader>
                <DialogTitle>{titulo}</DialogTitle>
                <DialogDescription>
                    {descricao}
                </DialogDescription>
            </DialogHeader>

            <div className="space-y-2">
                <label htmlFor={campoId} className="block text-sm font-medium">
                    {rotulo}
                </label>
                <input
                    id={campoId}
                    type="text"
                    value={nome}
                    maxLength={255}
                    disabled={salvando}
                    aria-invalid={Boolean(erro)}
                    aria-describedby={erro ? erroId : undefined}
                    onChange={(event) => setNome(event.target.value)}
                    onKeyDown={(event) => {
                        if (event.key === 'Enter' && !event.nativeEvent.isComposing) {
                            event.preventDefault();
                            void cadastrar();
                        }
                    }}
                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                />
                {erro && (
                    <p id={erroId} role="alert" className="text-sm text-red-600">
                        {erro}
                    </p>
                )}
            </div>

            <DialogFooter className="gap-2 sm:space-x-0">
                <Button
                    type="button"
                    variant="outline"
                    disabled={salvando}
                    onClick={() => setAberto(false)}
                >
                    Cancelar
                </Button>
                <Button
                    type="button"
                    disabled={salvando}
                    onClick={() => void cadastrar()}
                >
                    {salvando ? 'Salvando…' : textoSalvar}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
    );
}
