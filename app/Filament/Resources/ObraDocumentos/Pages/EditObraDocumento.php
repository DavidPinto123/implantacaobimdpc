<?php

namespace App\Filament\Resources\ObraDocumentos\Pages;

use App\Filament\Resources\ObraDocumentos\ObraDocumentoResource;
use App\Models\ObraDocumento;
use App\Models\Obras;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EditObraDocumento extends EditRecord
{
    protected static string $resource = ObraDocumentoResource::class;

    protected int $arquivosAntesDoSave = 0;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (blank($data['arquivos_paths'] ?? null) && filled($this->record->arquivo_path)) {
            $data['arquivos_paths'] = [$this->record->arquivo_path];
        }

        if (blank($data['arquivos_nomes'] ?? null) && filled($this->record->arquivo_nome)) {
            $data['arquivos_nomes'] = [$this->record->arquivo_nome];
        }

        if (blank($data['arquivos_nomes'] ?? null) && filled($this->record->arquivo_path)) {
            $data['arquivos_nomes'] = [basename($this->record->arquivo_path)];
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->arquivosAntesDoSave = count($this->record->arquivos_paths_resolved);

        $user = Auth::user();

        if (! $user instanceof User) {
            throw ValidationException::withMessages([
                'obra_id' => 'Usuário inválido para editar documentos.',
            ]);
        }

        if (ObraDocumentoResource::isSentStatus($this->record->status)) {
            throw ValidationException::withMessages([
                'status' => 'Este documento já foi enviado e não pode mais ser editado.',
            ]);
        }

        $obra = Obras::query()
            ->whereKey($this->record->obra_id)
            ->first();

        if (! $obra) {
            throw ValidationException::withMessages([
                'obra_id' => 'A obra vinculada a este documento não foi encontrada.',
            ]);
        }

        $data['obra_id'] = $this->record->obra_id;
        $data['nome'] = $this->record->nome;
        $data['usuario_id'] = $user->id;
        $data['arquivos_nomes'] = $data['arquivos_nomes'] ?? $this->record->arquivos_nomes;

        $paths = $data['arquivos_paths'] ?? $this->record->arquivos_paths_resolved;

        if (filled($paths) && is_array($paths) && $paths !== []) {
            $data['status'] = 'enviado';
        } else {
            $data['status'] = 'pendente';
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getFormActions(): array
    {
        if (ObraDocumentoResource::isSentStatus($this->record->status)) {
            return [];
        }

        return parent::getFormActions();
    }

    protected function getRedirectUrl(): string
    {
        return ObraDocumentoResource::getUrl('index');
    }

    protected function afterSave(): void
    {
        $documento = $this->record->fresh();
        if (! $documento) {
            return;
        }

        $arquivosDepois = count($documento->arquivos_paths_resolved);
        $adicionados = max(0, $arquivosDepois - $this->arquivosAntesDoSave);

        if ($adicionados === 0) {
            return;
        }

        $user = Auth::user();
        if (! $user instanceof User) {
            return;
        }

        // Quem precisa ser notificado depende de quem subiu:
        //  - Fornecedor subiu => notifica gestores de Obras
        //  - Gestor subiu => notifica usuários do fornecedor atribuído
        if ($user->hasRole('Fornecedor')) {
            $this->notificarGestoresEnvioArquivo($documento, $adicionados);
        } else {
            $this->notificarConstrutoraEnvioArquivo($documento, $adicionados);
        }
    }

    private function notificarGestoresEnvioArquivo(ObraDocumento $documento, int $quantidade): void
    {
        $gestores = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn (Builder $q) => $q->where('name', 'Gestor'))
            ->whereHas('setores', fn (Builder $q) => $q->whereRaw('LOWER(setor) = ?', ['obras']))
            ->get();

        if ($gestores->isEmpty()) {
            return;
        }

        $obra = Obras::query()->find($documento->obra_id);
        $obraNome = $obra?->projeto?->nome ?? ('Obra #'.$documento->obra_id);
        $construtoraNome = $documento->construtora?->nome ?? 'Fornecedor';

        $titulo = $quantidade === 1
            ? 'Fornecedor enviou um arquivo'
            : 'Fornecedor enviou '.$quantidade.' arquivos';

        $corpo = sprintf(
            '%s anexou ao documento "%s" da obra "%s".',
            $construtoraNome,
            $documento->nome,
            $obraNome
        );

        Notification::make()
            ->title($titulo)
            ->body($corpo)
            ->icon('heroicon-o-document-arrow-up')
            ->success()
            ->actions([
                Action::make('ver_doc')
                    ->label('Abrir item')
                    ->url(ObraDocumentoResource::getUrl('edit', ['record' => $documento->id])),
            ])
            ->sendToDatabase($gestores);
    }

    private function notificarConstrutoraEnvioArquivo(ObraDocumento $documento, int $quantidade): void
    {
        $construtora = $documento->construtora;
        if (! $construtora) {
            return;
        }

        $usuarios = $construtora->users()->get();
        if ($usuarios->isEmpty()) {
            return;
        }

        $obra = Obras::query()->find($documento->obra_id);
        $obraNome = $obra?->projeto?->nome ?? ('Obra #'.$documento->obra_id);

        $titulo = $quantidade === 1
            ? 'Gestor anexou um arquivo'
            : 'Gestor anexou '.$quantidade.' arquivos';

        $corpo = sprintf(
            'O gestor anexou um arquivo ao documento "%s" da obra "%s".',
            $documento->nome,
            $obraNome
        );

        Notification::make()
            ->title($titulo)
            ->body($corpo)
            ->icon('heroicon-o-document-arrow-up')
            ->success()
            ->actions([
                Action::make('ver_doc')
                    ->label('Abrir item')
                    ->url(ObraDocumentoResource::getUrl('edit', ['record' => $documento->id])),
            ])
            ->sendToDatabase($usuarios);
    }
}
