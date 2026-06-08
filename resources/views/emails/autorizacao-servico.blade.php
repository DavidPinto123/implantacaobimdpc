@extends('emails.layout', ['title' => 'Autorização de Serviço'])

@section('content')
    <div style="font-size:15px; line-height:1.7; color:#1f2937;">
        <p style="margin:0 0 16px;">
            Prezados,
        </p>

        <p style="margin:0 0 16px;">
            Segue anexa AS.
        </p>

        <div style="margin:20px 0; padding:16px; border-radius:12px; background:#fef3c7; border:1px solid #f59e0b; color:#92400e;">
            <p style="margin:0 0 12px; font-weight:700;">
                Emissão de notas: 1° a 18° dia de cada mês (mão de obra e material)
            </p>

            <p style="margin:0; font-weight:700;">
                TODO E QUALQUER FATURAMENTO SÓ DEVERÁ ACONTECER APÓS AUTORIZAÇÃO DO GESTOR DPC OU DA GERENCIADORA CONTRATADA.
            </p>
        </div>

        <p style="margin:0 0 16px;">
            Por favor, confirmar a data de entrada / entrega do material na obra juntamente com o gestor.
            Os contatos do gestor da obra estão no anexo.
        </p>

        @if($emailGestor)
            <div style="margin:20px 0; padding:16px; border-radius:12px; background:#fee2e2; border:1px solid #ef4444; color:#991b1b;">
                <p style="margin:0 0 10px; font-weight:700; text-transform:uppercase;">
                    AS NF’S DEVEM SER ENVIADAS OBRIGATORIAMENTE POR EMAIL PARA O E-MAIL ABAIXO:
                </p>

                <a href="mailto:{{ $emailGestor }}" style="color:#1d4ed8; font-weight:700; text-decoration:none;">
                    {{ $emailGestor }}
                </a>
                <span style="color:#4b5563;">(Gestor)</span>
            </div>
        @endif

        <p style="margin:0 0 16px;">
            É imprescindível a leitura da autorização de serviço.
        </p>

        <p style="margin:0;">
            Qualquer dúvida, entrar em contato.
        </p>
    </div>
@endsection

@section('signature')
    <table cellpadding="0" cellspacing="0" border="0" style="width:100%;">
        <tr>
            <td style="font-family:Arial, sans-serif; font-size:13px; line-height:1.6; color:#374151;">
                <strong style="display:block; font-size:15px; color:#111827; margin-bottom:2px;">
                    {{ $remetente?->name ?? 'Gestão Smart' }}
                </strong>

                <span>Expansão / Comercial</span><br>
                <span>DPC</span>

                @if(! empty($remetente?->email))
                    <div style="margin-top:10px;">
                        {{ $remetente->email }}
                    </div>
                @endif
            </td>
        </tr>
    </table>
@endsection
