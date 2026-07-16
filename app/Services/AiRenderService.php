<?php

namespace App\Services;

use App\Models\AmbientacaoImagem;

class AiRenderService
{
    public function isConfigured(): bool
    {
        return filled(config('services.ai_render.provider'));
    }

    public function generate(AmbientacaoImagem $recorte): AmbientacaoImagem
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Nenhum provedor de IA configurado ainda. Defina services.ai_render.provider para habilitar esta função.');
        }

        throw new \RuntimeException('Integração com provedor de IA ainda não implementada.');
    }
}
