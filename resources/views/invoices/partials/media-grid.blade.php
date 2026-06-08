@php
    use App\Support\PdfMedia;

    $arquivos = PdfMedia::normalizeFiles($arquivos ?? [], $limit ?? 6);
@endphp

<div class="imagens">
    @forelse ($arquivos as $arquivo)
        @php
            $arquivoPath = PdfMedia::filePath($arquivo);
            $isVideo = PdfMedia::isVideo($arquivo);
            $src = PdfMedia::src($arquivo);
            $href = PdfMedia::href($arquivo);
        @endphp

        @if (!$arquivoPath)
            <div class="pdf-img-fallback">Arquivo inválido</div>
        @elseif ($isVideo)
            <a href="{{ $href }}" target="_blank" class="video-thumb">
                <div class="video-play"></div>
                <div class="video-text">Assistir vídeo</div>
            </a>
        @elseif ($src)
            <a href="{{ $href }}" target="_blank">
                <img src="{{ $src }}" class="thumb" alt="Imagem">
            </a>
        @else
            <div class="pdf-img-fallback">Imagem indisponível</div>
        @endif
    @empty
        <p>Nenhuma imagem disponível</p>
    @endforelse
</div>