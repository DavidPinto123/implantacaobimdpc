<?php

namespace App\Filament\Pages;

use App\Models\Acompanhamento;
use App\Models\Projeto;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class MapaGeral extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-map';

    protected string $view = 'filament.pages.mapa-geral';

    protected static UnitEnum|string|null $navigationGroup = 'Outros';

    protected static ?string $navigationParentItem = 'Mapas';

    public function getTitle(): string
    {
        return 'Mapa de Unidades Próprias e Franquiadas - Pipeline 2025';
    }

    /*
    public function getViewData(): array
    {
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

        $dadosPorPaisEstadoETipo = Acompanhamento::select('pais', 'estado', 'tipo', DB::raw('count(*) as total'))
            ->where('pipeline', 'PIPE 2025')
            ->groupBy('pais', 'estado', 'tipo')
            ->get();

        $dadosDosPaises = [];
        $dadosDosEstados = [];

        foreach ($dadosPorPaisEstadoETipo as $dado) {
            $paisOriginal = strtoupper($dado->pais);

            if (!isset($mapaPaisesIso[$paisOriginal])) {
                continue;
            }

            $codigoIso = $mapaPaisesIso[$paisOriginal];

            // Por país: acumula totais por tipo
            if (!isset($dadosDosPaises[$codigoIso])) {
                $dadosDosPaises[$codigoIso] = [];
            }
            $dadosDosPaises[$codigoIso][$dado->tipo] =
                ($dadosDosPaises[$codigoIso][$dado->tipo] ?? 0) + $dado->total;

            // Se for Brasil, organiza também por estado
            if ($codigoIso === 'BR') {
                $siglaEstado = strtoupper($dado->estado);
                if (!isset($dadosDosEstados[$siglaEstado])) {
                    $dadosDosEstados[$siglaEstado] = [];
                }
                $dadosDosEstados[$siglaEstado][$dado->tipo] =
                    ($dadosDosEstados[$siglaEstado][$dado->tipo] ?? 0) + $dado->total;
            }
        }

        return [
            'dadosDosPaises' => $dadosDosPaises,
            'dadosDosEstados' => $dadosDosEstados,
        ];
    }*/
    public function getViewData(): array
    {
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
            'ARGENTINA' => 'AR',
            'EL SALVADOR' => 'SV',
            'EQUADOR' => 'EC',
            'HONDURAS' => 'HN',
            'MÉXICO' => 'MX',
            'PORTUGAL' => 'PT',
            'URUGUAY' => 'UY',
            'PANAMA' => 'PA',
        ];

        $dadosPorPaisEstadoETipo = Projeto::select('pais_id', 'estado_id', 'tipo', DB::raw('count(*) as total'))
            ->with(['pais', 'estado'])
            ->where('pipeline', 'PIPE 2025')
            ->groupBy('pais_id', 'estado_id', 'tipo')
            ->get();

        $dadosDosPaises = [];
        $dadosDosEstados = [];
        $dadosDosOutrosPaises = [];

        foreach ($dadosPorPaisEstadoETipo as $dado) {
            $paisOriginal = strtoupper($dado->pais->nome ?? '');
            $codigoIso = $mapaPaisesIso[$paisOriginal] ?? null;
            if (! $codigoIso) {
                continue;
            }

            $tipo = $dado->tipo ?? 'Obras';

            // Países
            $dadosDosPaises[$codigoIso][$tipo] = ($dadosDosPaises[$codigoIso][$tipo] ?? 0) + $dado->total;

            if ($codigoIso === 'BR') {
                $estadoNome = $dado->estado->nome ?? '';
                $mapaSigla = [
                    'Acre' => 'AC',
                    'Alagoas' => 'AL',
                    'Amapá' => 'AP',
                    'Amazonas' => 'AM',
                    'Bahia' => 'BA',
                    'Ceará' => 'CE',
                    'Distrito Federal' => 'DF',
                    'Espírito Santo' => 'ES',
                    'Goiás' => 'GO',
                    'Maranhão' => 'MA',
                    'Mato Grosso' => 'MT',
                    'Mato Grosso do Sul' => 'MS',
                    'Minas Gerais' => 'MG',
                    'Pará' => 'PA',
                    'Paraíba' => 'PB',
                    'Paraná' => 'PR',
                    'Pernambuco' => 'PE',
                    'Piauí' => 'PI',
                    'Rio de Janeiro' => 'RJ',
                    'Rio Grande do Norte' => 'RN',
                    'Rio Grande do Sul' => 'RS',
                    'Rondônia' => 'RO',
                    'Roraima' => 'RR',
                    'Santa Catarina' => 'SC',
                    'São Paulo' => 'SP',
                    'Sergipe' => 'SE',
                    'Tocantins' => 'TO',
                ];
                $siglaEstado = $mapaSigla[$estadoNome] ?? $estadoNome;

                $dadosDosEstados[$siglaEstado][$tipo] = ($dadosDosEstados[$siglaEstado][$tipo] ?? 0) + $dado->total;
            } else {
                $estadoNome = $dado->estado->nome ?? '';
                $dadosDosOutrosPaises[$paisOriginal][$estadoNome][$tipo] =
                    ($dadosDosOutrosPaises[$paisOriginal][$estadoNome][$tipo] ?? 0) + $dado->total;
            }
        }

        return [
            'dadosDosPaises' => $dadosDosPaises,
            'dadosDosEstados' => $dadosDosEstados,
            'dadosDosOutrosPaises' => $dadosDosOutrosPaises,
        ];
    }

    public static function canAccess(): bool
    {

        return auth()->user()?->can('View:MapaGeral');
    }

    public static function shouldRegisterNavigation(): bool
    {

        return auth()->user()?->can('View:MapaGeral');
    }
}
