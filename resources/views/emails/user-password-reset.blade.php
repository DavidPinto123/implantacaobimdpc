<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefina sua senha</title>
</head>
<body style="margin:0; padding:0; background:#f4f7fb; font-family: Arial, Helvetica, sans-serif; color:#1f2937;">
    <div style="max-width:640px; margin:0 auto; padding:32px 16px;">
        <div style="background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 10px 30px rgba(15,23,42,.08);">
            <div style="background:#111827; padding:28px 32px; text-align:center;">
                <img src="{{ asset('images/logo_dpc_dark.png') }}" alt="DPC" style="max-height:44px;">
            </div>

            <div style="padding:32px;">
                <h1 style="margin:0 0 16px; font-size:24px; color:#111827;">Olá{{ filled($name) ? ', '.$name : '' }}!</h1>

                <p style="margin:0 0 16px; line-height:1.7; font-size:15px;">
                    Recebemos uma solicitação para redefinir sua senha de acesso.
                </p>

                <div style="margin:28px 0; text-align:center;">
                    <a href="{{ $resetUrl }}" style="display:inline-block; padding:12px 22px; border-radius:10px; background:#fbbf24; color:#111827; text-decoration:none; font-weight:700;">
                        Redefinir senha
                    </a>
                </div>

                <p style="margin:0 0 14px; line-height:1.7; font-size:14px; color:#4b5563;">
                    Ao clicar, você será levado para criar uma nova senha pessoal.
                </p>

                <p style="margin:0; line-height:1.7; font-size:14px; color:#4b5563;">
                    Se você não solicitou isso, pode ignorar este e-mail com segurança.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
