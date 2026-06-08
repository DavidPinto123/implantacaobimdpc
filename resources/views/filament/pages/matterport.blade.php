<x-filament::page>
    @php
        $matterports = collect([
  
  
            [
                'nome' => 'Moema - Av. Jamaris - Planalto Paulista - SP',
                'estado' => 'SP',
                'imagem' => 'images/matterport-moema.png',
                'link' => 'https://my.matterport.com/show/?m=sR7JXQCyi2L',
            ],
            [
                'nome' => 'Taguatinga - QSD 18 - Taguatinga Sul - DF',
                'estado' => 'DF',
                'imagem' => 'images/matterport-taguatinga.png',
                'link' => 'https://my.matterport.com/show/?m=esujy73jUrp',
            ],
            [
                'nome' => 'Carrefour Aricanduva - Aricanduva - SP',
                'estado' => 'SP',
                'imagem' => 'images/matterport-aricanduva.png',
                'link' => 'https://my.matterport.com/show/?m=xM6qYjtsdie',
            ],
            [
                'nome' => 'Dom Atacadista Macaé - RJ',
                'estado' => 'RJ',
                'imagem' => 'images/matterport-dom-macae.png',
                'link' => 'https://my.matterport.com/show/?m=uGvWNM56E4u',
            ],
  			[
                'nome' => 'Asa Norte QD 509 (fachada) - Asa Norte, Brasília - DF',
                'estado' => 'DF',
                'imagem' => 'images/matterport-asa-norte-fachada.png',
                'link' => 'https://my.matterport.com/show/?m=whTbsa2SSWJ',
            ],
            [
                'nome' => 'Asa Norte QD 509 (andares) - Asa Norte, Brasília - DF',
                'estado' => 'DF',
                'imagem' => 'images/matterport-asa-norte-andares.png',
                'link' => 'https://my.matterport.com/show/?m=8TKKrhE3UT5',
            ],
            [
                'nome' => 'Asa Norte QD 509 (estacionamento/subsolo) - Asa Norte, Brasília - DF',
                'estado' => 'DF',
                'imagem' => 'images/matterport-asa-norte-est.png',
                'link' => 'https://my.matterport.com/show/?m=uE8L5x2BE6L',
            ],	
            [
                'nome' => 'Comercial Buritis - Belo Horizonte - MG',
                'estado' => 'MG',
                'imagem' => 'images/matterport-buritis.png',
                'link' => 'https://my.matterport.com/show/?m=HqRsgGp5Ge6',
            ],
  
            [
                'nome' => 'Paulista Aurora - Paulista Centro - PE',
                'estado' => 'PE',
                'imagem' => 'images/matterport-aurora.png',
                'link' => 'https://my.matterport.com/show/?m=XsmtgGLiWvK',
            ], 
  
            [
                'nome' => 'Anjo da Guarda - São Luis - MA',
                'estado' => 'MA',
                'imagem' => 'images/matterport-anjo.png',
                'link' => 'https://my.matterport.com/show/?m=xyYkWY3p8zq',
            ], 
            [
                'nome' => 'Leme Estacionamento/Casa - Leme - RJ',
                'estado' => 'RJ',
                'imagem' => 'images/matterport-leme.png',
                'link' => 'https://my.matterport.com/show/?m=rmcStKvYyjz',
            ],
  
            [
                'nome' => 'Flamengo - Rio de Janeiro - RJ',
                'estado' => 'RJ',
                'imagem' => 'images/matterport-flamengo.png',
                'link' => 'https://my.matterport.com/show/?m=h362ABVf8qv',
            ],
  
            [
                'nome' => 'Espaço para Podcast - São Paulo - SP',
                'estado' => 'SP',
                'imagem' => 'images/matterport-podcast.png',
                'link' => 'https://my.matterport.com/show/?m=dagEDuG8JoU',
            ],
            [
                'nome' => 'Lojas Americanas - Nilópolis - RJ',
                'estado' => 'RJ',
                'imagem' => 'images/matterport-lojas-americanas-nilopolis.png',
                'link' => 'https://my.matterport.com/show/?m=ntjTQFATSuU',
            ],
            [
                'nome' => 'Macromix Esteio - Esteio - RS',
                'estado' => 'RS',
                'imagem' => 'images/matterport-macromix-esteio.png',
                'link' => 'https://my.matterport.com/show/?m=PejZdRvWWBQ',
            ],
  
             [
                'nome' => 'Manaus Compensa - Manaus - AM',
                'estado' => 'AM',
                'imagem' => 'images/matterport-compensa-manaus.png',
                'link' => 'https://my.matterport.com/show/?m=2mhBnTkXQ5m',
            ],
            [
                'nome' => 'Shopping Grande Circular - Manaus - AM',
                'estado' => 'AM',
                'imagem' => 'images/matterport-grande-circular-manaus.png',
                'link' => 'https://my.matterport.com/show/?m=d8KRRZfkRC8',
            ],
  
            [
                'nome' => 'Manaus Shopping São Jose - Manaus - AM',
                'estado' => 'AM',
                'imagem' => 'images/matterport-shopping-sao-jose.png',
                'link' => 'https://my.matterport.com/show/?m=2XCyiL4mVvR',
            ],
  
            [
                'nome' => 'Catete - Rio de Janeiro - RJ',
                'estado' => 'RJ',
                'imagem' => 'images/matterport-catete.png',
                'link' => 'https://my.matterport.com/show/?m=Czvmeiw9dja',
            ],
  
            [
                'nome' => 'Vila Madalena Península - São Paulo - SP',
                'estado' => 'SP',
                'imagem' => 'images/matterport-vila-madalena-peninsula.png',
                'link' => 'https://my.matterport.com/show/?m=Ta6fXakMu88',
            ],
  
            [
                'nome' => 'Lopes Jd Roberto Supermercados - Osasco - SP',
                'estado' => 'SP',
                'imagem' => 'images/matterport-lopes-supermercados.png',
                'link' => 'https://my.matterport.com/show/?m=FbSLYZwpKoz',
            ],
  
            [
                'nome' => 'Empório Bahamas - Uberlândia - MG',
                'estado' => 'MG',
                'imagem' => 'images/matterport-emporio-bahamas-uberlandia.png',
                'link' => 'https://my.matterport.com/show/?m=6yKbSyHZuKv',
            ],
            [
                'nome' => 'Estrada do Barro Vermelho - Rocha Miranda - RJ',
                'estado' => 'RJ',
                'imagem' => 'images/matterport-estrada-bairro-vermelho-rio-de-janeiro.png',
                'link' => 'https://my.matterport.com/show/?m=MPhtGDdyBFh',
            ],
            [
                'nome' => 'Bahamas Mix Uberada - Uberaba - MG',
                'estado' => 'MG',
                'imagem' => 'images/matterport-bahamas-uberaba.png',
                'link' => 'https://my.matterport.com/show/?m=z1eLxS9U329',
            ],
            [
                'nome' => 'Cidade da Moda - Nova Iguaçu - RJ',
                'estado' => 'RJ',
                'imagem' => 'images/matterport-cidade-moda-nova-iguacu.png',
                'link' => 'https://my.matterport.com/show/?m=PKuBwtdZtbo',
            ],
            [
                'nome' => 'Rua Maria Antônio - São Paulo - SP',
                'estado' => 'SP',
                'imagem' => 'images/matterport-maria-antonio.png',
                'link' => 'https://my.matterport.com/show/?m=Jfv72CPD7Mw',
            ],            
  			[
                'nome' => 'Atacadão Pacaembu (Fachada) | São Paulo - SP',
                'estado' => 'SP',
                'imagem' => 'images/matterport-fachada-atacadao.png',
                'link' => 'https://my.matterport.com/show/?m=7JUpANdJdwf',
            ],
            [
                'nome' => 'Atacadão Pacaembu (Estoque) | São Paulo - SP',
                'estado' => 'SP',
                'imagem' => 'images/matterport-estoque-atacadao.png',
                'link' => 'https://my.matterport.com/show/?m=TQ5WXNhrpFT',
            ],


  
            [
                'nome' => "Sam's Clube Jabaquara - São Paulo - SP",
                'estado' => 'SP',
                'imagem' => 'images/matterport-sams-club-jabaquaraaa.png',
                'link' => 'https://my.matterport.com/show/?m=Dr2rPUgJ6yT',
            ],
            [
                'nome' => 'Itaim Paulista - São Paulo - SP',
                'estado' => 'SP',
                'imagem' => 'images/matterport-itaim-paulista.png',
                'link' => 'https://my.matterport.com/show/?m=F9JAtzoMRJc',
            ],
            [
                'nome' => 'Bio Ritmo - Paulista - São Paulo - SP',
                'estado' => 'SP',
                'imagem' => 'images/matterport-bio-ritmo.png',
                'link' => 'https://my.matterport.com/show/?m=RoC7UdpNZ27',
            ],













  
  





        ]);

        $busca = request('busca');
        $estado = request('filtro_estado');

        $filtrados = $matterports->filter(function ($item) use ($busca, $estado) {
            return (! $busca || str_contains(strtolower($item['nome']), strtolower($busca)))
                && (! $estado || $item['estado'] === $estado);
        });
    @endphp
  
  	<style>
      /* Placeholder claro */
      #busca::placeholder {
        color: rgba(107, 114, 128, 1); /* cinza-500 */
      }
      /* Placeholder modo escuro */
      .dark #busca::placeholder {
        color: rgba(255, 255, 255, 1) !important;
        --tw-placeholder-opacity: 1 !important;
      }
    </style>

      <div class="flex flex-wrap items-end justify-end gap-4 mb-6">
        <form method="GET" id="form-filtro" class="flex flex-wrap gap-4 items-end">
            <div class="flex flex-col">
                <label for="busca" class="text-sm font-medium text-gray-700 dark:text-white">Buscar</label>
                <input
                    id="busca"
                    type="text"
                    name="busca"
                    placeholder="Buscar por nome ou cidade"
                    value="{{ request('busca') }}"
                    class="filament-input w-64 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                />
            </div>

            <div class="flex flex-col">
                <label for="filtro_estado" class="text-sm font-medium text-gray-700 dark:text-white">Estado</label>
                <select
                    id="filtro_estado"
                    name="filtro_estado"
                    class="filament-input w-48 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                >
                    <option value="">Todos os estados</option>
                    <option value="AM" @selected(request('filtro_estado') == 'AM')>Amazonas</option>
                    <option value="SP" @selected(request('filtro_estado') == 'SP')>São Paulo</option>
                    <option value="RJ" @selected(request('filtro_estado') == 'RJ')>Rio de Janeiro</option>
                    <option value="MG" @selected(request('filtro_estado') == 'MG')>Minas Gerais</option>
                    <option value="RS" @selected(request('filtro_estado') == 'RS')>Rio Grande do Sul</option>
                    <option value="MA" @selected(request('filtro_estado') == 'MA')>Maranhão</option>
                    <option value="PE" @selected(request('filtro_estado') == 'PE')>Pernambuco</option>
                    <option value="DF" @selected(request('filtro_estado') == 'DF')>Distrito Federal</option>
                </select>
            </div>

            <button type="submit" class="filament-button h-10 rounded-lg hidden">
                Filtrar
            </button>
        </form>

        @if(request()->has('busca') || request()->has('filtro_estado'))
            <a
                href="{{ route('filament.admin.pages.matterport') }}"
                class="filament-button filament-button-size-sm filament-button-color-secondary whitespace-nowrap"
                style="height: 38px; line-height: 38px;"
                role="button"
                aria-label="Limpar filtros"
            >
                Limpar filtros
            </a>
        @endif
    </div>



    @if ($filtrados->isEmpty())
    <p class="text-gray-500 text-center">Nenhum resultado encontrado.</p>
