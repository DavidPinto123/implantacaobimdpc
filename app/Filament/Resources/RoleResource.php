<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages\ListRoles;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource as ShieldRoleResource;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class RoleResource extends ShieldRoleResource
{
    public static function getPages(): array
    {
        $pages = parent::getPages();
        $pages['index'] = ListRoles::route('/');

        return $pages;
    }

    public static function getTabFormComponentForResources(): Component
    {
        if (static::shield()->hasSimpleResourcePermissionView()) {
            return parent::getTabFormComponentForResources();
        }

        return Tab::make('resources')
            ->label(__('filament-shield::filament-shield.resources'))
            ->visible(fn (): bool => Utils::isResourceTabEnabled())
            ->badge(static::getResourceTabBadgeCount())
            ->schema([
                TextInput::make('recursos_search')
                    ->hiddenLabel()
                    ->placeholder('Filtrar recursos...')
                    ->dehydrated(false)
                    ->extraInputAttributes([
                        'x-on:input' => 'const q=this.value.toLowerCase().trim();let c=this,ss=[];while(c&&c!==document.body){ss=Array.from(c.querySelectorAll("section.fi-section"));if(ss.length>1)break;c=c.parentElement;}ss.forEach(s=>{const h=(s.querySelector(".fi-section-header-heading")?.textContent??"").toLowerCase().trim();s.style.display=!q||h.includes(q)?"":"none";});',
                    ]),
                Grid::make()
                    ->schema(static::getResourceEntitiesSchema())
                    ->columns(static::shield()->getGridColumns()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return parent::table($table)
            ->recordActions([
                EditAction::make(),
                Action::make('duplicar')
                    ->label('Duplicar')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->modalHeading('Duplicar perfil')
                    ->modalDescription('Cria uma cópia deste perfil com todas as suas permissões.')
                    ->form([
                        TextInput::make('nome')
                            ->label('Nome do novo perfil')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (Role $record, array $data): void {
                        $novoRole = Role::create([
                            'name'       => $data['nome'],
                            'guard_name' => $record->guard_name,
                        ]);
                        $novoRole->syncPermissions($record->permissions()->pluck('id'));

                        Notification::make()
                            ->title('Perfil duplicado com sucesso')
                            ->body('"' . $data['nome'] . '" criado com ' . $record->permissions()->count() . ' permissão(ões).')
                            ->success()
                            ->send();
                    }),
                DeleteAction::make(),
            ]);
    }
}
