<?php

namespace App\Filament\Pages;

use App\Models\Acompanhamento;
use App\Models\Projeto;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class MapaObras extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-map';

    protected string $view = 'filament.pages.mapa-obras';

    protected static UnitEnum|string|null $navigationGroup = 'Mapas';

    public function getTitle(): string
    {
        return 'Mapa de Unidades em Obras - Pipeline 2025';
    }

    /*
    public function getViewData(): array
    {
        $dadosDosEstados = Acompanhamento::select('estado', DB::raw('count(*) as total'))
            ->where('status', 'OBRAS')
            ->where('pipeline', 'PIPE 2025')
            ->groupBy('estado')
            ->get()
            ->pluck('total', 'estado')
            ->toArray();

        return [
            'dadosDosEstados' => $dadosDosEstados,
        ];
    }
    */
    public function getViewData(): array
    {
        // Mapeamento de países para código ISO
        $mapaPaisesIso = [
            'BRASIL' => 'BR',
            'CHILE' => 'CL',
            'COLÔMBIA' => 'CO',
            'COSTA RICA' => 'CR',
            'GUATEMALA' => 'GT',
            'PARAGUAY' => 'PY',
            'PERU' => 'PE',
            'REP. DOMINICANA' => 'DO',
            'ESPANHA' => 'ES',
        ];

        // Mapeamento manual de estados brasileiros para siglas
        $mapaEstadosBR = [
            'São Paulo' => 'SP',
            'Rio de Janeiro' => 'RJ',
            'Minas Gerais' => 'MG',
            'Bahia' => 'BA',
            'Espírito Santo' => 'ES',
            'Paraná' => 'PR',
            'Santa Catarina' => 'SC',
            'Rio Grande do Sul' => 'RS',
            'Goiás' => 'GO',
            'Distrito Federal' => 'DF',
            'Mato Grosso' => 'MT',
            'Mato Grosso do Sul' => 'MS',
            'Amazonas' => 'AM',
            'Pará' => 'PA',
            'Rondônia' => 'RO',
            'Roraima' => 'RR',
            'Amapá' => 'AP',
            'Tocantins' => 'TO',
            'Pernambuco' => 'PE',
            'Ceará' => 'CE',
            'Paraíba' => 'PB',
            'Rio Grande do Norte' => 'RN',
            'Alagoas' => 'AL',
            'Sergipe' => 'SE',
            'Piauí' => 'PI',
            'Maranhão' => 'MA',
            'Acre' => 'AC',
            'Amazonas' => 'AM',
            'Roraima' => 'RR',
            'Amapá' => 'AP',
            'Tocantins' => 'TO',
        ];

        $projetos = Projeto::with(['pais', 'estado'])
            ->where('status', 'Obras')
            ->where('pipeline', 'PIPE 2025')
            ->get();

        $dadosDosPaises = [];
        $dadosDosEstados = [];
        $dadosDosOutrosPaises = [];

        foreach ($projetos as $projeto) {
            $paisOriginal = strtoupper($projeto->pais?->nome ?? 'Desconhecido');

            if (! isset($mapaPaisesIso[$paisOriginal])) {
                continue;
            }

            $codigoIso = $mapaPaisesIso[$paisOriginal];

            // Contagem por país
            if (! isset($dadosDosPaises[$codigoIso])) {
                $dadosDosPaises[$codigoIso] = [];
            }
            $dadosDosPaises[$codigoIso][$projeto->status] =
                ($dadosDosPaises[$codigoIso][$projeto->status] ?? 0) + 1;

            // Se for Brasil, organiza também por estado
            if ($codigoIso === 'BR') {
                $nomeEstado = $projeto->estado?->nome ?? 'Desconhecido';
                $siglaEstado = $mapaEstadosBR[$nomeEstado] ?? $nomeEstado;

                if (! isset($dadosDosEstados[$siglaEstado])) {
                    $dadosDosEstados[$siglaEstado] = [];
                }
                $dadosDosEstados[$siglaEstado][$projeto->status] =
                    ($dadosDosEstados[$siglaEstado][$projeto->status] ?? 0) + 1;
            } else {
                // Outros países organizando por estado/cidade se precisar
                $nomeEstado = $projeto->estado?->nome ?? 'Desconhecido';
                if (! isset($dadosDosOutrosPaises[$paisOriginal])) {
                    $dadosDosOutrosPaises[$paisOriginal] = [];
                }
                if (! isset($dadosDosOutrosPaises[$paisOriginal][$nomeEstado])) {
                    $dadosDosOutrosPaises[$paisOriginal][$nomeEstado] = [];
                }
                $dadosDosOutrosPaises[$paisOriginal][$nomeEstado][$projeto->status] =
                    ($dadosDosOutrosPaises[$paisOriginal][$nomeEstado][$projeto->status] ?? 0) + 1;
            }
        }

        // dd($dadosDosEstados);

        return [
            'dadosDosPaises' => $dadosDosPaises,
            'dadosDosEstados' => $dadosDosEstados,
            'dadosDosOutrosPaises' => $dadosDosOutrosPaises,
        ];
    }

    public static function canAccess(): bool
    {

        return auth()->user()?->can('View:MapaObras');
    }

    public static function shouldRegisterNavigation(): bool
    {

        return auth()->user()?->can('View:MapaObras');
    }
}