@else
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach ($filtrados->values() as $index => $item)
            <div class="text-center flex flex-col justify-between h-full">
                <h2 class="text-lg font-semibold mb-2" style="min-height: 3rem;">{{ $item['nome'] }}</h2>
                <a href="{{ $item['link'] }}" target="_blank">
                    <img src="{{ asset($item['imagem']) }}" alt="{{ $item['nome'] }}" style="height: 250px; width: 100%; object-fit: cover;" class="rounded-xl shadow-md transition hover:scale-105">
                </a>
            </div>

            {{-- Quebra de linha após cada grupo de 3 --}}
            @if (($index + 1) % 3 === 0 && $index + 1 < $filtrados->count())
                </div>
                <hr class="my-6 border-t border-gray-300 dark:border-gray-700">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @endif
        @endforeach
    </div>
@endif


    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('form-filtro');
            const buscaInput = document.getElementById('busca');
            const estadoSelect = document.getElementById('filtro_estado');

            estadoSelect.addEventListener('change', () => {
                form.submit();
            });

            let debounceTimeout;
            buscaInput.addEventListener('input', () => {
                clearTimeout(debounceTimeout);
                debounceTimeout = setTimeout(() => {
                    form.submit();
                }, 500);
            });
        });
    </script>
</x-filament::page>
