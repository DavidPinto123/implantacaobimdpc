<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Notifications\UserUpdatedNotification;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Password;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $this->record->notify(new UserUpdatedNotification);

        if ($this->record->wasChanged('email_verified_at') && $this->record->hasVerifiedEmail()) {
            Password::broker()->sendResetLink([
                'email' => $this->record->email,
            ]);
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (empty($data['is_fornecedor'])) {
            $data['construtoras_id'] = null;
        }

        return $data;
    }
}
