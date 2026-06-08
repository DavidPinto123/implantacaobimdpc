@php
    use App\Support\PdfMedia;

    $arquivos = PdfMedia::normalizeFiles($arquivos ?? [], 20);
@endphp

@foreach ($arquivos as $arquivo)
    @php
        $arquivoPath = PdfMedia::filePath($arquivo);
        $ext = PdfMedia::extension($arquivo);
        $url = PdfMedia::href($arquivo);
    @endphp

    @if (!$arquivoPath)
        <div class="entrega-doc" style="display:block; margin-bottom:6px;">
            Arquivo inválido
        </div>
    @elseif ($ext === 'pdf')
        <div class="entrega-doc" style="display:block; margin-bottom:6px;">
            Documento PDF:
            <a href="{{ $url }}" target="_blank" style="text-decoration:none;">Abrir</a>
        </div>
    @elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
        <div class="entrega-doc" style="display:block; margin-bottom:6px;">
            Imagem:
            <a href="{{ $url }}" target="_blank" style="text-decoration:none;">Abrir</a>
        </div>
    @elseif (in_array($ext, ['mp4', 'mov', 'avi', 'mpeg', 'webm']))
        <div class="entrega-doc" style="display:block; margin-bottom:6px;">
            Vídeo:
            <a href="{{ $url }}" target="_blank" style="text-decoration:none;">Assistir</a>
        </div>
    @else
        <div class="entrega-doc" style="display:block; margin-bottom:6px;">
            Arquivo:
            <a href="{{ $url }}" target="_blank" style="text-decoration:none;">Abrir</a>
        </div>
    @endif
@endforeach