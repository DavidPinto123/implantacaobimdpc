<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile; // v4
use Filament\Forms\Components\FileUpload;                              // v4
use Filament\Forms\Components\TextInput;               // v4
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class EditProfile extends BaseEditProfile
{
    protected static ?string $title = 'Meu Perfil';

    protected ?string $heading = 'Dados do usuário';

    // v4: assinatura com Schema e ->components()
    public function form(Schema $schema): Schema
    {
        return $schema->components([

            // -------------------------------------------------------------------------
            Grid::make()
                ->columns(4)
                ->schema([
                    Section::make('Informações Básicas')
                        ->description('Preencha com seus dados')
                        ->columnSpan(3)
                        ->schema([
                            $this->getNameFormComponent(),
                            // O componente base já trata e-mail; manter assim evita duplicar regras:
                            $this->getEmailFormComponent(),
                        ]),
                    Section::make('Foto')
                        ->columnSpan(1)
                        ->schema([
                            // Foto de perfil (certifique-se da coluna 'foto_perfil' em users)
                            FileUpload::make('foto_perfil')
                                ->hiddenLabel()
                                ->image()
                                ->avatar()
                                ->imageEditor()
                                ->circleCropper()
                                ->disk((string) config('filesystems.media_disk', 'r2'))
                                ->directory(fn ($record) => 'user/fotos-perfil/'.($record?->id ?? 'temp')
                                ),
                        ]),
                ]),
            // ---------------------------------------------------------------------------
            Section::make('Segurança')
                ->description('Para trocar de senha preencha sua senha atual e a nova senha')
                ->schema([
                    // Segurança
                    TextInput::make('current_password')
                        ->label('Senha atual')
                        ->password()
                        ->revealable()
                        ->autocomplete('current-password')
                        ->dehydrated(false)
                        ->rule('current_password')
                        ->required(fn (Get $get) => filled($get('password'))), // << Get correto no v4

                    $this->getPasswordFormComponent()
                        ->revealable()
                        ->autocomplete('new-password'),

                    $this->getPasswordConfirmationFormComponent()
                        ->label('Confirmar nova senha'),

                ]),
        ]);
    }

    // v4: os overrides retornam Filament\Schemas\Components\Component
    protected function getPasswordFormComponent(): Component
    {
        /** @var TextInput $input */
        $input = parent::getPasswordFormComponent();

        return $input
            ->revealable(true)
            ->minLength(8);
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        /** @var TextInput $input */
        $input = parent::getPasswordConfirmationFormComponent();

        return $input->label('Confirmar nova senha');
    }
}
