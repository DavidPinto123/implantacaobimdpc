<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class ApsAuthController extends Controller
{
    public function getToken()
    {
        $clientId = config('services.aps.client_id');
        $clientSecret = config('services.aps.client_secret');

        if (! $clientId || ! $clientSecret) {
            return response()->json([
                'error' => 'APS_CLIENT_ID ou APS_CLIENT_SECRET não configurados. Verifique o .env e o config/services.php.',
            ], 500);
        }

        // 👇 Endpoint CORRETO do OAuth v2
        $url = 'https://developer.api.autodesk.com/authentication/v2/token';

        // Requisição conforme a doc oficial do APS
        $response = Http::asForm()
            ->withHeaders([
                'Accept' => 'application/json',
            ])
            ->post($url, [
                'grant_type' => 'client_credentials',
                'scope' => 'data:read data:write data:create bucket:read bucket:create viewables:read',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ]);

        $data = $response->json();

        if ($response->failed() || ! isset($data['access_token'])) {
            // Devolve o erro cru da Autodesk pra gente ver exatamente o que está vindo
            return response()->json([
                'error' => 'Falha ao obter token da Autodesk',
                'details' => $data,
                'status' => $response->status(),
            ], 500);
        }

        return response()->json([
            'access_token' => $data['access_token'],
            'expires_in' => $data['expires_in'],
        ]);
    }
}
