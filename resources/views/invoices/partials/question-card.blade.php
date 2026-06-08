@php
    use App\Support\PdfFormatter;

    $showBadge = $showBadge ?? true;
    $fields = $fields ?? [];
@endphp

<div class="card">
    <div class="question-header">
        <h4>
            {{ $title }}
            @if (!empty($subtitle))
                <small>{{ $subtitle }}</small>
            @endif
        </h4>

        @if ($showBadge)
            {!! PdfFormatter::badge($badge ?? null) !!}
        @endif
    </div>

    @foreach ($fields as $field)
        <div class="field mt">
            <label>{{ $field['label'] }}</label>

            @if (!empty($field['html']))
                <div class="value">{!! $field['html'] !!}</div>
            @else
                <div class="value">{{ $field['value'] ?? 'Não se aplica' }}</div>
            @endif
        </div>
    @endforeach

    @if (isset($media))
        @include('invoices.partials.media-grid', [
            'arquivos' => $media,
            'limit' => $limit ?? 6,
        ])
    @endif
</div>