<x-filament::page>
    <div class="flex flex-col md:flex-row gap-6">
        <!-- MAPA -->
        <div class="md:w-1/2 flex-shrink-0">
            <div class="flex items-center justify-between mb-2">
                <h2 id="mapa-titulo" class="font-bold">Selecione um país</h2>
                <button type="button" id="mapa-voltar"
                    class="hidden text-sm font-semibold px-3 py-1 rounded"
                    style="background-color:#ffba00; color:black;">
                    ← Voltar para as Américas
                </button>
            </div>
            <div id="projetos-mapa" class="w-full rounded-lg overflow-hidden" style="height:80vh;">
                <svg id="projetos-mapa-svg" class="w-full h-full" viewBox="0 0 800 800" preserveAspectRatio="xMidYMid meet"></svg>
            </div>
            <p class="text-xs text-gray-500 mt-1">Use a roda do mouse para zoom e arraste para mover.</p>
        </div>

        <!-- CONTEÚDO COM ABAS -->
        <div class="rounded-b-lg flex-1" x-data="{ tab: 'prospeccao' }">

            <!-- Cards de Quantitativos -->
            <div class="flex flex-wrap gap-4 mb-6">
                <div class="flex-1 min-w-[150px] p-4 rounded-lg shadow-md flex flex-col items-center"
                    style="background-color:#ffba00; color:black;">
                    <span class="text-lg font-bold">Prospecção</span>
                    <span id="qtd-prospeccao" class="text-2xl font-extrabold">0</span>
                </div>
                <div class="flex-1 min-w-[150px] p-4 rounded-lg shadow-md flex flex-col items-center"
                    style="background-color:#ffba00; color:black;">
                    <span class="text-lg font-bold">Assinatura</span>
                    <span id="qtd-assinatura" class="text-2xl font-extrabold">0</span>
                </div>
                <div class="flex-1 min-w-[150px] p-4 rounded-lg shadow-md flex flex-col items-center"
                    style="background-color:#ffba00; color:black;">
                    <span class="text-lg font-bold">Projetos</span>
                    <span id="qtd-projetos" class="text-2xl font-extrabold">0</span>
                </div>
                <div class="flex-1 min-w-[150px] p-4 rounded-lg shadow-md flex flex-col items-center"
                    style="background-color:#ffba00; color:black;">
                    <span class="text-lg font-bold">Obras</span>
                    <span id="qtd-obras" class="text-2xl font-extrabold">0</span>
                </div>
            </div>

            <!-- Abas -->
            <div class="mb-0 flex space-x-2" aria-label="Tabs">
                <template x-for="t in [
                    {key: 'prospeccao', label: 'Prospecção'},
                    {key: 'assinatura', label: 'Assinatura'},
                    {key: 'projetos',   label: 'Projetos'},
                    {key: 'obras',      label: 'Obras'},
                ]" :key="t.key">
                    <button
                        @click="tab = t.key"
                        :class="tab === t.key
                            ? 'font-bold px-4 py-2 rounded-t-lg'
                            : 'text-gray-600 dark:text-white hover:text-black px-4 py-2'"
                        :style="tab === t.key ? 'background-color:#ffba00; color:black; border-top-left-radius:8px; border-top-right-radius:8px;' : ''"
                        x-text="t.label">
                    </button>
                </template>
            </div>

            <!-- Tabela Prospecção -->
            <div x-show="tab === 'prospeccao'">
                <div class="shadow-sm rounded-b-lg overflow-hidden">
                    <div class="overflow-y-auto" style="max-height:400px;">
                        <table class="w-full text-sm">
                            <thead style="background-color:#ffba00; color:black;">
                                <tr>
                                    <th class="px-3 py-2 text-left w-1/2"
                                        style="position:sticky; top:0; z-index:10; background-color:#ffba00; color:black;">
                                        Nome
                                    </th>
                                    <th class="px-3 py-2 text-left w-1/4"
                                        style="position:sticky; top:0; z-index:10; background-color:#ffba00; color:black;">
                                        Cidade
                                    </th>
                                    <th class="px-3 py-2 text-left w-1/4"
                                        style="position:sticky; top:0; z-index:10; background-color:#ffba00; color:black;">
                                        Estado
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="tabela-prospeccao"
                                class="divide-y divide-gray-200 bg-white dark:bg-gray-900 dark:text-white"
                                style="color:inherit;">
                                <tr>
                                    <td colspan="3" class="text-center py-3 text-gray-500">Selecione um estado.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tabela Assinatura -->
            <div x-show="tab === 'assinatura'">
                <div class="shadow-sm rounded overflow-hidden">
                    <div class="overflow-y-auto" style="max-height:400px;">
                        <table class="w-full text-sm">
                            <thead style="background-color:#ffba00; color:black;">
                                <tr>
                                    <th class="px-3 py-2 text-left w-1/2"
                                        style="position:sticky; top:0; z-index:10; background-color:#ffba00; color:black;">
                                        Nome
                                    </th>
                                    <th class="px-3 py-2 text-left w-1/4"
                                        style="position:sticky; top:0; z-index:10; background-color:#ffba00; color:black;">
                                        Cidade
                                    </th>
                                    <th class="px-3 py-2 text-left w-1/4"
                                        style="position:sticky; top:0; z-index:10; background-color:#ffba00; color:black;">
                                        Estado
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="tabela-assinatura"
                                class="divide-y divide-gray-200 bg-white dark:bg-gray-900 dark:text-white"
                                style="color:inherit;">
                                <tr>
                                    <td colspan="3" class="text-center py-3 text-gray-500">Selecione um estado.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tabela Projetos -->
            <div x-show="tab === 'projetos'">
                <div class="shadow-sm rounded overflow-hidden">
                    <div class="overflow-y-auto" style="max-height:400px;">
                        <table class="w-full text-sm">
                            <thead style="background-color:#ffba00; color:black;">
                                <tr>
                                    <th class="px-3 py-2 text-left w-1/2"
                                        style="position:sticky; top:0; z-index:10; background-color:#ffba00; color:black;">
                                        Nome
                                    </th>
                                    <th class="px-3 py-2 text-left w-1/4"
                                        style="position:sticky; top:0; z-index:10; background-color:#ffba00; color:black;">
                                        Cidade
                                    </th>
                                    <th class="px-3 py-2 text-left w-1/4"
                                        style="position:sticky; top:0; z-index:10; background-color:#ffba00; color:black;">
                                        Estado
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="tabela-projetos"
                                class="divide-y divide-gray-200 bg-white dark:bg-gray-900 dark:text-white"
                                style="color:inherit;">
                                <tr>
                                    <td colspan="3" class="text-center py-3 text-gray-500">Selecione um estado.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tabela Obras -->
            <div x-show="tab === 'obras'">
                <div class="shadow-sm rounded overflow-hidden">
                    <div class="overflow-y-auto" style="max-height:400px;">
                        <table class="w-full text-sm">
                            <thead style="background-color:#ffba00; color:black;">
                                <tr>
                                    <th class="px-3 py-2 text-left w-1/2"
                                        style="position:sticky; top:0; z-index:10; background-color:#ffba00; color:black;">
                                        Nome
                                    </th>
                                    <th class="px-3 py-2 text-left w-1/4"
                                        style="position:sticky; top:0; z-index:10; background-color:#ffba00; color:black;">
                                        Cidade
                                    </th>
                                    <th class="px-3 py-2 text-left w-1/4"
                                        style="position:sticky; top:0; z-index:10; background-color:#ffba00; color:black;">
                                        Estado
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="tabela-obra"
                                class="divide-y divide-gray-200 bg-white dark:bg-gray-900 dark:text-white"
                                style="color:inherit;">
                                <tr>
                                    <td colspan="3" class="text-center py-3 text-gray-500">Selecione um estado.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament::page>

