<?php

// App/Support/TextoIa.php

namespace App\Support;

class TextoIa
{
    public static function limpar(string $t): string
    {
        // normaliza EOL
        $t = preg_replace("/\r\n?/", "\n", $t);

        // remove trechos meta/comentários comuns
        $blacklist = [
            '/^".*$/m',                         // linhas começando com aspas
            '/^Thus.*$/mi',
            '/^Will produce:.*$/mi',
            '/^Use Portuguese.*$/mi',
            '/^Se.*dispon[ií]vel.*$ /mi',
        ];
        $t = preg_replace($blacklist, '', $t);

        // mantém somente do primeiro título reconhecido em diante
        $titulos = [
            'Resumo Executivo',
            'Pontos de Força',
            'Vulnerabilidades / Riscos',
            'Recomendações Estratégicas',
            'Próximos Passos',
        ];
        $primeiro = implode('|', array_map(function ($s) {
            return preg_quote($s, '/');
        }, $titulos));

        if (preg_match("/^(.*?)(###\s*(?:$primeiro))/ms", $t, $m)) {
            $t = $m[2].substr($t, strlen($m[0]) - strlen($m[2]));
        }

        // limpa espaços duplicados
        $t = preg_replace("/\n{3,}/", "\n\n", trim($t));

        return $t;
    }
}
