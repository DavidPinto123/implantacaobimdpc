{{-- Table Excel Pattern — Alpine/vanilla JS loaded via panels::scripts.after render hook.
     Responsible for: (a) computing cumulative sticky offsets for frozen columns,
     (b) attaching drag handles to resizable column headers and persisting widths. --}}
<script>
(function () {
    'use strict';

    const ATTACHED = new WeakSet();
    const MIN_WIDTH = 40;
    const MAX_WIDTH = 1200;

    function findLivewireComponent(el) {
        const wireEl = el.closest('[wire\\:id]');
        if (! wireEl) return null;

        const id = wireEl.getAttribute('wire:id');
        if (! id) return null;

        if (typeof window.Livewire?.find === 'function') {
            return window.Livewire.find(id);
        }

        return null;
    }

    function applyInlineWidth(root, colName, widthPx) {
        const safe = CSS.escape(colName);
        const size = `${widthPx}px`;

        root.querySelectorAll(`[data-gs-column="${safe}"]`).forEach((el) => {
            el.style.width = size;
            el.style.minWidth = size;
            el.style.maxWidth = size;
        });
    }

    function recalculateStickyOffsets(root) {
        const frozenHeaders = Array.from(root.querySelectorAll('th[data-gs-frozen="1"]'));

        let accumulated = 0;

        frozenHeaders.forEach((header) => {
            const colName = header.dataset.gsColumn;
            const offset = `${accumulated}px`;

            header.style.setProperty('--gs-sticky-left', offset);

            if (colName) {
                const safe = CSS.escape(colName);
                root
                    .querySelectorAll(`td[data-gs-column="${safe}"][data-gs-frozen="1"]`)
                    .forEach((cell) => cell.style.setProperty('--gs-sticky-left', offset));
            }

            accumulated += header.getBoundingClientRect().width;
        });
    }

    function attachResizeHandles(root) {
        root.querySelectorAll('th[data-gs-resizable="1"]').forEach((th) => {
            if (th.dataset.gsResizeAttached === '1') return;
            th.dataset.gsResizeAttached = '1';

            const handle = document.createElement('span');
            handle.className = 'gs-table-excel__resize-handle';
            handle.setAttribute('aria-hidden', 'true');
            th.appendChild(handle);

            let dragging = false;
            let startX = 0;
            let startWidth = 0;

            const onMouseMove = (event) => {
                if (! dragging) return;

                const delta = event.clientX - startX;
                const newWidth = Math.max(MIN_WIDTH, Math.min(MAX_WIDTH, startWidth + delta));
                const colName = th.dataset.gsColumn;

                if (colName) {
                    applyInlineWidth(root, colName, newWidth);
                }

                recalculateStickyOffsets(root);
            };

            const onMouseUp = () => {
                if (! dragging) return;
                dragging = false;
                document.body.style.cursor = '';
                document.body.style.userSelect = '';

                const newWidth = th.getBoundingClientRect().width;
                const colName = th.dataset.gsColumn;
                const tableKey = root.dataset.gsTableKey;

                if (colName && tableKey) {
                    const component = findLivewireComponent(root);
                    if (component?.call) {
                        component.call(
                            'tableExcelSetColumnWidth',
                            tableKey,
                            colName,
                            Math.round(newWidth),
                        );
                    }
                }

                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);
            };

            handle.addEventListener('mousedown', (event) => {
                dragging = true;
                startX = event.clientX;
                startWidth = th.getBoundingClientRect().width;
                document.body.style.cursor = 'col-resize';
                document.body.style.userSelect = 'none';
                event.preventDefault();
                event.stopPropagation();

                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
            });

            handle.addEventListener('dblclick', (event) => {
                event.preventDefault();
                event.stopPropagation();

                const colName = th.dataset.gsColumn;
                const tableKey = root.dataset.gsTableKey;

                if (colName && tableKey) {
                    const component = findLivewireComponent(root);
                    if (component?.call) {
                        component.call('tableExcelResetColumnWidth', tableKey, colName);
                    }
                }
            });
        });
    }

    function init(root) {
        if (ATTACHED.has(root)) {
            recalculateStickyOffsets(root);
            attachResizeHandles(root);
            return;
        }

        ATTACHED.add(root);

        attachResizeHandles(root);
        recalculateStickyOffsets(root);
        bindCellHighlight(root);

        if (typeof ResizeObserver === 'function') {
            const observer = new ResizeObserver(() => recalculateStickyOffsets(root));
            root.querySelectorAll('th[data-gs-frozen="1"]').forEach((h) => observer.observe(h));
        }
    }

    /**
     * Ao clicar numa célula (<td data-gs-column>), destaca em amarelo a
     * linha e a coluna inteiras (cruzamento, estilo Excel).
     *
     * Listener delegado na <table> — continua funcionando mesmo quando o
     * <tbody> é re-renderizado via AJAX (innerHTML replace). Só precisa
     * ser ligado UMA VEZ por root, daí o guard via dataset.
     */
    function bindCellHighlight(root) {
        if (root.dataset.gsHighlightBound === '1') return;
        root.dataset.gsHighlightBound = '1';

        const table = root.querySelector('.gs-table-excel-page__table');
        if (! table) return;

        const clearFocus = () => {
            root.querySelectorAll('.gs-te-focus-row').forEach((el) => el.classList.remove('gs-te-focus-row'));
            root.querySelectorAll('.gs-te-focus-col').forEach((el) => el.classList.remove('gs-te-focus-col'));
        };

        table.addEventListener('click', (event) => {
            const td = event.target.closest('td[data-gs-column]');
            if (! td) return;

            const tr = td.parentElement;
            if (! tr) return;

            const colKey = td.dataset.gsColumn;
            if (! colKey) return;

            clearFocus();

            // Aplica novo destaque: linha + toda a coluna (td + th).
            tr.classList.add('gs-te-focus-row');
            const safe = CSS.escape(colKey);
            root.querySelectorAll(`[data-gs-column="${safe}"]`).forEach((el) => {
                el.classList.add('gs-te-focus-col');
            });
        });

        // Limpa destaque ao clicar fora da tabela.
        document.addEventListener('click', (event) => {
            if (! root.isConnected) return;
            if (root.contains(event.target)) return;
            clearFocus();
        });

        // Limpa destaque ao pressionar Esc.
        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;
            if (! root.isConnected) return;
            clearFocus();
        });
    }

    /**
     * Recarrega o <tbody> de cada tabela via $wire.call('fetchRowsHtml').
     * Disparado pelo servidor através do evento 'te-refresh-rows' após
     * filtrar/ordenar/paginar/editar. Cada root chama seu próprio
     * componente Livewire, então múltiplas tabelas não se confundem.
     */
    async function refreshAllTableBodies() {
        const roots = document.querySelectorAll('.gs-table-excel');

        for (const root of roots) {
            const tbody = root.querySelector('[data-gs-tbody]');
            const component = findLivewireComponent(root);
            if (! tbody || ! component?.call) continue;

            try {
                const html = await component.call('fetchRowsHtml');
                if (typeof html !== 'string') continue;
                tbody.innerHTML = html;
                recalculateStickyOffsets(root);
            } catch (error) {
                console.error('[table-excel] fetchRowsHtml falhou:', error);
            }
        }
    }

    function scan() {
        document.querySelectorAll('.gs-table-excel').forEach(init);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scan);
    } else {
        scan();
    }

    document.addEventListener('livewire:navigated', scan);
    document.addEventListener('livewire:init', () => {
        if (window.Livewire?.on) {
            // Listener global único — Livewire.on fica ativo até o teardown
            // da SPA, e dispara refreshAllTableBodies para qualquer tabela
            // na página quando o servidor emite 'te-refresh-rows'.
            window.Livewire.on('te-refresh-rows', () => refreshAllTableBodies());
        }

        if (window.Livewire?.hook) {
            window.Livewire.hook('morph.updated', () => queueMicrotask(scan));
            window.Livewire.hook('commit', ({ succeed }) => {
                succeed(() => queueMicrotask(scan));
            });
        }
    });
})();
</script>
