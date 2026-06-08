<?php

namespace Database\Seeders;

use App\Models\Estado;
use App\Models\Pais;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class EstadosAmericasSeeder extends Seeder
{
    public function run(): void
    {
        $dir = public_path('geojson/states');

        if (! is_dir($dir)) {
            $this->command->error("Pasta não encontrada: {$dir}");

            return;
        }

        $arquivos = File::glob($dir.'/*.geo.json');

        $totalEstados = 0;
        $totalPaises = 0;

        foreach ($arquivos as $arquivo) {
            $iso = strtoupper(pathinfo($arquivo, PATHINFO_FILENAME));
            // Ex.: "BR.geo" → "BR".
            $iso = preg_replace('/\.geo$/i', '', $iso);

            $pais = Pais::where('iso', $iso)->first();
            if (! $pais) {
                $this->command->warn("Pulando {$iso}: país não encontrado em `pais` (rode PaisesAmericasSeeder primeiro).");

                continue;
            }

            $json = json_decode(File::get($arquivo), true);
            if (! is_array($json) || ! isset($json['features'])) {
                $this->command->warn("Pulando {$iso}: GeoJSON inválido.");

                continue;
            }

            $count = 0;
            foreach ($json['features'] as $feature) {
                $props = $feature['properties'] ?? [];
                $nome = $this->resolverNome($props);
                if (! $nome) {
                    continue;
                }

                $iso2 = $props['iso_3166_2'] ?? null;
                $sigla = $this->resolverSigla($props);

                // A coluna `uf` é varchar(2) (nascida para o Brasil). Só popula quando couber.
                $ufBanco = ($sigla !== null && mb_strlen($sigla) <= 2) ? $sigla : null;

                $existente = $this->localizarExistente($pais->id, $iso2, $ufBanco, $nome);

                $atributos = [
                    'pais_id' => $pais->id,
                    'nome' => $nome,
                    'uf' => $ufBanco,
                    'iso_3166_2' => $iso2,
                ];

                if ($existente) {
                    // Não sobrescreve `nome`/`uf` já populados (preserva o que veio de seeders/migrations BR existentes).
                    $existente->fill(array_filter($atributos, fn ($v, $k) => $existente->{$k} === null && $v !== null, ARRAY_FILTER_USE_BOTH))->save();
                } else {
                    Estado::create($atributos);
                }

                $count++;
            }

            $this->command->info(" {$iso}: {$count} estados/províncias processados.");
            $totalEstados += $count;
            $totalPaises++;
        }

        $this->command->info("Concluído: {$totalEstados} estados em {$totalPaises} países.");
    }

    private function resolverNome(array $props): ?string
    {
        $candidatos = [
            $props['name'] ?? null,
            $props['nome'] ?? null,
            $props['name_local'] ?? null,
            $props['NAME_1'] ?? null,
        ];

        foreach ($candidatos as $c) {
            if (is_string($c) && trim($c) !== '') {
                return trim($c);
            }
        }

        return null;
    }

    private function resolverSigla(array $props): ?string
    {
        // Para Brasil o brazil-states.geo.json traz `sigla` (UF).
        if (! empty($props['sigla'])) {
            return strtoupper($props['sigla']);
        }

        // Para os demais, usa a parte após `-` em iso_3166_2 (ex: "AR-B" → "B"),
        // ou a parte após `.` em code_hasc (ex: "AR.SC" → "SC").
        $iso = $props['iso_3166_2'] ?? null;
        if (is_string($iso) && str_contains($iso, '-')) {
            return strtoupper(substr($iso, strpos($iso, '-') + 1));
        }

        $hasc = $props['code_hasc'] ?? null;
        if (is_string($hasc) && str_contains($hasc, '.')) {
            return strtoupper(substr($hasc, strpos($hasc, '.') + 1));
        }

        return null;
    }

    private function localizarExistente(int $paisId, ?string $iso2, ?string $sigla, string $nome): ?Estado
    {
        if ($iso2) {
            $e = Estado::where('pais_id', $paisId)->where('iso_3166_2', $iso2)->first();
            if ($e) {
                return $e;
            }
        }

        if ($sigla) {
            $e = Estado::where('pais_id', $paisId)->where('uf', $sigla)->first();
            if ($e) {
                return $e;
            }
        }

        return Estado::where('pais_id', $paisId)->where('nome', $nome)->first();
    }
}
