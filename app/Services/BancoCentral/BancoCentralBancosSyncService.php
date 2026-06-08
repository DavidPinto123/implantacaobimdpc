<?php

namespace App\Services\BancoCentral;

use App\Models\Banco;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class BancoCentralBancosSyncService
{
    public const CSV_URL = 'https://www.bcb.gov.br/pom/spb/ing/ParticipantesSTRIng.csv';

    /**
     * @return array{sincronizados: int, inativados: int}
     */
    public function sync(): array
    {
        $rows = $this->parseCsv($this->downloadCsv());
        $now = now();
        $ispbs = [];
        $sincronizados = 0;

        foreach ($rows as $row) {
            $ispb = $this->normalizeIspb(Arr::get($row, 'ISPB'));

            if ($ispb === '') {
                continue;
            }

            $ispbs[] = $ispb;

            Banco::query()->updateOrCreate(
                ['ispb' => $ispb],
                [
                    'codigo' => $this->normalizeCodigo(Arr::get($row, 'Code_Number')),
                    'nome_reduzido' => trim((string) Arr::get($row, 'Short_Name')),
                    'nome_extenso' => $this->getFirstFilled($row, ['Full_Name', 'Full_name']),
                    'participa_compe' => $this->parseBoolean(Arr::get($row, 'Participation_in_Compe')),
                    'ativo' => true,
                    'sincronizado_em' => $now,
                ],
            );

            $sincronizados++;
        }

        $inativados = Banco::query()
            ->whereNotIn('ispb', $ispbs)
            ->where('ativo', true)
            ->update(['ativo' => false]);

        return [
            'sincronizados' => $sincronizados,
            'inativados' => $inativados,
        ];
    }

    protected function downloadCsv(): string
    {
        return Http::timeout(20)
            ->retry(3, 500)
            ->get(self::CSV_URL)
            ->throw()
            ->body();
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    protected function parseCsv(string $csv): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($csv));

        if ($lines === false || $lines === []) {
            return [];
        }

        $delimiter = str_contains($lines[0], ';') ? ';' : ',';
        $headers = array_map(
            fn (?string $header): string => trim(str_replace("\u{FEFF}", '', (string) $header)),
            str_getcsv(array_shift($lines), $delimiter),
        );
        $rows = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $values = str_getcsv($line, $delimiter);
            $row = array_combine($headers, array_pad($values, count($headers), null));

            if ($row !== false) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    protected function normalizeIspb(mixed $value): string
    {
        return preg_replace('/\D/', '', (string) $value) ?? '';
    }

    protected function normalizeCodigo(mixed $value): ?string
    {
        $rawValue = trim((string) $value);

        if (strtolower($rawValue) === 'n/a') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $rawValue) ?? '';

        if ($digits === '') {
            return null;
        }

        return str_pad($digits, 3, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string, string|null>  $row
     * @param  array<int, string>  $keys
     */
    protected function getFirstFilled(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = trim((string) Arr::get($row, $key));

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    protected function parseBoolean(mixed $value): bool
    {
        return in_array(mb_strtolower(trim((string) $value)), ['sim', 'yes', 's', 'y', 'true', '1'], true);
    }
}
