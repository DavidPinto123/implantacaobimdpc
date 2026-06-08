<?php

namespace App\Filament\Resources\ListaEmails\Schemas;

use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ListaEmailForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nome')
                    ->label('Nome da lista')
                    ->required()
                    ->maxLength(255),

                Textarea::make('descricao')
                    ->label('Descrição')
                    ->rows(3),

                Toggle::make('ativo')
                    ->label('Ativo')
                    ->default(true),

                TagsInput::make('emails')
                    ->label('E-mails')
                    ->placeholder('Digite e pressione enter')
                    ->required()
                    ->rules(['required', 'array'])
                    ->nestedRecursiveRules(['email'])
                    ->validationMessages([
                        'email' => 'Um ou mais e-mails são inválidos.',
                    ]),
            ]);
    }
}
