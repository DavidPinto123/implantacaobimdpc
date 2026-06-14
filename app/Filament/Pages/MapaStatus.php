<?php

namespace App\Filament\Pages;

use App\Models\Acompanhamento;
use App\Models\Projeto;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class MapaStatus extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-map';

    protected string $view = 'filament.pages.mapa-status';

    protected static UnitEnum|string|null $navigationGroup = 'Mapas';

    public function getTitle(): string
    {
        return 'Mapa de Unidades por Status - Pipeline 2025';
    }

    /*
    public function getViewData(): array
    {
        $dadosPorEstadoEStatus = Acompanhamento::select('estado', 'status', DB::raw('count(*) as total'))
            ->whereIn('status', ['EM PROCESSO', 'IMPLANTAÇÃO', 'INAUGURADA', 'OBRAS'])
              ->where('pipeline', 'PIPE 2025')
            ->groupBy('estado', 'status')
            ->get();

        $dadosOrganizados = [];
        foreach ($dadosPorEstadoEStatus as $dado) {
            $dadosOrganizados[$dado->estado][$dado->status] = $dado->total;
        }

        return [
            'dadosDosEstados' => $dadosOrganizados
        ];
    }
    */
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

        $dadosPorPaisEstadoEStatus = Projeto::select('pais_id', 'estado_id', 'status', DB::raw('count(*) as total'))
            ->with(['pais', 'estado'])
            ->where('pipeline', 'PIPE 2025')
            ->whereIn('status', ['Inaugurada', 'Obras', 'Em processo', 'Stand-by', 'Cancelada'])
            ->groupBy('pais_id', 'estado_id', 'status')
            ->get();

        $dadosDosPaises = [];
        $dadosDosEstados = [];
        $dadosDosOutrosPaises = [];

        foreach ($dadosPorPaisEstadoEStatus as $dado) {
            $paisOriginal = strtoupper($dado->pais?->nome ?? 'Desconhecido');
            $estadoNome = $dado->estado?->nome ?? 'Desconhecido';
            // dd($estadoNome);

            if (! isset($mapaPaisesIso[$paisOriginal])) {
                continue;
            }

            $codigoIso = $mapaPaisesIso[$paisOriginal];

            // Por país
            if (! isset($dadosDosPaises[$codigoIso])) {
                $dadosDosPaises[$codigoIso] = [];
            }
            $dadosDosPaises[$codigoIso][$dado->status] =
                ($dadosDosPaises[$codigoIso][$dado->status] ?? 0) + $dado->total;

            // Brasil: por estado
            if ($codigoIso === 'BR') {
                $siglaEstado = match ($estadoNome) {
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
                    default => '??',
                };

                if (! isset($dadosDosEstados[$siglaEstado])) {
                    $dadosDosEstados[$siglaEstado] = [];
                }
                $dadosDosEstados[$siglaEstado][$dado->status] =
                    ($dadosDosEstados[$siglaEstado][$dado->status] ?? 0) + $dado->total;
            } else {
                // Outros países
                if (! isset($dadosDosOutrosPaises[$paisOriginal])) {
                    $dadosDosOutrosPaises[$paisOriginal] = [];
                }
                if (! isset($dadosDosOutrosPaises[$paisOriginal][$estadoNome])) {
                    $dadosDosOutrosPaises[$paisOriginal][$estadoNome] = [];
                }
                $dadosDosOutrosPaises[$paisOriginal][$estadoNome][$dado->status] =
                    ($dadosDosOutrosPaises[$paisOriginal][$estadoNome][$dado->status] ?? 0) + $dado->total;
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

        return auth()->user()?->can('View:MapaStatus');
    }

    public static function shouldRegisterNavigation(): bool
    {

        return auth()->user()?->can('View:MapaStatus');
    }
}
