<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ApsDocsController extends Controller
{
    private function getToken(): string
    {
        $clientId = config('services.aps.client_id');
        $clientSecret = config('services.aps.client_secret');

        $url = 'https://developer.api.autodesk.com/authentication/v2/token';
        $basicAuth = base64_encode($clientId.':'.$clientSecret);

        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => 'Basic '.$basicAuth,
                'Accept' => 'application/json',
            ])
            ->post($url, [
                'grant_type' => 'client_credentials',
                'scope' => 'data:read data:write data:create bucket:read bucket:create viewables:read',
            ]);

        $data = $response->json();

        if ($response->failed() || ! isset($data['access_token'])) {
            abort(500, 'Erro ao obter token APS: '.json_encode($data));
        }

        return $data['access_token'];
    }

    public function hubs()
    {
        $token = $this->getToken();

        $url = 'https://developer.api.autodesk.com/project/v1/hubs';

        return Http::withToken($token)->get($url)->json();
    }

    public function projetos(string $hubId)
    {
        $token = $this->getToken();

        $url = "https://developer.api.autodesk.com/project/v1/hubs/$hubId/projects";

        return Http::withToken($token)->get($url)->json();
    }

    public function pastas(string $hubId, string $projetoId)
    {
        $token = $this->getToken();

        $url = "https://developer.api.autodesk.com/project/v1/hubs/$hubId/projects/$projetoId/topFolders";

        return Http::withToken($token)->get($url)->json();
    }

    public function arquivos(string $projetoId, string $pastaId)
    {
        $token = $this->getToken();

        $url = "https://developer.api.autodesk.com/data/v1/projects/$projetoId/folders/$pastaId/contents";

        $response = Http::withToken($token)->get($url)->json();

        // Opcional: montar uma lista simplificada só com nome e derivative URN
        $simplificados = [];

        $included = $response['included'] ?? [];

        foreach ($included as $item) {
            if (($item['type'] ?? '') !== 'versions') {
                continue;
            }

            $name = $item['attributes']['displayName'] ?? $item['attributes']['name'] ?? null;
            $derivativeUrn = $item['relationships']['derivatives']['data']['id'] ?? null;

            if ($name && $derivativeUrn) {
                $simplificados[] = [
                    'name' => $name,
                    'version_id' => $item['id'],
                    'derivative_urn' => $derivativeUrn,
                ];
            }
        }

        return [
            'raw' => $response,      // tudo que veio da Autodesk
            'simplificados' => $simplificados, // mais fácil de ler
        ];
    }

    public function findRvtAnywhere(string $prefix)
    {
        $token = $this->getToken();

        // 1. BUSCAR HUBS
        $hubs = Http::withToken($token)
            ->get('https://developer.api.autodesk.com/project/v1/hubs')
            ->json('data') ?? [];

        foreach ($hubs as $hub) {
            $hubId = $hub['id'];

            // 2. BUSCAR PROJETOS DO HUB
            $projetos = Http::withToken($token)
                ->get("https://developer.api.autodesk.com/project/v1/hubs/$hubId/projects")
                ->json('data') ?? [];

            foreach ($projetos as $proj) {
                $projectId = $proj['id'];

                // 3. BUSCAR PASTAS RAIZ
                $pastas = Http::withToken($token)
                    ->get("https://developer.api.autodesk.com/project/v1/hubs/$hubId/projects/$projectId/topFolders")
                    ->json('data') ?? [];

                foreach ($pastas as $pasta) {
                    $folderId = $pasta['id'];

                    // 4. BUSCAR ARQUIvo .RVT (recursivo)
                    $found = $this->scanFolderForRvt($token, $projectId, $folderId, $prefix);

                    if ($found) {
                        return $found;
                    }
                }
            }
        }

        return null;
    }

    private function scanFolderForRvt(string $token, string $projectId, string $folderId, string $prefix)
    {
        $url = "https://developer.api.autodesk.com/data/v1/projects/$projectId/folders/$folderId/contents";
        $response = Http::withToken($token)->get($url)->json();

        $included = $response['included'] ?? [];

        foreach ($included as $item) {
            // Se for um arquivo (version)
            if (($item['type'] ?? '') === 'versions') {
                $name = $item['attributes']['displayName'] ?? '';

                if (Str::startsWith($name, $prefix) && Str::endsWith(strtolower($name), '.rvt')) {
                    $derivativeUrn = $item['relationships']['derivatives']['data']['id'] ?? null;
                    $modelUrn = Str::after($derivativeUrn, 'urn:');

                    return [
                        'fileName' => $name,
                        'modelUrn' => $modelUrn,
                        'projectId' => $projectId,
                        'folderId' => $folderId,
                    ];
                }
            }
        }

        // Se não achou, procurar dentro de pastas internas
        $folders = collect($response['data'] ?? [])
            ->where('type', 'folders')
            ->pluck('id');

        foreach ($folders as $subfolderId) {
            $result = $this->scanFolderForRvt($token, $projectId, $subfolderId, $prefix);
            if ($result) {
                return $result;
            }
        }

        return null;
    }
}
