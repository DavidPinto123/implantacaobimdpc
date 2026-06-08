<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? $assunto ?? 'Gestão Smart' }}</title>
</head>

<body style="margin:0; padding:0; background:#f4f7fb; font-family: Arial, Helvetica, sans-serif; color:#1f2937;">
    <div style="max-width:640px; margin:0 auto; padding:32px 16px;">
        <div style="background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 10px 30px rgba(15,23,42,.08);">
            <div style="background:#111827; padding:28px 32px; text-align:center;">
                <img src="{{ asset('images/logo_dpc_dark.png') }}" alt="DPC" style="max-height:44px;">
            </div>

            <div style="padding:32px;">
                @yield('content')

                @hasSection('signature')
                    <div style="height:1px; background:#e5e7eb; margin:28px 0;"></div>

                    @yield('signature')
                @endif
            </div>
        </div>
    </div>
</body>

</html>
