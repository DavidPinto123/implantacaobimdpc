<x-filament-widgets::widget>
    <x-filament::section>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-gray-200 dark:border-white/10">
                    <tr class="text-left">
                        <th class="px-6 py-4 font-semibold text-gray-950 dark:text-white">
                            Status
                        </th>

                        <th class="px-6 py-4 font-semibold text-gray-950 dark:text-white">
                            Total
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($linhas as $linha)
                        <tr class="border-b border-gray-100 dark:border-white/5">
                            <td class="px-6 py-5 font-semibold text-gray-950 dark:text-white">
                                {{ $linha['titulo'] }}
                            </td>

                            <td class="px-6 py-5 text-gray-700 dark:text-gray-300">
                                <div class="space-y-1">
                                    <div class="font-medium text-gray-950 dark:text-white"></div>
                                    <div>R$ {{ number_format($linha['total'], 2, ',', '.') }}</div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>