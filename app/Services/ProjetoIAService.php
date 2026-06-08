<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ProjetoIAService
{
    public static function analisarProjeto(array $dadosProjeto): string
    {
        $prompt = <<<'PROMPT'
Você é analista de viabilidade de projetos. Gere um relatório **APENAS** em português, em **Markdown**, com **exatamente** estas seções como títulos H3, nesta ordem, e nada mais fora delas:

### Resumo Executivo
### Pontos de Força
### Vulnerabilidades / Riscos
### Recomendações Estratégicas
### Próximos Passos

Regras:
- Não escreva comentários sobre seu raciocínio, nem explicações do que vai fazer.
- Se faltar informação para uma seção, escreva exatamente: "Sem [nome da seção] disponível."
- Use listas com marcadores quando fizer sentido.
- Não inclua texto antes do primeiro título nem depois do último.

Dados do projeto (JSON):
%s
PROMPT;

        $prompt = sprintf(
            $prompt,
            json_encode($dadosProjeto, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $payload = [
            'model' => 'openai/gpt-4o-mini', // use um modelo melhor se puder; troque se necessário
            'temperature' => 0.2,
            'max_tokens' => 900,
            'messages' => [
                ['role' => 'system', 'content' => 'Você é um analista de viabilidade de projetos.'],
                ['role' => 'user',   'content' => $prompt],
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.env('OPENROUTER_API_KEY'),
            'HTTP-Referer' => config('app.url') ?? 'https://example.com', // recomendado pelo OpenRouter
            'X-Title' => config('app.name') ?? 'App',                // opcional
        ])
            ->timeout(30)
            ->retry(2, 500)
            ->post('https://openrouter.ai/api/v1/chat/completions', $payload);

        if ($response->failed()) {
            // Retorna o corpo da falha para debugar rapidamente
            return 'Erro na chamada de IA: '.$response->body();
        }

        $conteudo = data_get($response->json(), 'choices.0.message.content');

        if (! is_string($conteudo) || trim($conteudo) === '') {
            return 'Sem resposta da IA.';
        }

        // Limpeza básica: remove “meta” em inglês e lixo antes do primeiro título esperado.
        $conteudo = self::limparResposta($conteudo);

        return $conteudo !== '' ? $conteudo : 'Sem resposta da IA.';
    }

    /**
     * Remove ruídos comuns e mantém do primeiro título esperado em diante.
     */
    protected static function limparResposta(string $t): string
    {
        // normaliza quebras
        $t = preg_replace("/\r\n?/", "\n", $t);

        // remove linhas meta comuns
        $t = preg_replace([
            '/^".*$/m',
            '/^Thus.*$/mi',
            '/^Will produce:.*$/mi',
            '/^Use Portuguese.*$/mi',
        ], '', $t);

        $titulos = [
            'Resumo Executivo',
            'Pontos de Força',
            'Vulnerabilidades / Riscos',
            'Recomendações Estratégicas',
            'Próximos Passos',
        ];
        $regexPrimeiro = implode('|', array_map(fn ($s) => preg_quote($s, '/'), $titulos));

        // corta qualquer coisa antes do primeiro título Markdown esperado
        if (preg_match("/(###\s*(?:$regexPrimeiro))/m", $t, $m, PREG_OFFSET_CAPTURE)) {
            $t = substr($t, $m[0][1]);
        }

        // compacta múltiplas linhas vazias
        $t = preg_replace("/\n{3,}/", "\n\n", trim($t));

        return $t;
    }
}
