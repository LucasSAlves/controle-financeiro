<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Redefinição de senha</title>
</head>

<body style="
    margin: 0;
    padding: 0;
    background-color: #f3f4f6;
    font-family: Arial, Helvetica, sans-serif;
    color: #111827;
">
    <table
        role="presentation"
        width="100%"
        cellspacing="0"
        cellpadding="0"
        border="0"
        style="background-color: #f3f4f6;"
    >
        <tr>
            <td align="center" style="padding: 32px 16px;">
                <table
                    role="presentation"
                    width="100%"
                    cellspacing="0"
                    cellpadding="0"
                    border="0"
                    style="
                        max-width: 620px;
                        background-color: #ffffff;
                        border-radius: 20px;
                        overflow: hidden;
                        box-shadow: 0 4px 18px rgba(15, 23, 42, 0.08);
                    "
                >
                    <tr>
                        <td
                            align="center"
                            style="
                                padding: 32px 24px;
                                background: linear-gradient(135deg, #1d4ed8, #2563eb, #10b981);
                                color: #ffffff;
                            "
                        >
                            <div
                                style="
                                    display: inline-block;
                                    width: 58px;
                                    height: 58px;
                                    line-height: 58px;
                                    border-radius: 18px;
                                    background-color: rgba(255, 255, 255, 0.18);
                                    font-size: 22px;
                                    font-weight: bold;
                                "
                            >
                                CF
                            </div>

                            <h1
                                style="
                                    margin: 18px 0 0;
                                    font-size: 26px;
                                    line-height: 34px;
                                "
                            >
                                Redefinição de senha
                            </h1>

                            <p
                                style="
                                    margin: 10px 0 0;
                                    color: #dbeafe;
                                    font-size: 15px;
                                    line-height: 24px;
                                "
                            >
                                Controle Financeiro
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 36px 34px;">
                            <h2
                                style="
                                    margin: 0 0 18px;
                                    font-size: 22px;
                                    line-height: 30px;
                                    color: #111827;
                                "
                            >
                                Olá, {{ $user->name }}!
                            </h2>

                            <p
                                style="
                                    margin: 0 0 16px;
                                    font-size: 16px;
                                    line-height: 26px;
                                    color: #374151;
                                "
                            >
                                Recebemos uma solicitação para redefinir a senha
                                da sua conta.
                            </p>

                            <p
                                style="
                                    margin: 0 0 26px;
                                    font-size: 16px;
                                    line-height: 26px;
                                    color: #374151;
                                "
                            >
                                Clique no botão abaixo para criar uma nova senha:
                            </p>

                            <table
                                role="presentation"
                                cellspacing="0"
                                cellpadding="0"
                                border="0"
                                align="center"
                                style="margin: 0 auto 28px;"
                            >
                                <tr>
                                    <td
                                        align="center"
                                        bgcolor="#2563eb"
                                        style="border-radius: 12px;"
                                    >
                                        <a
                                            href="{{ $url }}"
                                            target="_blank"
                                            style="
                                                display: inline-block;
                                                padding: 15px 28px;
                                                color: #ffffff;
                                                font-size: 16px;
                                                font-weight: bold;
                                                text-decoration: none;
                                                border-radius: 12px;
                                            "
                                        >
                                            Redefinir minha senha
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <div
                                style="
                                    margin-bottom: 24px;
                                    padding: 16px 18px;
                                    border: 1px solid #bfdbfe;
                                    border-radius: 12px;
                                    background-color: #eff6ff;
                                    color: #1d4ed8;
                                    font-size: 14px;
                                    line-height: 23px;
                                "
                            >
                                Este link de recuperação expira em
                                <strong>{{ $tempoExpiracao }} minutos</strong>.
                            </div>

                            <p
                                style="
                                    margin: 0 0 18px;
                                    font-size: 15px;
                                    line-height: 25px;
                                    color: #4b5563;
                                "
                            >
                                Se você não solicitou a redefinição da senha,
                                ignore este e-mail. Sua conta continuará protegida.
                            </p>

                            <p
                                style="
                                    margin: 0;
                                    font-size: 15px;
                                    line-height: 25px;
                                    color: #4b5563;
                                "
                            >
                                Atenciosamente,<br>
                                <strong>Equipe Controle Financeiro</strong>
                            </p>

                            <div
                                style="
                                    margin-top: 30px;
                                    padding-top: 22px;
                                    border-top: 1px solid #e5e7eb;
                                "
                            >
                                <p
                                    style="
                                        margin: 0 0 10px;
                                        font-size: 13px;
                                        line-height: 21px;
                                        color: #6b7280;
                                    "
                                >
                                    Caso o botão não funcione, copie e cole o
                                    endereço abaixo no seu navegador:
                                </p>

                                <p
                                    style="
                                        margin: 0;
                                        word-break: break-all;
                                        font-size: 12px;
                                        line-height: 20px;
                                    "
                                >
                                    <a
                                        href="{{ $url }}"
                                        style="color: #2563eb;"
                                    >
                                        {{ $url }}
                                    </a>
                                </p>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td
                            align="center"
                            style="
                                padding: 20px 24px;
                                background-color: #f9fafb;
                                border-top: 1px solid #e5e7eb;
                                color: #6b7280;
                                font-size: 12px;
                                line-height: 20px;
                            "
                        >
                            Controle Financeiro Lucas Silva © {{ date('Y') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
