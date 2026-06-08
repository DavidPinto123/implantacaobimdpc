@php
    $size = $size ?? 32;
    $stroke = $stroke ?? 3;
    $radius = ($size / 2) - $stroke;
    $circumference = 2 * M_PI * $radius;
    $offset = $circumference - ($pct / 100) * $circumference;
@endphp
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 {{ $size }} {{ $size }}" style="flex-shrink: 0;">
    <circle cx="{{ $size/2 }}" cy="{{ $size/2 }}" r="{{ $radius }}"
            fill="none" stroke="rgba(0,0,0,.12)" stroke-width="{{ $stroke }}"/>
    <circle cx="{{ $size/2 }}" cy="{{ $size/2 }}" r="{{ $radius }}"
            fill="none" stroke="#111" stroke-width="{{ $stroke }}"
            stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $offset }}"
            stroke-linecap="round"
            transform="rotate(-90 {{ $size/2 }} {{ $size/2 }})"/>
    <text x="50%" y="50%" text-anchor="middle" dominant-baseline="central"
          font-size="{{ $size * 0.28 }}" font-weight="800" fill="#111">{{ $pct }}%</text>
</svg>
