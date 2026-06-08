<div class="rounded-2xl bg-white dark:bg-gray-900 shadow-2xl overflow-hidden">
    {{-- Cabeçalho --}}
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-5">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Análise de Projeto</h2>
    </div>

    @php
        use Illuminate\Support\Str;

        $texto = $analise ?? '';

        $titulos = [
            'Resumo Executivo',
            'Pontos de Força',
            'Vulnerabilidades / Riscos',
            'Recomendações Estratégicas',
            'Próximos Passos',
        ];

        // cores/estilos por seção
        $ui = [
            'Resumo Executivo'            => ['bg' => 'bg-amber-50 dark:bg-amber-900/20', 'border' => 'border-amber-400', 'dot' => 'bg-amber-400',
                                                'title' => 'text-amber-700 dark:text-amber-300',],
            'Pontos de Força'             => ['bg' => 'bg-emerald-50 dark:bg-emerald-900/20', 'border' => 'border-emerald-400', 'dot' => 'bg-emerald-400',
        'title' => 'text-emerald-700 dark:text-emerald-300',],
            'Vulnerabilidades / Riscos'   => ['bg' => 'bg-rose-50 dark:bg-rose-900/20', 'border' => 'border-rose-400', 'dot' => 'bg-rose-400',
        'title' => 'text-emerald-700 dark:text-emerald-300',],
            'Recomendações Estratégicas'  => ['bg' => 'bg-sky-50 dark:bg-sky-900/20', 'border' => 'border-sky-400', 'dot' => 'bg-sky-400',
        'title' => 'text-emerald-700 dark:text-emerald-300',],
            'Próximos Passos'             => ['bg' => 'bg-violet-50 dark:bg-violet-900/20', 'border' => 'border-violet-400', 'dot' => 'bg-violet-400',
        'title' => 'text-emerald-700 dark:text-emerald-300',],
        ];

        // quebra em seções (aceita ### Título ou Título:)
        $secoes = [];
        foreach ($titulos as $i => $titulo) {
            $proximo = $titulos[$i+1] ?? null;

            $cur  = '(?:###\s*' . preg_quote($titulo, '/') . '|' . preg_quote($titulo, '/') . '\s*:?)';
            $next = $proximo
                ? '(?=###\s*' . preg_quote($proximo, '/') . '|' . preg_quote($proximo, '/') . '\s*:?)'
                : '$';

            $pattern = "/$cur\\s*(.*?)(?:$next)/is";

            preg_match($pattern, $texto, $m);
            $conteudo = isset($m[1]) ? trim($m[1]) : '';

            // fallback
            $conteudo = $conteudo !== '' ? $conteudo : "Sem {$titulo} disponível.";

            // Renderiza como Markdown (listas, negrito etc.)
            $html = Str::markdown($conteudo, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]);

            $secoes[$titulo] = $html;
        }
    @endphp

    {{-- Conteúdo --}}
    <div class="p-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @foreach($secoes as $titulo => $html)
                @php $c = $ui[$titulo]; @endphp
                <section class="group rounded-xl border {{ $c['border'] }} {{ $c['bg'] }} p-4 shadow-sm hover:shadow-md transition">
                    <header class="flex items-center gap-2 mb-2">
                        <span class="inline-block size-2 rounded-full {{ $c['dot'] }}"></span>
                        <h3 class="text-lg font-semibold {{ $c['title'] }}">
    {{ $titulo }}
</h3>
                    </header>

                    <div class="prose max-w-none prose-p:my-2 prose-ul:my-2 prose-li:my-0 dark:prose-invert">
                        {!! $html !!}
                    </div>
                </section>
            @endforeach
        </div>

        {{-- Rodapé opcional --}}
        <div class="mt-6 text-xs text-gray-500 dark:text-gray-400">
            <span class="inline-block px-2 py-1 rounded-md bg-gray-100 dark:bg-gray-800">Gerado por IA</span>
        </div>
    </div>
</div>
