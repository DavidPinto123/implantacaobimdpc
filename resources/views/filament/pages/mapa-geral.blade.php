<x-filament::page>
    <div id="map" style="height: 650px;"></div>
</x-filament::page>

@push('styles')
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        crossorigin=""
    />
    <style>
      	#map {
      		z-index:1;
      	}
        .info.legend {
          	font-family: "Inter", sans-serif;
          	font-weight: 400;
  			font-style: normal;
          	font-size: 14px;
            background: white;
            padding: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.2);
            border-radius: 5px;
            line-height: 1.2;
        }

        .info.legend i {
            width: 18px;
            height: 18px;
            margin-right: 8px;
            opacity: 0.7;
            display: inline-block;
        }

        .pais-tooltip {
          	font-family: "Inter", sans-serif;
          	font-weight: 400;
  			font-style: normal;
            color: #000;
        }
    </style>
@endpush

@push('scripts')
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
    document.addEventListener("DOMContentLoaded", async function () {
        // Verifique se o elemento existe
        const mapElement = document.getElementById('map');
        if (!mapElement) {
            console.error('Elemento do mapa não encontrado!');
            return;
        }

        const map = L.map('map').setView([-05, -30], 3);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        const siglaParaNomeEstado = {
            "AC": "Acre", "AL": "Alagoas", "AP": "Amapá", "AM": "Amazonas",
            "BA": "Bahia", "CE": "Ceará", "DF": "Distrito Federal", "ES": "Espírito Santo",
            "GO": "Goiás", "MA": "Maranhão", "MT": "Mato Grosso", "MS": "Mato Grosso do Sul",
            "MG": "Minas Gerais", "PA": "Pará", "PB": "Paraíba", "PR": "Paraná", 
            "PE": "Pernambuco", "PI": "Piauí", "RJ": "Rio de Janeiro", "RN": "Rio Grande do Norte", 
            "RS": "Rio Grande do Sul", "RO": "Rondônia", "RR": "Roraima", "SC": "Santa Catarina", 
            "SP": "São Paulo", "SE": "Sergipe", "TO": "Tocantins"
        };

        
        const dados = @json($dadosDosEstados);
        const dadosDosPaises = @json($dadosDosPaises);
        const dadosDosOutrosPaises = @json($dadosDosOutrosPaises);

        // URL do arquivo GeoJSON dos estados do Brasil
        const geojsonUrl = '/geojson/brazil-states.geo.json';

        const response = await fetch(geojsonUrl);
        const geojson = await response.json();
      
      	    function getColor(value) {
              return value === 0 ? '#FFEDA0' :
              		 value > 100 ? '#800026' :
                     value > 50  ? '#BD0026' :
                     value > 20  ? '#E31A1C' :
                     value > 10  ? '#FC4E2A' :
                                   '#FACC6B' ;
    		}

        // Exibir os estados no mapa
        L.geoJSON(geojson, {
            onEachFeature: function (feature, layer) {
                const estadoGeoJson = feature.properties.name;

                // Encontrar a sigla do estado com base no nome
                const estadoSigla = Object.keys(siglaParaNomeEstado).find(sigla => siglaParaNomeEstado[sigla] === estadoGeoJson);
                
                // Obter os dados correspondentes ao estado
                const info = dados[estadoSigla];

                // Definir o popup com as informações de registros
                const propria = info?.['Própria'] || 0;
                const franquia = info?.['Franquia'] || 0;
                const total = propria + franquia;

                const popup = `
                    <b>${estadoGeoJson}</b><br>
                    Próprias: ${propria}<br>
                    Franquias: ${franquia}<br>
                    <strong>Total: ${total}</strong>
                `;

                // Adicionar o popup e definir estilo
                layer.bindPopup(popup);
                layer.setStyle({ fillColor: getColor(total),
                    fillOpacity: 1,
                    color: "#FFFFFF",
                    weight: 1});
              if (estadoSigla) {
        const centro = layer.getBounds().getCenter();

        const tooltip = L.tooltip({
            permanent: true,
            direction: 'center',
            className: 'sigla-tooltip'
        })
        .setContent(estadoSigla)
        .setLatLng(centro);

        tooltip.addTo(map);
    }
            }
        }).addTo(map);

        // Exibir os países no mapa
        const paisesParaExibir = ["Chile", "Colombia", "Guatemala", "Peru", "Dominican Republic", "Spain","Costa Rica", "Paraguay", "Argentina",
                                 "El Salvador", "Ecuador", "Honduras", "Mexico", "Portugal", "Uruguay", "Panama"];
        const nomeParaCodigoISO = {
            "Brazil": "BR", "Chile": "CL", "Colombia": "CO", "Costa Rica": "CR", 
            "Guatemala": "GT", "Paraguay": "PY", "Peru": "PE", "Dominican Republic": "DO", "Spain": "ES"
        };

        const coordenadasCentraisPersonalizadas = {
            "Chile": [-30.0, -71.0],
            "Spain": [40.0, -4.0]
        };

        const geojsonPaisUrl = '/geojson/countries.geo.json';
        const responsePais = await fetch(geojsonPaisUrl);
        const geojsonPais = await responsePais.json();

        L.geoJSON(geojsonPais, {
            filter: function (feature) {
                return paisesParaExibir.includes(feature.properties.name);
            },
            style: function (feature) {
                const nomePais = feature.properties.name;
                const codigoISO = nomeParaCodigoISO[nomePais];
                const contagem = dadosDosPaises[codigoISO] || {};
                const total = (contagem['Própria'] || 0) + (contagem['Franquia'] || 0);

                return {
                    fillColor: getColor(total),
                    fillOpacity: 1,
                    color: "#FFFFFF",
                    weight: 1
                };
            },
            onEachFeature: function (feature, layer) {
                const nomePais = feature.properties.name;
                const codigoISO = nomeParaCodigoISO[nomePais];
                const contagem = dadosDosPaises[codigoISO] || {};
                const propria = contagem['Própria'] || 0;
                const franquia = contagem['Franquia'] || 0;
                const total = propria + franquia;
              	const nomePaisOriginal = {
                    "Colombia": "COLÔMBIA",
                    "Dominican Republic": "REP. DOMINICANA",
                    "Spain": "ESPANHA",
                  	"Paraguay": "PARAGUAI",
                  	"Uruguay": "URUGUAI",
                  	"Ecuador": "EQUADOR",
                  	"Panama": "PANAMÁ",
                  	"Mexico": "MÉXICO",
                };

                const nomePaisNormalized = nomePaisOriginal[nomePais] || nomePais.toUpperCase();

                let estados = [];
                if (
                    nomePais !== "Brazil" &&
                    dadosDosOutrosPaises[nomePaisNormalized] &&
                    Object.keys(dadosDosOutrosPaises[nomePaisNormalized]).length > 0
                ) {
                    estados = Object.keys(dadosDosOutrosPaises[nomePaisNormalized])
                        .map(estado => `${estado}: ${dadosDosOutrosPaises[nomePaisNormalized][estado]}`);
                }
              	 
                 console.log(`nomePaisNormalized: ${nomePaisNormalized}`);
                 console.log(`dadosDosOutrosPaises[nomePaisNormalized]:, dadosDosOutrosPaises[nomePaisNormalized]`);

                const popup = `
                    <strong>${nomePaisNormalized}</strong><br>
                    Próprias: ${propria}<br>
                    Franquias: ${franquia}<br>
                    <strong>Total: ${total}</strong><br>
                    <strong>Estados:</strong><br>
                    ${estados.length ? estados.join("<br>") : "Nenhum estado disponível"}
                `;

                layer.bindPopup(popup);

                const centro1 = coordenadasCentraisPersonalizadas[nomePais] 
                    ? L.latLng(...coordenadasCentraisPersonalizadas[nomePais]) 
                    : layer.getBounds().getCenter();

                const tooltip = L.tooltip({
                    permanent: true,
                    direction: 'center',
                    className: 'estado-tooltip'
                })
                .setContent(nomePaisNormalized)
                .setLatLng(centro1);

                tooltip.addTo(map);
            }
        }).addTo(map);
      		// Criar a legenda baseada nas cores
            const legenda = L.control({ position: 'bottomright' });

            legenda.onAdd = function () {
                const div = L.DomUtil.create('div', 'info legend');
                const grades = [10, 20, 50, 100];

                div.innerHTML = '<h4 style="color: black;">Total de "Próprias" + "Franquias"</h4>';

                // Legenda para valor igual a 0
                div.innerHTML += `
                    <div style="display: flex; align-items: center; margin-bottom: 4px;">
                        <i style="background:${getColor(0)}; width: 18px; height: 18px; display: inline-block; margin-right: 8px;"></i>
                        <a style="color: black;">0</a>
                    </div>
                `;

                // Legendas para os outros intervalos
                for (let i = 0; i < grades.length; i++) {
                    const from = grades[i - 1] || 1;  // Começa de 1 para evitar o 0
                    const to = grades[i];

                    const color = getColor(from + 1); // +1 para garantir a cor correta do intervalo

                    div.innerHTML += `
                        <div style="display: flex; align-items: center; margin-bottom: 4px;">
                            <i style="background:${color}; width: 18px; height: 18px; display: inline-block; margin-right: 8px;"></i>
                            <a style="color: black;">
                                ${to ? `${from}–${to - 1}` : `${from}+`}
                            </a>
                        </div>
                    `;
                }

                return div;
            };
            legenda.addTo(map);
    });
</script>
@endpush