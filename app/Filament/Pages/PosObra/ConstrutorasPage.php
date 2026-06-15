<?php

namespace App\Filament\Pages\PosObra;

use App\Enums\PosObra\TipoConstrutora;
use App\Forms\Components\CnpjInput;
use App\Models\Construtora;
use App\Models\Obras;
use App\Models\PosObra\DisciplinaConfig;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use UnitEnum;

class ConstrutorasPage extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static UnitEnum|string|null $navigationGroup = 'Outros';
    protected static ?string $navigationParentItem = 'Cadastros';

    protected static ?string $navigationLabel = 'Construtoras / Prestadoras';

    protected static ?string $title = 'Construtoras e Prestadoras';

    protected static ?int $navigationSort = 11;

    protected string $view = 'filament.pages.pos-obra.construtoras';

    public array $construtoras = [];

    // Adicionar
    public ?string $nome = null;

    public ?string $cnpj = null;

    public ?string $telefoneWhatsapp = null;

    public ?string $tipo = 'CONSTRUTORA';

    public array $disciplinaIds = [];

    public array $obraIds = [];

    // Editar
    public ?int $editandoId = null;

    public ?string $editandoTipo = null;

    public array $editandoDisciplinas = [];

    public array $editandoObras = [];

    // Filtro
    public string $filtroTipo = '';

    public string $filtroBusca = '';

    public function mount(): void
    {
        $this->loadData();
    }

    public function adicionarForm(Schema $schema): Schema
    {
        return $schema->columns(4)->schema([
            TextInput::make('nome')
                ->label('Nome')
                ->required()
                ->maxLength(255),
            CnpjInput::make('cnpj'),
            Select::make('tipo')
                ->label('Tipo')
                ->options(collect(TipoConstrutora::cases())->mapWithKeys(fn ($e) => [$e->value => $e->label()]))
                ->required(),
            TextInput::make('telefoneWhatsapp')
                ->label('WhatsApp')
                ->tel()
                ->mask('(99) 99999-9999')
                ->maxLength(20),
        ]);
    }

    public function disciplinasForm(Schema $schema): Schema
    {
        return $schema->columns(2)->schema([
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
                ->placeholder('Selecione as obras...'),
            Select::make('disciplinaIds')
                ->label('Disciplinas')
                ->options(
                    DisciplinaConfig::where('ativo', true)
                        ->whereNotNull('label')
                        ->orderBy('ordem')
                        ->pluck('label', 'id')
                )
                ->multiple()
                ->searchable()
                ->preload()
                ->placeholder('Selecione as disciplinas...'),
        ]);
    }

    public function editarDisciplinasForm(Schema $schema): Schema
    {
        return $schema->columns(3)->schema([
            Select::make('editandoTipo')
                ->label('Tipo')
                ->options(collect(TipoConstrutora::cases())->mapWithKeys(fn ($e) => [$e->value => $e->label()]))
                ->required(),
            Select::make('editandoObras')
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
                ->preload(),
            Select::make('editandoDisciplinas')
                ->label('Disciplinas')
                ->options(
                    DisciplinaConfig::where('ativo', true)
                        ->whereNotNull('label')
                        ->orderBy('ordem')
                        ->pluck('label', 'id')
                )
                ->multiple()
                ->searchable()
                ->preload(),
        ]);
    }

    public function loadData(): void
    {
        $query = Construtora::with(['disciplinas:id,label,codigo', 'obras:id,sigla,unidade'])
            ->whereNotNull('nome')
            ->orderBy('nome');

        if ($this->filtroTipo) {
            $query->where('tipo', $this->filtroTipo);
        }

        if ($this->filtroBusca) {
            $query->where('nome', 'like', "%{$this->filtroBusca}%");
        }

        $this->construtoras = $query->get()
            ->map(fn (Construtora $c) => [
                'id' => $c->id,
                'nome' => $c->nome,
                'cnpj' => $c->cnpj,
                'telefone_whatsapp' => $c->telefone_whatsapp,
                'tipo' => $c->tipo?->value ?? 'CONSTRUTORA',
                'tipo_label' => $c->tipo?->label() ?? 'Fornecedor',
                'obras' => $c->obras->map(fn ($o) => [
                    'id' => $o->id,
                    'sigla' => $o->sigla,
                    'unidade' => $o->unidade,
                ])->toArray(),
                'disciplinas' => $c->disciplinas->map(fn ($d) => [
                    'id' => $d->id,
                    'label' => $d->label,
                    'codigo' => $d->codigo,
                ])->toArray(),
            ])
            ->toArray();
    }

    public function updatedFiltroTipo(): void
    {
        $this->loadData();
    }

    public function updatedFiltroBusca(): void
    {
        $this->loadData();
    }

    public function adicionar(): void
    {
        if (! $this->nome) {
            Notification::make()->title('Informe o nome.')->warning()->send();

            return;
        }

        $construtora = Construtora::create([
            'nome' => $this->nome,
            'cnpj' => $this->cnpj,
            'tipo' => $this->tipo,
            'telefone_whatsapp' => $this->telefoneWhatsapp,
        ]);

        if (! empty($this->obraIds)) {
            $construtora->obras()->syncWithoutDetaching($this->obraIds);
        }
        if (! empty($this->disciplinaIds)) {
            $construtora->disciplinas()->sync($this->disciplinaIds);
        }

        $this->reset(['nome', 'cnpj', 'telefoneWhatsapp', 'disciplinaIds', 'obraIds']);
        $this->tipo = 'CONSTRUTORA';
        $this->loadData();

        Notification::make()->title("Fornecedor {$construtora->nome} cadastrada")->success()->send();
    }

    public function editar(int $id): void
    {
        $this->editandoId = $id;
        $c = Construtora::with(['disciplinas', 'obras'])->find($id);
        $this->editandoTipo = $c?->tipo?->value ?? 'CONSTRUTORA';
        $this->editandoObras = $c?->obras->pluck('id')->map(fn ($id) => (string) $id)->toArray() ?? [];
        $this->editandoDisciplinas = $c?->disciplinas->pluck('id')->map(fn ($id) => (string) $id)->toArray() ?? [];
    }

    public function salvarEdicao(): void
    {
        if (! $this->editandoId) {
            return;
        }

        $c = Construtora::find($this->editandoId);
        if (! $c) {
            return;
        }

        $c->update(['tipo' => $this->editandoTipo]);
        $c->obras()->sync($this->editandoObras);
        $c->disciplinas()->sync($this->editandoDisciplinas);

        $this->reset(['editandoId', 'editandoTipo', 'editandoDisciplinas', 'editandoObras']);
        $this->loadData();

        Notification::make()->title('Fornecedor atualizada')->success()->send();
    }

    public function cancelarEdicao(): void
    {
        $this->reset(['editandoId', 'editandoTipo', 'editandoDisciplinas', 'editandoObras']);
    }

    public function remover(int $id): void
    {
        $c = Construtora::find($id);
        if (! $c) {
            return;
        }

        $nome = $c->nome;
        $c->disciplinas()->detach();
        $c->delete();

        $this->loadData();

        Notification::make()->title("Fornecedor {$nome} removida")->warning()->send();
    }
}