@push('styles')
    <style>
        #projetos-mapa { z-index: 1; }
        #projetos-mapa-svg { display: block; }
        .map-pais { fill: #d1d5db; stroke: #ffffff; stroke-width: 0.5px; cursor: pointer; transition: fill .15s; }
        .map-pais:hover { fill: #ffba00; }
        .map-estado { fill: #fde68a; stroke: #ffffff; stroke-width: 0.5px; cursor: pointer; transition: fill .15s; }
        .map-estado:hover { fill: #ffba00; }
        .map-estado.selected { fill: #ef0505; stroke: #ef0505; stroke-width: 1px; }
        .map-tooltip {
            position: absolute;
            pointer-events: none;
            background: rgba(17, 24, 39, 0.92);
            color: #fff;
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 4px;
            transform: translate(-50%, -120%);
            white-space: nowrap;
            display: none;
            z-index: 10;
        }
    </style>
@endpush

@push('scripts')
<script src="https://unpkg.com/d3@7/dist/d3.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const container = document.getElementById('projetos-mapa');
    const svgEl = document.getElementById('projetos-mapa-svg');
    if (!container || !svgEl) return;

    // ISO-2 → nome (pt-br) dos países das Américas que devem aparecer no mapa.
    const PAISES_AMERICAS = {
        'AR': 'Argentina', 'BO': 'Bolívia', 'BR': 'Brasil', 'CL': 'Chile',
        'CO': 'Colômbia', 'EC': 'Equador', 'GY': 'Guiana', 'PY': 'Paraguai',
        'PE': 'Peru', 'SR': 'Suriname', 'UY': 'Uruguai', 'VE': 'Venezuela',
        'BZ': 'Belize', 'CR': 'Costa Rica', 'SV': 'El Salvador',
        'GT': 'Guatemala', 'HN': 'Honduras', 'MX': 'México', 'NI': 'Nicarágua',
        'PA': 'Panamá',
        'BS': 'Bahamas', 'BB': 'Barbados', 'CU': 'Cuba',
        'DO': 'República Dominicana', 'HT': 'Haiti', 'JM': 'Jamaica',
        'PR': 'Porto Rico', 'TT': 'Trinidad e Tobago',
    };
    const NOME_GEOJSON_PARA_ISO = {
        'Argentina': 'AR', 'Bolivia': 'BO', 'Brazil': 'BR', 'Chile': 'CL',
        'Colombia': 'CO', 'Ecuador': 'EC', 'Guyana': 'GY', 'Paraguay': 'PY',
        'Peru': 'PE', 'Suriname': 'SR', 'Uruguay': 'UY', 'Venezuela': 'VE',
        'Belize': 'BZ', 'Costa Rica': 'CR', 'El Salvador': 'SV',
        'Guatemala': 'GT', 'Honduras': 'HN', 'Mexico': 'MX', 'Nicaragua': 'NI',
        'Panama': 'PA',
        'The Bahamas': 'BS', 'Bahamas': 'BS',
        'Barbados': 'BB', 'Cuba': 'CU', 'Dominican Republic': 'DO',
        'Haiti': 'HT', 'Jamaica': 'JM', 'Puerto Rico': 'PR',
        'Trinidad and Tobago': 'TT',
    };

    const WIDTH = 800;
    const HEIGHT = 800;
    svgEl.setAttribute('viewBox', `0 0 ${WIDTH} ${HEIGHT}`);

    const svg = d3.select(svgEl);
    const root = svg.append('g').attr('class', 'mapa-root');
    const camadaPaises = root.append('g').attr('class', 'camada-paises');
    const camadaEstados = root.append('g').attr('class', 'camada-estados');

    // Tooltip
    container.style.position = 'relative';
    const tooltip = document.createElement('div');
    tooltip.className = 'map-tooltip';
    container.appendChild(tooltip);
    const showTooltip = (text, ev) => {
        const r = container.getBoundingClientRect();
        tooltip.style.left = (ev.clientX - r.left) + 'px';
        tooltip.style.top = (ev.clientY - r.top) + 'px';
        tooltip.textContent = text;
        tooltip.style.display = 'block';
    };
    const hideTooltip = () => { tooltip.style.display = 'none'; };

    // Zoom
    const zoom = d3.zoom()
        .scaleExtent([1, 40])
        .on('zoom', (ev) => {
            root.attr('transform', ev.transform);
        });
    svg.call(zoom);

    // UI helpers
    const titulo = document.getElementById('mapa-titulo');
    const btnVoltar = document.getElementById('mapa-voltar');

    const cardsIds = {
        prospeccao: 'qtd-prospeccao',
        assinatura: 'qtd-assinatura',
        projetos: 'qtd-projetos',
        obras: 'qtd-obras',
    };
    const tabelasIds = {
        prospeccao: 'tabela-prospeccao',
        assinatura: 'tabela-assinatura',
        projetos: 'tabela-projetos',
        obra: 'tabela-obra',
    };

    const resetarTabelas = (msg = 'Selecione um estado.') => {
        Object.values(tabelasIds).forEach(id => {
            const tbody = document.getElementById(id);
            if (tbody) tbody.innerHTML = `<tr><td colspan="3" class="text-center py-3 text-gray-500">${msg}</td></tr>`;
        });
        Object.values(cardsIds).forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerText = 0;
        });
    };

    const escapeHtml = (s) => String(s ?? '').replace(/[&<>"']/g, m => ({
        '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;',
    }[m]));

    const renderTabela = (tbody, projetos) => {
        if (!projetos || !projetos.length) {
            tbody.innerHTML = `<tr><td colspan="3" class="text-center py-2">Nenhum projeto encontrado.</td></tr>`;
            return;
        }
        tbody.innerHTML = projetos.map(p => {
            const nome = escapeHtml(p.nome ?? '');
            const cidade = escapeHtml((p.cidade && p.cidade.nome) ? p.cidade.nome : (p.cidade_id ?? ''));
            const estado = escapeHtml((p.estado && p.estado.nome) ? p.estado.nome : (p.estado_id ?? ''));
            const url = p.obra_url;

            if (url) {
                return `<tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location.href='${escapeHtml(url)}'" title="Abrir obra">
                    <td class="px-3 py-2 text-yellow-700 underline">${nome}</td>
                    <td class="px-3 py-2">${cidade}</td>
                    <td class="px-3 py-2">${estado}</td>
                </tr>`;
            }

            return `<tr class="opacity-70" title="Projeto sem obra vinculada">
                <td class="px-3 py-2">${nome}</td>
                <td class="px-3 py-2">${cidade}</td>
                <td class="px-3 py-2">${estado}</td>
            </tr>`;
        }).join('');
    };

    const carregarProjetos = async (paisIso, uf) => {
        try {
            const url = `/projetos-por-localidade?pais=${encodeURIComponent(paisIso)}&uf=${encodeURIComponent(uf)}`;
            const res = await fetch(url);
            const data = await res.json();
            renderTabela(document.getElementById(tabelasIds.prospeccao), data.prospeccao || []);
            renderTabela(document.getElementById(tabelasIds.assinatura), data.assinatura || []);
            renderTabela(document.getElementById(tabelasIds.projetos), data.projetos || []);
            renderTabela(document.getElementById(tabelasIds.obra), data.obra || []);
            document.getElementById(cardsIds.prospeccao).innerText = (data.prospeccao || []).length;
            document.getElementById(cardsIds.assinatura).innerText = (data.assinatura || []).length;
            document.getElementById(cardsIds.projetos).innerText = (data.projetos || []).length;
            document.getElementById(cardsIds.obras).innerText = (data.obra || []).length;
        } catch (err) {
            console.error('Falha ao buscar projetos:', err);
        }
    };

    // ---------- Renderização ----------

    const carregarPaises = async () => {
        const res = await fetch('/geojson/countries.geo.json');
        const geo = await res.json();

        const features = geo.features.filter(f =>
            f.properties && Object.prototype.hasOwnProperty.call(NOME_GEOJSON_PARA_ISO, f.properties.name)
        );
        const fc = { type: 'FeatureCollection', features };

        // Projeção encaixando o conjunto no viewBox.
        const projection = d3.geoMercator().fitSize([WIDTH, HEIGHT], fc);
        const path = d3.geoPath(projection);

        camadaEstados.selectAll('*').remove();
        camadaPaises.selectAll('*').remove();

        camadaPaises.selectAll('path.map-pais')
            .data(features)
            .join('path')
            .attr('class', 'map-pais')
            .attr('d', path)
            .on('mousemove', (ev, f) => {
                const iso = NOME_GEOJSON_PARA_ISO[f.properties.name];
                showTooltip(PAISES_AMERICAS[iso] ?? f.properties.name, ev);
            })
            .on('mouseleave', hideTooltip)
            .on('click', async (ev, f) => {
                const iso = NOME_GEOJSON_PARA_ISO[f.properties.name];
                if (!iso) return;
                hideTooltip();
                await selecionarPais(iso, f, projection);
            });
    };

    const zoomParaFeature = (feature, projection) => {
        const path = d3.geoPath(projection);
        const [[x0, y0], [x1, y1]] = path.bounds(feature);
        const dx = x1 - x0, dy = y1 - y0;
        const cx = (x0 + x1) / 2, cy = (y0 + y1) / 2;
        const scale = Math.min(40, 0.9 / Math.max(dx / WIDTH, dy / HEIGHT));
        const tx = WIDTH / 2 - scale * cx;
        const ty = HEIGHT / 2 - scale * cy;

        svg.transition()
            .duration(700)
            .call(zoom.transform, d3.zoomIdentity.translate(tx, ty).scale(scale));
    };

    const selecionarPais = async (iso, feature, projectionPaises) => {
        titulo.innerText = `Estados — ${PAISES_AMERICAS[iso] ?? iso}`;
        btnVoltar.classList.remove('hidden');
        resetarTabelas('Selecione um estado.');

        // Reset instantâneo do zoom antes de desenhar (evita transform residual).
        svg.interrupt();
        svg.call(zoom.transform, d3.zoomIdentity);

        // Carrega estados deste país.
        let geoEstados;
        try {
            const res = await fetch(`/geojson/states/${iso}.geo.json`);
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            geoEstados = await res.json();
        } catch (err) {
            console.warn(`GeoJSON de estados não disponível para ${iso}:`, err);
            zoomParaFeature(feature, projectionPaises);
            resetarTabelas(`Sem dados de estados disponíveis para ${PAISES_AMERICAS[iso] ?? iso}.`);
            return;
        }

        if (!geoEstados.features || !geoEstados.features.length) {
            console.warn(`GeoJSON sem features para ${iso}.`);
            resetarTabelas(`Sem estados cadastrados para ${PAISES_AMERICAS[iso] ?? iso}.`);
            return;
        }

        const margem = 20;
        const projecaoPais = d3.geoMercator()
            .fitExtent([[margem, margem], [WIDTH - margem, HEIGHT - margem]], geoEstados);
        const pathEstados = d3.geoPath(projecaoPais);

        // Esconde camada de países e limpa qualquer estado anterior.
        camadaPaises.style('display', 'none');
        camadaEstados.selectAll('*').remove();

        let selecionado = null;

        camadaEstados.selectAll('path.map-estado')
            .data(geoEstados.features)
            .join('path')
            .attr('class', 'map-estado')
            .attr('d', pathEstados)
            .on('mousemove', (ev, f) => {
                const props = f.properties || {};
                const nome = props.name || props.NAME_1 || props.nome || '';
                showTooltip(nome, ev);
            })
            .on('mouseleave', hideTooltip)
            .on('click', function (ev, f) {
                if (selecionado) selecionado.classList.remove('selected');
                this.classList.add('selected');
                selecionado = this;

                const props = f.properties || {};
                const nome = props.name || props.NAME_1 || props.nome || '';
                const sigla = props.iso_3166_2 || props.sigla || props.HASC_1 || props.uf || '';
                const refUf = sigla || nome;
                carregarProjetos(iso, refUf);
            });
    };

    const voltarParaAmericas = () => {
        svg.interrupt();
        svg.call(zoom.transform, d3.zoomIdentity);
        camadaEstados.selectAll('*').remove();
        camadaPaises.style('display', null);
        titulo.innerText = 'Selecione um país';
        btnVoltar.classList.add('hidden');
        resetarTabelas();
    };

    btnVoltar.addEventListener('click', voltarParaAmericas);

    try {
        await carregarPaises();
    } catch (err) {
        console.error('Falha ao carregar países:', err);
    }
});
</script>
@endpush
