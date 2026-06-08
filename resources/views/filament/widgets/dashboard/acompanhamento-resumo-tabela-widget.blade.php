<x-filament-widgets::widget>
    @php
        $resumo = $this->getResumo();
        $labels = $resumo['labels'];
        $rows = $resumo['rows'];
    @endphp

    <style>
        .home-acomp-wrap {
            margin-top: -14px;
        }

        .home-acomp-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            color: #111827;
            background: #ffffff;
        }

        .home-acomp-table thead tr {
            background: #f5bf00;
            color: #111827;
        }

        .home-acomp-table th,
        .home-acomp-table td {
            padding: 6px 10px;
            border-bottom: 1px solid #d1d5db;
            white-space: nowrap;
        }

        .home-acomp-table tbody tr:nth-child(odd) {
            background: #f9fafb;
        }

        .home-acomp-table .row-total {
            background: #e5e7eb;
            font-weight: 700;
        }

        .dark .home-acomp-table {
            color: #e5e7eb;
            background: #0f1115;
        }

        .dark .home-acomp-table th,
        .dark .home-acomp-table td {
            border-bottom-color: #2b2f37;
        }

        .dark .home-acomp-table tbody tr {
            background: #0f1115;
        }

        .dark .home-acomp-table tbody tr:nth-child(odd) {
            background: #151922;
        }

        .dark .home-acomp-table .row-total {
            background: #1f2937;
            color: #ffffff;
        }
    </style>

    <div class="home-acomp-wrap">
        <div class="overflow-x-auto">
            <table class="home-acomp-table">
                <thead>
                    <tr>
                        <th class="text-left font-semibold">Status</th>
                        @foreach ($labels as $label)
                            <th class="text-center font-semibold">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $status => $values)
                        <tr class="{{ $status === 'Total' ? 'row-total' : '' }}">
                            <td class="font-medium">{{ $status }}</td>
                            @foreach ($values as $value)
                                @if ($status === 'Delta Meta')
                                    @php
                                        $isEmpty = $value === null || $value === '';
                                        $valueInt = $isEmpty ? null : (int) $value;
                                        $class = $isEmpty ? '' : ($valueInt > 0 ? 'text-success-600' : ($valueInt < 0 ? 'text-danger-600' : 'text-gray-600'));
                                    @endphp
                                    <td class="text-center {{ $class }}">{{ $isEmpty ? '' : $valueInt }}</td>
                                @else
                                    <td class="text-center">{{ $value }}</td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament-widgets::widget>
