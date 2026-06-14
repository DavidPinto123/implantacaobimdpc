<?php

namespace App\Filament\Pages\PosObra;

use App\Models\Obras;
use App\Models\User;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use UnitEnum;

class LideresObraPage extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static UnitEnum|string|null $navigationGroup = 'Cadastros';

    protected static ?string $navigationLabel = 'Líderes de Obra';

    protected static ?string $title = 'Líderes de Obra';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.pos-obra.lideres-obra';

    public array $lideres = [];

    public ?int $userId = null;

    public array $obraIds = [];

    public ?int $editandoId = null;

    public array $editandoObras = [];

    public function mount(): void
    {
        $this->loadData();
    }

    public function adicionarForm(Schema $schema): Schema
    {
        $liderIds = collect($this->lideres)->pluck('id')->toArray();

        return $schema->columns(2)->schema([
            Select::make('userId')
                ->label('Usuário')
                ->options(
                    User::whereNotIn('id', $liderIds)
                        ->whereNotNull('name')
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                )
                ->searchable()
                ->preload()
                ->placeholder('Selecione um usuário...')
                ->required(),
            Select::make('obraIds')
                ->label('Obras')
                ->options(
                    Obras::whereHas('projeto', fn ($q) => $q->whereNotNull('sigla'))
                        ->with('projeto:id,sigla')
                        ->get()
                        ->sortBy('sigla')
                        ->mapWithKeys(fn ($o) => [$o->id => $o->sigla.($o->unidade ? " ({$o->unidade})" : '')])
                )
                ->multiple()
                ->searchable()
                ->preload()
                ->placeholder('Selecione as obras...')
                ->required(),
        ]);
    }

    public function editarForm(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('editandoObras')
                ->label('Obras vinculadas')
                ->options(
                    Obras::whereHas('projeto', fn ($q) => $q->whereNotNull('sigla'))
                        ->with('projeto:id,sigla')
                        ->get()
                        ->sortBy('sigla')
                        ->mapWithKeys(fn ($o) => [$o->id => $o->sigla.($o->unidade ? " ({$o->unidade})" : '')])
                )
                ->multiple()
                ->searchable()
                ->preload(),
        ]);
    }

    public function loadData(): void
    {
        $this->lideres = User::where('is_lider_obra', true)
            ->with('obrasComoLider:id,sigla,unidade')
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'obras' => $u->obrasComoLider->map(fn ($o) => [
                    'id' => $o->id,
                    'sigla' => $o->sigla,
                    'unidade' => $o->unidade,
                ])->toArray(),
            ])
            ->toArray();
    }

    public function adicionar(): void
    {
        if (! $this->userId || empty($this->obraIds)) {
            Notification::make()->title('Selecione um usuário e pelo menos uma obra.')->warning()->send();

            return;
        }

        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        $user->update(['is_lider_obra' => true]);
        $user->obrasComoLider()->syncWithoutDetaching($this->obraIds);

        $this->reset(['userId', 'obraIds']);
        $this->loadData();

        Notification::make()->title("Líder {$user->name} adicionado")->success()->send();
    }

    public function editarObras(int $userId): void
    {
        $this->editandoId = $userId;
        $user = User::find($userId);
        $this->editandoObras = $user?->obrasComoLider()->pluck('obras.id')->map(fn ($id) => (string) $id)->toArray() ?? [];
    }

    public function salvarObras(): void
    {
        if (! $this->editandoId) {
            return;
        }

        $user = User::find($this->editandoId);
        if (! $user) {
            return;
        }

        $user->obrasComoLider()->sync($this->editandoObras);

        $this->reset(['editandoId', 'editandoObras']);
        $this->loadData();

        Notification::make()->title('Obras atualizadas')->success()->send();
    }

    public function cancelarEdicao(): void
    {
        $this->reset(['editandoId', 'editandoObras']);
    }

    public function remover(int $userId): void
    {
        $user = User::find($userId);
        if (! $user) {
            return;
        }

        $user->obrasComoLider()->detach();
        $user->update(['is_lider_obra' => false]);

        $this->loadData();

        Notification::make()->title("Líder {$user->name} removido")->warning()->send();
    }
}
