<?php

namespace App\Services;

use App\Filament\Resources\Obras\ObrasResource;
use App\Models\Estado;
use App\Models\Etapa;
use App\Models\Pais;
use App\Models\Projeto;
use Illuminate\Support\Collection;

class ProjetosPorLocalidadeService
{
    /**
     * Busca projetos filtrados por país (ISO-2) e estado (sigla, ISO 3166-2 ou nome).
     *
     * Retorna um array com 4 coleções: prospeccao, assinatura, projetos, obra.
     */
    public function buscar(?string $paisIso, ?string $estadoRef): array
    {
        $estado = $this->resolverEstado($paisIso, $estadoRef);

        if (! $estado) {
            return $this->respostaVazia();
        }

        $etapaProspeccao = Etapa::where('nome', 'Prospecção')->first();

        $prospeccao = $etapaProspeccao
            ? Projeto::with(['estado', 'cidade', 'obras'])
                ->where('estado_id', $estado->id)
                ->whereHas('etapas', fn ($q) => $q->where('etapa_id', $etapaProspeccao->id))
                ->get()
            : collect();

        $assinatura = Projeto::with(['estado', 'cidade', 'obras'])
            ->where('estado_id', $estado->id)
            ->where('status_contrato', 'ASSINADO')
            ->get();

        $projetos = Projeto::with(['estado', 'cidade', 'obras'])
            ->where('estado_id', $estado->id)
            ->where('status', 'Em processo')
            ->get();

        $obra = Projeto::with(['estado', 'cidade', 'obras'])
            ->where('estado_id', $estado->id)
            ->where('status', 'Obras')
            ->get();

        return [
            'prospeccao' => $this->anexarObraUrl($prospeccao),
            'assinatura' => $this->anexarObraUrl($assinatura),
            'projetos' => $this->anexarObraUrl($projetos),
            'obra' => $this->anexarObraUrl($obra),
        ];
    }

    private function anexarObraUrl(Collection $projetos): Collection
    {
        return $projetos->map(function (Projeto $p) {
            $obra = $p->obras->first();
            $arr = $p->toArray();
            $arr['obra_id'] = $obra?->id;
            $arr['obra_url'] = $obra ? ObrasResource::getUrl('view', ['record' => $obra->id]) : null;

            return $arr;
        });
    }

    private function resolverEstado(?string $paisIso, ?string $estadoRef): ?Estado
    {
        if (! $estadoRef) {
            return null;
        }

        $paisId = null;
        if ($paisIso) {
            $pais = Pais::where('iso', strtoupper($paisIso))->first();
            $paisId = $pais?->id;
        }

        $query = Estado::query();
        if ($paisId) {
            $query->where('pais_id', $paisId);
        }

        // Tenta na ordem: iso_3166_2, uf (sigla), nome.
        $estado = (clone $query)->where('iso_3166_2', $estadoRef)->first();
        if ($estado) {
            return $estado;
        }

        $estado = (clone $query)->where('uf', $estadoRef)->first();
        if ($estado) {
            return $estado;
        }

        return (clone $query)->where('nome', $estadoRef)->first();
    }

    private function respostaVazia(): array
    {
        return [
            'prospeccao' => collect(),
            'assinatura' => collect(),
            'projetos' => collect(),
            'obra' => collect(),
        ];
    }

    /**
     * Resolve sigla de UF brasileira para o nome completo (compat com a rota antiga).
     */
    public static function siglaBrParaNome(string $sigla): ?string
    {
        $mapa = [
            'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas',
            'BA' => 'Bahia', 'CE' => 'Ceará', 'DF' => 'Distrito Federal',
            'ES' => 'Espírito Santo', 'GO' => 'Goiás', 'MA' => 'Maranhão',
            'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais',
            'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná', 'PE' => 'Pernambuco',
            'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte',
            'RS' => 'Rio Grande do Sul', 'RO' => 'Rondônia', 'RR' => 'Roraima',
            'SC' => 'Santa Catarina', 'SP' => 'São Paulo', 'SE' => 'Sergipe',
            'TO' => 'Tocantins',
        ];

        return $mapa[strtoupper($sigla)] ?? null;
    }
}
