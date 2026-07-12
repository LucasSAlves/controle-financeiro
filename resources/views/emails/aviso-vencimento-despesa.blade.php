@php
    $total = $despesas->sum(fn ($despesa) => (float) $despesa->valor);
    $dataHoje = now(config('app.timezone'))->format('d/m/Y');
    $urlSistema = config('app.url') . '/dashboard';
@endphp

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Aviso de vencimento de despesa</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: Arial, Helvetica, sans-serif; color: #111827;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6; padding: 24px 0;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 680px; background-color: #ffffff; border-radius: 14px; overflow: hidden; border: 1px solid #e5e7eb;">

                    <tr>
                        <td style="background: linear-gradient(135deg, #2563eb, #16a34a); padding: 28px 32px; color: #ffffff;">
                            <h1 style="margin: 0; font-size: 24px; line-height: 1.3;">
                                Controle Financeiro
                            </h1>

                            <p style="margin: 8px 0 0; font-size: 15px;">
                                Aviso de vencimento de despesa
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 30px 32px;">
                            <h2 style="margin: 0 0 12px; font-size: 22px; color: #111827;">
                                Olá, {{ $user->name }}!
                            </h2>

                            <p style="margin: 0 0 18px; font-size: 16px; line-height: 1.6; color: #374151;">
                                Você tem despesa(s) pendente(s) vencendo hoje,
                                <strong>{{ $dataHoje }}</strong>.
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 20px 0;">
                                <tr>
                                    <td style="background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 18px;">
                                        <p style="margin: 0; font-size: 14px; color: #1e40af;">
                                            Quantidade de despesas
                                        </p>

                                        <p style="margin: 6px 0 0; font-size: 24px; font-weight: bold; color: #1d4ed8;">
                                            {{ $despesas->count() }}
                                        </p>
                                    </td>

                                    <td width="16"></td>

                                    <td style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 18px;">
                                        <p style="margin: 0; font-size: 14px; color: #166534;">
                                            Total vencendo hoje
                                        </p>

                                        <p style="margin: 6px 0 0; font-size: 24px; font-weight: bold; color: #15803d;">
                                            R$ {{ number_format($total, 2, ',', '.') }}
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; margin-top: 22px; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden;">
                                <thead>
                                    <tr style="background-color: #f9fafb;">
                                        <th align="left" style="padding: 12px; font-size: 13px; color: #374151; border-bottom: 1px solid #e5e7eb;">
                                            Descrição
                                        </th>
                                        <th align="left" style="padding: 12px; font-size: 13px; color: #374151; border-bottom: 1px solid #e5e7eb;">
                                            Valor
                                        </th>
                                        <th align="left" style="padding: 12px; font-size: 13px; color: #374151; border-bottom: 1px solid #e5e7eb;">
                                            Vencimento
                                        </th>
                                        <th align="left" style="padding: 12px; font-size: 13px; color: #374151; border-bottom: 1px solid #e5e7eb;">
                                            Categoria
                                        </th>
                                        <th align="left" style="padding: 12px; font-size: 13px; color: #374151; border-bottom: 1px solid #e5e7eb;">
                                            Parcela
                                        </th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($despesas as $despesa)
                                        <tr>
                                            <td style="padding: 12px; font-size: 14px; color: #111827; border-bottom: 1px solid #f3f4f6;">
                                                <strong>{{ $despesa->descricao }}</strong>

                                                @if ($despesa->forma_pagamento)
                                                    <br>
                                                    <span style="font-size: 12px; color: #6b7280;">
                                                        {{ $despesa->forma_pagamento }}
                                                    </span>
                                                @endif
                                            </td>

                                            <td style="padding: 12px; font-size: 14px; color: #dc2626; font-weight: bold; border-bottom: 1px solid #f3f4f6;">
                                                R$ {{ number_format((float) $despesa->valor, 2, ',', '.') }}
                                            </td>

                                            <td style="padding: 12px; font-size: 14px; color: #111827; border-bottom: 1px solid #f3f4f6;">
                                                {{ $despesa->data?->format('d/m/Y') }}
                                            </td>

                                            <td style="padding: 12px; font-size: 14px; color: #111827; border-bottom: 1px solid #f3f4f6;">
                                                {{ $despesa->categoria ?? '-' }}
                                            </td>

                                            <td style="padding: 12px; font-size: 14px; color: #111827; border-bottom: 1px solid #f3f4f6;">
                                                @if ($despesa->parcelado)
                                                    {{ $despesa->parcela_atual }}/{{ $despesa->total_parcelas }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <div style="margin-top: 28px; text-align: center;">
                                <a href="{{ $urlSistema }}" style="display: inline-block; background-color: #2563eb; color: #ffffff; text-decoration: none; padding: 13px 22px; border-radius: 10px; font-size: 15px; font-weight: bold;">
                                    Acessar sistema
                                </a>
                            </div>

                            <p style="margin: 28px 0 0; font-size: 14px; line-height: 1.6; color: #4b5563;">
                                Acesse seu sistema de controle financeiro para conferir a despesa ou marcar como paga.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color: #f9fafb; padding: 20px 32px; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0; font-size: 13px; color: #6b7280; line-height: 1.5;">
                                Este é um aviso automático do seu sistema de Controle Financeiro.
                                <br>
                                Caso a despesa já tenha sido paga, acesse o sistema e marque como paga.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
