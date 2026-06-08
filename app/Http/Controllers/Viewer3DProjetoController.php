<?php

namespace App\Http\Controllers;

use App\Models\Projeto;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Viewer3DProjetoController extends Controller
{
    public function __invoke(Projeto $projeto)
    {
        Log::info('Viewer3D - link_docs recebido', [
            'projeto_id' => $projeto->id,
            'nova_sigla' => $projeto->nova_sigla,
            'link_docs' => $projeto->link_docs,
        ]);

        // 0) Sem link_docs, não tem o que fazer
        if (! $projeto->link_docs) {
            return view('filament.pages.viewer3d-projeto', [
                'projeto' => $projeto,
                'modelUrn' => null,
                'error' => 'Este projeto não possui link_docs configurado.',
                'debug' => ['motivo' => 'sem_link_docs'],
            ]);
        }

        $url = $projeto->link_docs;

        // ------------------------------------------------
        // 1) Extrair ACC projectId e folderId da URL do ACC
        // ------------------------------------------------
        $parts = parse_url($url);

        $path = $parts['path'] ?? '';
        $segments = array_values(array_filter(explode('/', trim($path, '/'))));

        $accProjectId = null;
        if (($i = array_search('projects', $segments, true)) !== false && isset($segments[$i + 1])) {
            $accProjectId = $segments[$i + 1]; // ex: "6feb8d34-ac22-4ab6-9baf-7894d3cba958"
        }

        $query = [];
        parse_str($parts['query'] ?? '', $query);

        // vem como urn%3Aadsk.wipprod%3Afs.folder%3Aco.XXXXX → precisamos decodificar
        $folderId = ! empty($query['folderUrn']) ? urldecode($query['folderUrn']) : null;

        if (! $accProjectId || ! $folderId) {
            Log::warning('Viewer3D - não foi possível extrair ACC projectId/folderId do link_docs', [
                'url' => $url,
                'parts' => $parts,
                'segments' => $segments,
                'query' => $query,
            ]);

            return view('filament.pages.viewer3d-projeto', [
                'projeto' => $projeto,
                'modelUrn' => null,
                'error' => 'Não foi possível identificar o projeto/pasta a partir do link_docs (formato de URL não reconhecido).',
                'debug' => [
                    'url' => $url,
                    'parts' => $parts,
                    'segments' => $segments,
                    'query' => $query,
                ],
            ]);
        }

        // ------------------------------------------------
        // 2) Converter ACC projectId em Data Management projectId
        //    ACC -> Data Management = prefixar com "b."
        // ------------------------------------------------
        $dmProjectId = Str::startsWith($accProjectId, 'b.')
            ? $accProjectId
            : 'b.'.$accProjectId;

        Log::info('Viewer3D - IDs extraídos/convertidos', [
            'projeto_id' => $projeto->id,
            'accProjectId' => $accProjectId,
            'dmProjectId' => $dmProjectId,
            'folderId' => $folderId,
        ]);

        // ------------------------------------------------
        // 3) Consultar arquivos da pasta no ACC (Data Management API)
        // ------------------------------------------------
        /** @var ApsDocsController $aps */
        $aps = App::make(ApsDocsController::class);

        // ApsDocsController::arquivos espera exatamente o projectId do Data Management
        $result = $aps->arquivos($dmProjectId, $folderId);
        $arquivos = collect($result['simplificados'] ?? []);

        // Se não veio nada em simplificados, vamos mostrar um modo "debug"
        if ($arquivos->isEmpty()) {
            Log::warning('Viewer3D - nenhum arquivo simplificado retornado pela API', [
                'projeto_id' => $projeto->id,
                'dmProjectId' => $dmProjectId,
                'folderId' => $folderId,
                'raw' => $result,
            ]);

            return view('filament.pages.viewer3d-projeto', [
                'projeto' => $projeto,
                'modelUrn' => null,
                'error' => 'Nenhum item foi retornado para esta pasta pela API do ACC. Verifique se esta pasta realmente contém os arquivos RVT ou se o link_docs aponta para a pasta correta.',
                'debug' => [
                    'url' => $url,
                    'accProjectId' => $accProjectId,
                    'dmProjectId' => $dmProjectId,
                    'folderId' => $folderId,
                    'prefix' => $projeto->nova_sigla,
                    'api_raw' => $result, // tudo que veio do ApsDocsController
                ],
            ]);
        }

        // ------------------------------------------------
        // 4) Buscar RVT pelo prefixo (nova_sigla)
        // ------------------------------------------------
        $prefix = trim($projeto->nova_sigla ?? '');

        // Se por algum motivo a nova_sigla estiver vazia, não vamos deixar passar tudo
        if ($prefix === '') {
            return view('filament.pages.viewer3d-projeto', [
                'projeto' => $projeto,
                'modelUrn' => null,
                'error' => 'Nova sigla vazia – não é possível localizar o arquivo RVT correspondente.',
                'debug' => [
                    'url' => $url,
                    'accProjectId' => $accProjectId,
                    'dmProjectId' => $dmProjectId,
                    'folderId' => $folderId,
                    'prefix' => $prefix,
                ],
            ]);
        }

        $match = $arquivos->first(function ($f) use ($prefix) {
            $name = $f['name'] ?? '';

            // posição do prefixo no nome (case-insensitive)
            $pos = stripos($name, $prefix);
            $ends = strtolower(substr($name, -4)) === '.rvt';

            Log::info('Viewer3D - teste de match RVT', [
                'fileName' => $name,
                'prefix' => $prefix,
                'stripos' => $pos,
                'ends_rvt' => $ends,
            ]);

            // TEM que começar com a nova_sigla (pos == 0) e terminar com .rvt
            return $pos === 0 && $ends;
        });

        // ------------------------------------------------
        // 5) Se não achar pelo prefixo, pega o 1º .rvt
        // ------------------------------------------------
        if (! $match) {
            Log::warning('Viewer3D - nenhum arquivo RVT com a nova_sigla encontrado nesta pasta', [
                'projeto_id' => $projeto->id,
                'dmProjectId' => $dmProjectId,
                'folderId' => $folderId,
                'prefix' => $prefix,
                'arquivos' => $arquivos->pluck('name')->toArray(),
            ]);

            return view('filament.pages.viewer3d-projeto', [
                'projeto' => $projeto,
                'modelUrn' => null,
                'error' => "Nenhum arquivo RVT cujo nome comece com '{$prefix}' foi encontrado nesta pasta.",
                'debug' => [
                    'url' => $url,
                    'accProjectId' => $accProjectId,
                    'dmProjectId' => $dmProjectId,
                    'folderId' => $folderId,
                    'prefix' => $prefix,
                    'arquivos' => $arquivos->pluck('name')->toArray(),
                ],
            ]);
        }
        /*
        //------------------------------------------------
        // 6) Se ainda não achar nenhum RVT → erro real
        //------------------------------------------------
        if (! $match) {
            Log::warning('Viewer3D - nenhum arquivo RVT encontrado nesta pasta', [
                'projeto_id'  => $projeto->id,
                'dmProjectId' => $dmProjectId,
                'folderId'    => $folderId,
                'arquivos'    => $arquivos->toArray(),
            ]);

            return view('filament.pages.viewer3d-projeto', [
                'projeto'  => $projeto,
                'modelUrn' => null,
                'error'    => 'Nenhum arquivo RVT foi encontrado nesta pasta.',
                'debug'    => [
                    'url'        => $url,
                    'accProjectId' => $accProjectId,
                    'dmProjectId'  => $dmProjectId,
                    'folderId'   => $folderId,
                    'prefix'     => $prefix,
                    'arquivos'   => $arquivos->toArray(),
                ],
            ]);
        }
        */

        // ------------------------------------------------
        // 7) Extrair URN e renderizar viewer
        // ------------------------------------------------
        $modelUrn = Str::after($match['derivative_urn'], 'urn:');

        Log::info('Viewer3D - RVT escolhido', [
            'projeto_id' => $projeto->id,
            'fileName' => $match['name'],
            'modelUrn' => $modelUrn,
            'dmProjectId' => $dmProjectId,
            'folderId' => $folderId,
        ]);

        return view('filament.pages.viewer3d-projeto', [
            'projeto' => $projeto,
            'modelUrn' => $modelUrn,
            'error' => null,
            'debug' => [
                'url' => $url,
                'accProjectId' => $accProjectId,
                'dmProjectId' => $dmProjectId,
                'folderId' => $folderId,
                'prefix' => $prefix,
                'fileName' => $match['name'],
            ],
        ]);
    }
}
