<?php

namespace App\Filament\Resources\ObraRecebimentos\Pages;

use App\Filament\Resources\ObraRecebimentos\ObraRecebimentoResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EditObraRecebimento extends EditRecord
{
    protected static string $resource = ObraRecebimentoResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (blank($data['foto_entrega_paths'] ?? null) && filled($this->record->foto_entrega_path)) {
            $data['foto_entrega_paths'] = [$this->record->foto_entrega_path];
        }

        if (blank($data['foto_entrega_nomes'] ?? null) && filled($this->record->foto_entrega_nome)) {
            $data['foto_entrega_nomes'] = [$this->record->foto_entrega_nome];
        }

        if (blank($data['foto_entrega_nomes'] ?? null) && filled($this->record->foto_entrega_path)) {
            $data['foto_entrega_nomes'] = [basename($this->record->foto_entrega_path)];
        }

        if (blank($data['nota_fiscal_paths'] ?? null) && filled($this->record->nota_fiscal_path)) {
            $data['nota_fiscal_paths'] = [$this->record->nota_fiscal_path];
        }

        if (blank($data['nota_fiscal_nomes'] ?? null) && filled($this->record->nota_fiscal_nome)) {
            $data['nota_fiscal_nomes'] = [$this->record->nota_fiscal_nome];
        }

        if (blank($data['nota_fiscal_nomes'] ?? null) && filled($this->record->nota_fiscal_path)) {
            $data['nota_fiscal_nomes'] = [basename($this->record->nota_fiscal_path)];
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            throw ValidationException::withMessages([
                'obra_id' => 'Usuário inválido para editar recebimentos.',
            ]);
        }

        if (ObraRecebimentoResource::isReceivedStatus($this->record->status)) {
            throw ValidationException::withMessages([
                'status' => 'Este item já foi recebido e não pode mais ser editado.',
            ]);
        }

        $obraId = $this->record->obra_id;

        $obra = ObraRecebimentoResource::getAvailableObrasQuery($user)
            ->whereKey($obraId)
            ->first();

        if (! $obra) {
            throw ValidationException::withMessages([
                'obra_id' => 'Selecione uma obra válida para a seu fornecedor.',
            ]);
        }

        $data['obra_id'] = $this->record->obra_id;
        $data['nome'] = $this->record->nome;
        $data['construtora_id'] = ObraRecebimentoResource::resolveConstrutoraIdForObra($obra->id, $user)
            ?? $this->record->construtora_id;
        $fotoPaths = $this->normalizeUploadedPaths($data['foto_entrega_paths'] ?? $this->record->foto_entrega_paths_resolved);
        $notaPaths = $this->normalizeUploadedPaths($data['nota_fiscal_paths'] ?? $this->record->nota_fiscal_paths_resolved);

        $data['foto_entrega_paths'] = $fotoPaths;
        $data['nota_fiscal_paths'] = $notaPaths;
        $data['foto_entrega_nomes'] = $this->normalizeUploadedFileNames(
            $data['foto_entrega_nomes'] ?? $this->record->foto_entrega_nomes_resolved,
            $fotoPaths,
        );
        $data['nota_fiscal_nomes'] = $this->normalizeUploadedFileNames(
            $data['nota_fiscal_nomes'] ?? $this->record->nota_fiscal_nomes_resolved,
            $notaPaths,
        );
        $data['foto_entrega_path'] = $fotoPaths[0] ?? null;
        $data['foto_entrega_nome'] = $data['foto_entrega_nomes'][0] ?? null;
        $data['nota_fiscal_path'] = $notaPaths[0] ?? null;
        $data['nota_fiscal_nome'] = $data['nota_fiscal_nomes'][0] ?? null;

        $hasAnyComprovante = $fotoPaths !== [] || $notaPaths !== [];

        if ($hasAnyComprovante) {
            $data['status'] = 'recebido';
        } else {
            $data['status'] = $this->record->status;
        }

        return $data;
    }

    protected function normalizeUploadedPaths(string|array|null $paths): array
    {
        if (is_array($paths)) {
            return collect($paths)
                ->filter(fn (mixed $value): bool => is_string($value) && filled($value))
                ->values()
                ->all();
        }

        if (! filled($paths)) {
            return [];
        }

        return [$paths];
    }

    protected function normalizeUploadedFileNames(string|array|null $names, array $paths): array
    {
        if (is_array($names)) {
            $names = collect($names)
                ->filter(fn (mixed $value): bool => is_string($value) && filled($value))
                ->values()
                ->all();

            if ($names !== []) {
                return $names;
            }
        }

        if (is_string($names) && filled($names)) {
            return [$names];
        }

        return collect($paths)
            ->map(fn (string $path): string => basename($path))
            ->values()
            ->all();
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => ObraRecebimentoResource::canManageAll(Auth::user())),
        ];
    }

    protected function getFormActions(): array
    {
        if (ObraRecebimentoResource::isReceivedStatus($this->record->status)) {
            return [];
        }

        return parent::getFormActions();
    }

    protected function getRedirectUrl(): string
    {
        return ObraRecebimentoResource::getUrl('index');
    }
}
