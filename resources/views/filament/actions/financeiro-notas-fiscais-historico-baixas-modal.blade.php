@php
    /** @var \App\Models\ControleNotaFiscalNota|null $nota */
    $baixas = $nota?->baixas ?? collect();
@endphp

<div class="fi-section-content-ctn">
    @if ($baixas->isEmpty())
        <p style="color: #6b7280; font-size: 0.875rem;">
            Esta nota ainda não possui registros de baixa.
        </p>
    @else
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                <thead>
                    <tr style="border-bottom: 1px solid #e5e7eb; text-align: left;">
                        <th style="padding: 0.5rem 0.75rem; font-weight: 600; color: #374151;">#</th>
                        <th style="padding: 0.5rem 0.75rem; font-weight: 600; color: #374151;">Baixado por</th>
                        <th style="padding: 0.5rem 0.75rem; font-weight: 600; color: #374151;">Baixado em</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($baixas as $i => $baixa)
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 0.55rem 0.75rem; color: #6b7280;">
                                {{ $baixas->count() - $i }}
                            </td>
                            <td style="padding: 0.55rem 0.75rem; color: #111827;">
                                {{ $baixa->usuario?->name ?? '—' }}
                            </td>
                            <td style="padding: 0.55rem 0.75rem; color: #111827;">
                                {{ $baixa->baixado_em?->format('d/m/Y H:i') ?? '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p style="margin-top: 0.85rem; color: #6b7280; font-size: 0.78rem;">
            {{ $baixas->count() }} {{ $baixas->count() === 1 ? 'registro' : 'registros' }} de baixa.
        </p>
    @endif
</div>
