<?php

namespace App\Services\DadosAbertos;

use App\Models\Cidade;
use App\Models\Estado;
use App\Models\Pais;
use Illuminate\Support\Arr;
use JsonException;
use RuntimeException;

class LocalizacaoBrasilSyncService
{
    public const DATASET_PATH = 'resources/data/ibge/municipios.json';

    /**
     * @return array{paises: int, estados: int, cidades: int}
     */
    public function sync(): array
    {
        return $this->syncFromMunicipios($this->loadMunicipiosDataset());
    }

    /**
     * @param  array<int, array<string, mixed>>  $municipios
     * @return array{paises: int, estados: int, cidades: int}
     */
    public function syncFromMunicipios(array $municipios): array
    {
        $pais = Pais::query()->firstOrCreate(['nome' => 'Brasil']);
        $estados = [];
        $cidades = [];

        foreach ($municipios as $municipio) {
            $nomeCidade = trim((string) Arr::get($municipio, 'nome'));
            $nomeEstado = trim((string) (
                Arr::get($municipio, 'microrregiao.mesorregiao.UF.nome')
                ?? Arr::get($municipio, 'regiao-imediata.regiao-intermediaria.UF.nome')
            ));
            $uf = mb_strtoupper(trim((string) (
                Arr::get($municipio, 'microrregiao.mesorregiao.UF.sigla')
                ?? Arr::get($municipio, 'regiao-imediata.regiao-intermediaria.UF.sigla')
            )));

            if ($nomeCidade === '' || $nomeEstado === '' || $uf === '') {
                continue;
            }

            $estado = $estados[$uf] ??= Estado::query()->updateOrCreate(
                [
                    'pais_id' => $pais->id,
                    'nome' => $nomeEstado,
                ],
                [
                    'uf' => $uf,
                ],
            );

            Cidade::query()->updateOrCreate([
                'estado_id' => $estado->id,
                'nome' => $nomeCidade,
            ]);

            $cidades[$estado->id.'|'.$nomeCidade] = true;
        }

        return [
            'paises' => 1,
            'estados' => count($estados),
            'cidades' => count($cidades),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadMunicipiosDataset(): array
    {
        $path = base_path(self::DATASET_PATH);

        if (! is_file($path)) {
            throw new RuntimeException("Dataset do IBGE não encontrado em [{$path}].");
        }

        try {
            $data = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Dataset do IBGE contém JSON inválido.', previous: $exception);
        }

        if (! is_array($data)) {
            throw new RuntimeException('Dataset do IBGE não contém uma lista de municípios.');
        }

        return $data;
    }
}
