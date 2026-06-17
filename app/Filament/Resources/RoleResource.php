<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages\ListRoles;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource as ShieldRoleResource;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class RoleResource extends ShieldRoleResource
{
    public static function getPages(): array
    {
        $pages = parent::getPages();
        $pages['index'] = ListRoles::route('/');

        return $pages;
    }

    private static function resolveEditRecord($livewire): ?Model
    {
        if (! method_exists($livewire, 'getRecord')) {
            return null;
        }

        try {
            $record = $livewire->getRecord();
        } catch (\Throwable) {
            return null;
        }

        return ($record instanceof Model && $record->exists) ? $record : null;
    }

    public static function getTabFormComponentForResources(): Component
    {
        if (static::shield()->hasSimpleResourcePermissionView()) {
            return parent::getTabFormComponentForResources();
        }

        $filterHtml = <<<'FILTER'
<input type="text"
    placeholder="Filtrar recursos..."
    style="width:100%;padding:8px 12px;border:1px solid var(--fi-color-gray-300,#d1d5db);border-radius:.5rem;background:transparent;color:inherit;font-size:.875rem;outline:none;box-sizing:border-box;margin-bottom:4px;"
    @focus="$el.style.borderColor='var(--fi-color-primary-500,#6366f1)'"
    @blur="$el.style.borderColor='var(--fi-color-gray-300,#d1d5db)'"
    x-on:input="const q=this.value.toLowerCase().trim();let c=this,ss=[];while(c&&c!==document.body){ss=Array.from(c.querySelectorAll('section.fi-section'));if(ss.length>1)break;c=c.parentElement;}ss.forEach(s=>{const h=(s.querySelector('.fi-section-header-heading')?.textContent??'').toLowerCase().trim();s.style.display=!q||h.includes(q)?'':'none';});">
FILTER;

        $total = static::getResourceTabBadgeCount();

        $allResourcePermNames = collect(FilamentShield::getResources())
            ->flatMap(fn (array $r) => collect($r['permissions'])->pluck('key'))
            ->unique()
            ->values()
            ->all();

        return Tab::make('resources')
            ->label(__('filament-shield::filament-shield.resources'))
            ->visible(fn (): bool => Utils::isResourceTabEnabled())
            ->badge(function ($livewire) use ($total, $allResourcePermNames) {
                $record = static::resolveEditRecord($livewire);

                if (! $record) {
                    return $total;
                }

                $selected = $record->permissions()->whereIn('name', $allResourcePermNames)->count();

                return $selected > 0 ? "{$total} / {$selected}" : $total;
            })
            ->schema([
                Html::make($filterHtml),
                Grid::make()
                    ->schema(static::getResourceEntitiesSchema())
                    ->columns(static::shield()->getGridColumns()),
            ]);
    }

    public static function getTabFormComponentForPage(): Component
    {
        $options   = static::getPageOptions();
        $total     = count($options);
        $permNames = array_keys($options);

        return Tab::make('pages')
            ->label(__('filament-shield::filament-shield.pages'))
            ->visible(fn (): bool => Utils::isPageTabEnabled() && $total > 0)
            ->badge(function ($livewire) use ($total, $permNames) {
                $record = static::resolveEditRecord($livewire);

                if (! $record) {
                    return $total;
                }

                $selected = $record->permissions()->whereIn('name', $permNames)->count();

                return $selected > 0 ? "{$total} / {$selected}" : $total;
            })
            ->schema([
                static::getCheckboxListFormComponent(
                    name: 'pages_tab',
                    options: $options,
                ),
            ]);
    }

    public static function getTabFormComponentForWidget(): Component
    {
        $options   = static::getWidgetOptions();
        $total     = count($options);
        $permNames = array_keys($options);

        return Tab::make('widgets')
            ->label(__('filament-shield::filament-shield.widgets'))
            ->visible(fn (): bool => Utils::isWidgetTabEnabled() && $total > 0)
            ->badge(function ($livewire) use ($total, $permNames) {
                $record = static::resolveEditRecord($livewire);

                if (! $record) {
                    return $total;
                }

                $selected = $record->permissions()->whereIn('name', $permNames)->count();

                return $selected > 0 ? "{$total} / {$selected}" : $total;
            })
            ->schema([
                static::getCheckboxListFormComponent(
                    name: 'widgets_tab',
                    options: $options,
                ),
            ]);
    }

    public static function getTabFormComponentForCustomPermissions(): Component
    {
        $options   = FilamentShield::getCustomPermissions(static::shield()->hasLocalizedPermissionLabels());
        $total     = count($options);
        $permNames = array_keys($options);

        return Tab::make('custom_permissions')
            ->label(__('filament-shield::filament-shield.custom'))
            ->visible(fn (): bool => Utils::isCustomPermissionTabEnabled() && $total > 0)
            ->badge(function ($livewire) use ($total, $permNames) {
                $record = static::resolveEditRecord($livewire);

                if (! $record) {
                    return $total;
                }

                $selected = $record->permissions()->whereIn('name', $permNames)->count();

                return $selected > 0 ? "{$total} / {$selected}" : $total;
            })
            ->schema([
                static::getCheckboxListFormComponent(
                    name: 'custom_permissions_tab',
                    options: $options,
                ),
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
