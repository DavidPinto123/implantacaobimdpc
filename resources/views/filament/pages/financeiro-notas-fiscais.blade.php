<x-filament-panels::page>
    <style>
        /* +5px de altura nas linhas (2.5px no topo + 2.5px no fundo) */
        [data-gs-table-key="financeiro.notas_fiscais"] .gs-table-excel-page__td {
            padding-top: calc(0.45rem + 2.5px);
            padding-bottom: calc(0.45rem + 2.5px);
        }

        /* Linha de nota baixada — verde bem claro. */
        [data-gs-table-key="financeiro.notas_fiscais"] .gs-table-excel-page__row--baixada > td {
            background: color-mix(in srgb, var(--gs-pill-success) 5%, var(--gs-bg));
        }

        :root.dark [data-gs-table-key="financeiro.notas_fiscais"] .gs-table-excel-page__row--baixada > td {
            background: color-mix(in srgb, var(--gs-pill-success) 7%, var(--gs-bg));
        }

        /* Linha selecionada (bulk de notas) — fundo amarelo claro, sobrepõe pendente/baixada. */
        [data-gs-table-key="financeiro.notas_fiscais"] .gs-table-excel-page__row--selected > td {
            background: color-mix(in srgb, var(--gs-accent) 7%, var(--gs-bg));
        }

        :root.dark [data-gs-table-key="financeiro.notas_fiscais"] .gs-table-excel-page__row--selected > td {
            background: color-mix(in srgb, var(--gs-accent) 6%, var(--gs-bg));
        }

        /* Modal de pré-visualização de arquivos (NF / Boleto). */
        .gs-fnf-preview-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 60;
            padding: 1.25rem;
        }

        .gs-fnf-preview-modal {
            background: var(--gs-bg, #ffffff);
            color: inherit;
            border-radius: 0.75rem;
            width: min(1100px, 100%);
            height: min(85vh, 900px);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
        }

        .gs-fnf-preview-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--gs-border, #e5e7eb);
            font-weight: 600;
        }

        .gs-fnf-preview-close {
            background: transparent;
            border: 0;
            font-size: 1.5rem;
            line-height: 1;
            cursor: pointer;
            color: inherit;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
        }

        .gs-fnf-preview-close:hover {
            background: var(--gs-bg-subtle, #f3f4f6);
        }

        .gs-fnf-preview-body {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--gs-bg-subtle, #f3f4f6);
        }

        .gs-fnf-preview-body iframe {
            width: 100%;
            height: 100%;
            border: 0;
        }

        .gs-fnf-preview-body img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .gs-fnf-preview-fallback {
            padding: 2rem;
            text-align: center;
        }

        .gs-fnf-preview-fallback a {
            color: var(--gs-accent, #2563eb);
            text-decoration: underline;
        }
    </style>

    <div
        x-data="{
            open: false,
            url: '',
            label: '',
            kind: 'other',
            detectKind(href) {
                const clean = (href || '').split('?')[0].split('#')[0].toLowerCase();
                const ext = clean.includes('.') ? clean.split('.').pop() : '';
                if (ext === 'pdf') return 'pdf';
                if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'].includes(ext)) return 'image';
                return 'other';
            },
            abrir(href, titulo) {
                this.url = href;
                this.label = titulo || 'Pré-visualização';
                this.kind = this.detectKind(href);
                this.open = true;
            },
            fechar() {
                this.open = false;
                this.url = '';
            },
        }"
        x-init="
            document.addEventListener('click', (event) => {
                const link = event.target.closest('td[data-gs-column=&quot;anexos&quot;] a.gs-te-action');
                if (! link) return;
                const href = link.getAttribute('href');
                if (! href || href === '#') return;
                event.preventDefault();
                event.stopPropagation();
                abrir(href, link.getAttribute('title') || link.getAttribute('aria-label') || 'Pré-visualização');
            });
        "
        x-on:keydown.escape.window="open && fechar()"
    >
        <template x-if="open">
            <div class="gs-fnf-preview-backdrop" x-on:click.self="fechar()">
                <div class="gs-fnf-preview-modal" role="dialog" aria-modal="true">
                    <div class="gs-fnf-preview-header">
                        <span x-text="label"></span>
                        <button type="button" class="gs-fnf-preview-close" x-on:click="fechar()" aria-label="Fechar">&times;</button>
                    </div>
                    <div class="gs-fnf-preview-body">
                        <template x-if="kind === 'pdf'">
                            <iframe :src="url" title="Pré-visualização do arquivo"></iframe>
                        </template>
                        <template x-if="kind === 'image'">
                            <img :src="url" alt="Pré-visualização do arquivo">
                        </template>
                        <template x-if="kind === 'other'">
                            <div class="gs-fnf-preview-fallback">
                                <p>Pré-visualização não suportada para este formato.</p>
                                <p>
                                    <a :href="url" target="_blank" rel="noopener">Abrir em nova aba</a>
                                </p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>

    @include('filament.pages.partials.financeiro-notas-fiscais-table', $this->getTableExcelViewData())
</x-filament-panels::page>
