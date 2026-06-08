@extends('emails.layout', ['title' => $assunto ?? 'E-mail'])

@section('content')
    <div style="font-size:15px; line-height:1.7; color:#1f2937;">
        {!! $mensagemEmail !!}
    </div>
@endsection

@section('signature')
    <table cellpadding="0" cellspacing="0" border="0" style="width:100%;">
        <tr>
            @if(! empty($fotoPerfilBinaria))
                <td style="width:90px; vertical-align:top; padding-right:16px;">
                    <img
                        src="{{ $message->embedData(
                            $fotoPerfilBinaria,
                            $fotoPerfilNome ?? 'perfil.png',
                            $fotoPerfilMime ?? 'image/png'
                        ) }}"
                        style="width:90px; height:90px; border-radius:50%; display:block;"
                        alt="Foto"
                    >
                </td>

                <td style="width:1px; background:#d1d5db;"></td>

                <td style="padding-left:16px; font-family:Arial, sans-serif; font-size:13px; line-height:1.6; color:#374151;">
            @else
                <td style="font-family:Arial, sans-serif; font-size:13px; line-height:1.6; color:#374151;">
            @endif

                <strong style="display:block; font-size:15px; color:#111827; margin-bottom:2px;">
                    {{ $nomeRemetente ?? 'Gestão Smart' }}
                </strong>

                <span>{{ $cargoRemetente ?? 'Expansão / Comercial' }}</span><br>
                <span>{{ $empresaRemetente ?? 'DPC' }}</span>

                @if(! empty($emailRemetente))
                    <div style="margin-top:10px;">
                        {{ $emailRemetente }}
                    </div>
                @endif

                @if(! empty($telefoneRemetente))
                    <div style="margin-top:4px;">
                        {{ $telefoneRemetente }}
                    </div>
                @endif

                @if(! empty($enderecoRemetente))
                    <div style="margin-top:4px;">
                        {{ $enderecoRemetente }}
                    </div>
                @endif

                @if(! empty($linkRemetente))
                    <div style="margin-top:4px;">
                        <a href="{{ $linkRemetente }}" target="_blank" style="color:#2563eb; text-decoration:none;">
                            {{ $linkRemetente }}
                        </a>
                    </div>
                @endif
            </td>
        </tr>
    </table>
@endsection
