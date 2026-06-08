<?php

namespace App\Services;

use App\Filament\Resources\ObraDocumentos\ObraDocumentoResource;
use App\Models\AsEscopo;
use App\Models\Construtora;
use App\Models\ObraDocumento;
use App\Models\Obras;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ObraDocumentoSyncService
{
    /**
     * Mapeamento numero_as -> nomes canonicos dos ObraDocumentos do escopo.
     * Quando um escopo e contratado em uma obra, todos os documentos da lista
     * sao criados/atualizados.
     */
    public const MAPA_AS_DOCUMENTOS = [
        '01.1' => [
            'ART de Execução Civil',
            'Seguro de obra',
            'Manual da Obra',
        ],
        '03.1' => ['ART de Execução Ar cond.'],
        '05.1' => ['ART de Execução Elétrica'],
        '06.1' => ['ART de Execução Incêndio'],
        '32.1' => ['ART de Execução Elevador'],
        '52.1' => ['ART de Execução Hidráulica'],
    ];

    /**
     * Mantem compatibilidade com o comportamento anterior: retorna o documento principal
     * do escopo.
     */
    public function nomeDocumentoParaEscopo(?string $numeroAs): ?string
    {
        $nomes = $this->nomesDocumentosParaEscopo($numeroAs);

        return $nomes[0] ?? null;
    }

    /**
     * @return list<string>
     */
    public function nomesDocumentosParaEscopo(?string $numeroAs): array
    {
        $key = trim((string) $numeroAs);

        if ($key === '' || ! isset(self::MAPA_AS_DOCUMENTOS[$key])) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($nome) => trim((string) $nome),
            self::MAPA_AS_DOCUMENTOS[$key]
        )));
    }

    /**
     * Cria (se necessario) os ObraDocumentos correspondentes ao escopo contratado.
     * Se ja existir documento com mesmo nome na obra, atualiza o fornecedor caso esteja mudando.
     */
    public function syncCreatedFromEscopo(int $obraId, ?int $asEscopoId, ?string $empresaNome = null): ?ObraDocumento
    {
        if (! $obraId || ! $asEscopoId) {
            return null;
        }

        $escopo = AsEscopo::query()->find($asEscopoId);
        if (! $escopo instanceof AsEscopo) {
            return null;
        }

        $nomesDocs = $this->nomesDocumentosParaEscopo($escopo->numero_as);
        if ($nomesDocs === []) {
            return null;
        }

        $construtora = $this->resolverConstrutora($empresaNome);
        $documentoPrincipal = null;

        foreach ($nomesDocs as $idx => $nomeDoc) {
            $existente = ObraDocumento::query()
                ->where('obra_id', $obraId)
                ->whereRaw('LOWER(nome) = ?', [mb_strtolower($nomeDoc)])
                ->first();

            if ($existente instanceof ObraDocumento) {
                if ($construtora && (int) $existente->construtora_id !== (int) $construtora->id) {
                    $existente->update(['construtora_id' => $construtora->id]);

                    if ($idx === 0) {
                        $this->notificarConstrutora($existente, $construtora, atribuicao: false);
                    }
                }

                $documentoPrincipal ??= $existente;

                continue;
            }

            $documento = ObraDocumento::create([
                'obra_id' => $obraId,
                'construtora_id' => $construtora?->id,
                'nome' => $nomeDoc,
                'status' => 'pendente',
                'usuario_id' => Auth::id(),
            ]);

            if ($idx === 0 && $construtora) {
                $this->notificarConstrutora($documento, $construtora, atribuicao: true);
            }

            $documentoPrincipal ??= $documento;
        }

        return $documentoPrincipal;
    }

    /**
     * Atualiza o fornecedor dos ObraDocumentos associados ao escopo quando a empresa muda no controle de NF.
     */
    public function syncEmpresaAtualizada(int $obraId, ?int $asEscopoId, ?string $empresaNome): void
    {
        if (! $obraId || ! $asEscopoId) {
            return;
        }

        $escopo = AsEscopo::query()->find($asEscopoId);
        if (! $escopo instanceof AsEscopo) {
            return;
        }

        $nomesDocs = $this->nomesDocumentosParaEscopo($escopo->numero_as);
        if ($nomesDocs === []) {
            return;
        }

        $construtora = $this->resolverConstrutora($empresaNome);
        $construtoraIdNova = (int) ($construtora?->id ?? 0);

        foreach ($nomesDocs as $idx => $nomeDoc) {
            $documento = ObraDocumento::query()
                ->where('obra_id', $obraId)
                ->whereRaw('LOWER(nome) = ?', [mb_strtolower($nomeDoc)])
                ->first();

            if (! $documento instanceof ObraDocumento) {
                $this->syncCreatedFromEscopo($obraId, $asEscopoId, $empresaNome);

                return;
            }

            $construtoraIdAnterior = (int) ($documento->construtora_id ?? 0);

            if ($construtoraIdAnterior === $construtoraIdNova) {
                continue;
            }

            $documento->update(['construtora_id' => $construtoraIdNova ?: null]);

            if ($idx === 0 && $construtora) {
                $this->notificarConstrutora(
                    $documento,
                    $construtora,
                    atribuicao: $construtoraIdAnterior === 0
                );
            }
        }
    }

    /**
     * Tenta excluir os ObraDocumentos associados ao escopo.
     * Retorna true se excluiu (ou se nao havia nada a excluir).
     * Retorna false quando ha upload e $forcar = false.
     * Quando $forcar = true, mantem os documentos com upload e exclui os demais.
     */
    public function tentarExcluirPorEscopo(int $obraId, ?int $asEscopoId, bool $forcar = false): bool
    {
        if (! $obraId || ! $asEscopoId) {
            return true;
        }

        $escopo = AsEscopo::query()->find($asEscopoId);
        if (! $escopo instanceof AsEscopo) {
            return true;
        }

        $nomesDocs = $this->nomesDocumentosParaEscopo($escopo->numero_as);
        if ($nomesDocs === []) {
            return true;
        }

        foreach ($nomesDocs as $nomeDoc) {
            $documento = ObraDocumento::query()
                ->where('obra_id', $obraId)
                ->whereRaw('LOWER(nome) = ?', [mb_strtolower($nomeDoc)])
                ->first();

            if (! $documento instanceof ObraDocumento) {
                continue;
            }

            if ($this->temUpload($documento)) {
                if (! $forcar) {
                    return false;
                }

                continue;
            }

            $documento->delete();
        }

        return true;
    }

    /**
     * Verifica se um escopo tem upload em algum dos documentos associados.
     */
    public function escopoTemUploadDocumento(int $obraId, ?int $asEscopoId): bool
    {
        if (! $obraId || ! $asEscopoId) {
            return false;
        }

        $escopo = AsEscopo::query()->find($asEscopoId);
        if (! $escopo instanceof AsEscopo) {
            return false;
        }

        $nomesDocs = $this->nomesDocumentosParaEscopo($escopo->numero_as);
        if ($nomesDocs === []) {
            return false;
        }

        foreach ($nomesDocs as $nomeDoc) {
            $documento = ObraDocumento::query()
                ->where('obra_id', $obraId)
                ->whereRaw('LOWER(nome) = ?', [mb_strtolower($nomeDoc)])
                ->first();

            if ($documento instanceof ObraDocumento && $this->temUpload($documento)) {
                return true;
            }
        }

        return false;
    }

    public function nomeDocumentoVinculado(?int $asEscopoId): ?string
    {
        if (! $asEscopoId) {
            return null;
        }

        $escopo = AsEscopo::query()->find($asEscopoId);
        if (! $escopo instanceof AsEscopo) {
            return null;
        }

        return $this->nomeDocumentoParaEscopo($escopo->numero_as);
    }

    protected function temUpload(ObraDocumento $d): bool
    {
        return filled($d->arquivo_path)
            || (is_array($d->arquivos_paths) && array_filter($d->arquivos_paths) !== []);
    }

    protected function resolverConstrutora(?string $nome): ?Construtora
    {
        $nome = trim((string) $nome);
        if ($nome === '') {
            return null;
        }

        return Construtora::query()->where('nome', $nome)->first();
    }

    protected function notificarConstrutora(ObraDocumento $documento, Construtora $construtora, bool $atribuicao): void
    {
        $usuarios = $construtora->users()->get();

        if ($usuarios->isEmpty()) {
            return;
        }

        $obra = Obras::query()->find($documento->obra_id);
        $obraNome = $obra?->projeto?->nome ?? ('Obra #'.$documento->obra_id);

        $titulo = $atribuicao
            ? 'Novo documento atribuído'
            : 'Documento reatribuído ao seu fornecedor';

        $corpo = sprintf(
            'O documento "%s" da obra "%s" foi atribuído à %s para envio.',
            $documento->nome,
            $obraNome,
            $construtora->nome
        );

        Notification::make()
            ->title($titulo)
            ->body($corpo)
            ->icon('heroicon-o-document-text')
            ->warning()
            ->actions([
                Action::make('ver_doc')
                    ->label('Abrir item')
                    ->url(ObraDocumentoResource::getUrl('edit', ['record' => $documento->id])),
            ])
            ->sendToDatabase($usuarios);
    }
}
