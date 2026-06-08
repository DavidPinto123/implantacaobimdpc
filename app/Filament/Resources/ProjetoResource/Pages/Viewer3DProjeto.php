<?php

namespace App\Filament\Resources\ProjetoResource\Pages;

use App\Filament\Resources\ProjetoResource;
use App\Http\Controllers\ApsDocsController;
use App\Models\Projeto;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Viewer3DProjeto extends Page
{
    protected static string $resource = ProjetoResource::class;

    public string $view = 'filament.pages.viewer3d-projeto';

    public Projeto $projeto;

    public ?string $modelUrn = null;

    public ?string $error = null;

    public array $debug = [];

    public function mount(Projeto $record): void
    {
        $this->projeto = $record;

        Log::info('Viewer3D - link_docs recebido', [
            'projeto_id' => $this->projeto->id,
            'nova_sigla' => $this->projeto->nova_sigla,
            'link_docs' => $this->projeto->link_docs,
        ]);

        if (! $this->projeto->link_docs) {
            $this->modelUrn = null;
            $this->error = 'Este projeto não possui link_docs configurado.';
            $this->debug = ['motivo' => 'sem_link_docs'];

            return;
        }

        $url = $this->projeto->link_docs;

        $parts = parse_url($url);

        $path = $parts['path'] ?? '';
        $segments = array_values(array_filter(explode('/', trim($path, '/'))));

        $accProjectId = null;
        if (($i = array_search('projects', $segments, true)) !== false && isset($segments[$i + 1])) {
            $accProjectId = $segments[$i + 1];
        }

        $query = [];
        parse_str($parts['query'] ?? '', $query);

        $folderId = ! empty($query['folderUrn']) ? urldecode($query['folderUrn']) : null;

        if (! $accProjectId || ! $folderId) {
            Log::warning('Viewer3D - não foi possível extrair ACC projectId/folderId do link_docs', [
                'url' => $url,
                'parts' => $parts,
                'segments' => $segments,
                'query' => $query,
            ]);

            $this->modelUrn = null;
            $this->error = 'Não foi possível identificar o projeto/pasta a partir do link_docs (formato de URL não reconhecido).';
            $this->debug = [
                'url' => $url,
                'parts' => $parts,
                'segments' => $segments,
                'query' => $query,
            ];

            return;
        }

        $dmProjectId = Str::startsWith($accProjectId, 'b.')
            ? $accProjectId
            : 'b.'.$accProjectId;

        Log::info('Viewer3D - IDs extraídos/convertidos', [
            'projeto_id' => $this->projeto->id,
            'accProjectId' => $accProjectId,
            'dmProjectId' => $dmProjectId,
            'folderId' => $folderId,
        ]);

        /** @var ApsDocsController $aps */
        $aps = App::make(ApsDocsController::class);

        $result = $aps->arquivos($dmProjectId, $folderId);
        $arquivos = collect($result['simplificados'] ?? []);

        if ($arquivos->isEmpty()) {
            Log::warning('Viewer3D - nenhum arquivo simplificado retornado pela API', [
                'projeto_id' => $this->projeto->id,
                'dmProjectId' => $dmProjectId,
                'folderId' => $folderId,
                'raw' => $result,
            ]);

            $this->modelUrn = null;
            $this->error = 'Nenhum item foi retornado para esta pasta pela API do ACC. Verifique se esta pasta realmente contém os arquivos RVT ou se o link_docs aponta para a pasta correta.';
            $this->debug = [
                'url' => $url,
                'accProjectId' => $accProjectId,
                'dmProjectId' => $dmProjectId,
                'folderId' => $folderId,
                'prefix' => $this->projeto->nova_sigla,
                'api_raw' => $result,
            ];

            return;
        }

        $prefix = trim($this->projeto->nova_sigla);

        $match = $arquivos->first(function ($f) use ($prefix) {
            $name = $f['name'] ?? '';

            Log::info('Viewer3D - arquivo encontrado na pasta (para matching)', [
                'fileName' => $name,
                'prefix' => $prefix,
            ]);

            return Str::contains(Str::upper($name), Str::upper($prefix))
                && Str::endsWith(Str::lower($name), '.rvt');
        });

        if (! $match) {
            $match = $arquivos->first(function ($f) {
                return Str::endsWith(Str::lower($f['name'] ?? ''), '.rvt');
            });
        }

        if (! $match) {
            Log::warning('Viewer3D - nenhum arquivo RVT encontrado nesta pasta', [
                'projeto_id' => $this->projeto->id,
                'dmProjectId' => $dmProjectId,
                'folderId' => $folderId,
                'arquivos' => $arquivos->toArray(),
            ]);

            $this->modelUrn = null;
            $this->error = 'Nenhum arquivo RVT foi encontrado nesta pasta.';
            $this->debug = [
                'url' => $url,
                'accProjectId' => $accProjectId,
                'dmProjectId' => $dmProjectId,
                'folderId' => $folderId,
                'prefix' => $prefix,
                'arquivos' => $arquivos->toArray(),
            ];

            return;
        }

        $this->modelUrn = Str::after($match['derivative_urn'], 'urn:');

        Log::info('Viewer3D - RVT escolhido', [
            'projeto_id' => $this->projeto->id,
            'fileName' => $match['name'],
            'modelUrn' => $this->modelUrn,
            'dmProjectId' => $dmProjectId,
            'folderId' => $folderId,
        ]);

        $this->error = null;
        $this->debug = [
            'url' => $url,
            'accProjectId' => $accProjectId,
            'dmProjectId' => $dmProjectId,
            'folderId' => $folderId,
            'prefix' => $prefix,
            'fileName' => $match['name'],
        ];
    }

    public function getTitle(): string
    {
        return 'Viewer 3D – '.$this->projeto->nome;
    }

    protected function getViewData(): array
    {
        return [
            'projeto' => $this->projeto,
            'modelUrn' => $this->modelUrn,
            'error' => $this->error,
            'debug' => $this->debug,
        ];
    }
}
