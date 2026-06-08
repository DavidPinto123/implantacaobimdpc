<?php

namespace App\Filament\Resources\AutorizacaoServicos\Tables;

use App\Enums\AsStatus;
use App\Filament\Resources\AutorizacaoServicos\Pages\EditAutorizacaoServico;
use App\Models\AutorizacaoServico;
use App\Models\User;
use App\Services\AutorizacaoServicoFluxoService;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AutorizacaoServicosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (EloquentBuilder $query, $livewire): EloquentBuilder {
                if (filled($livewire->obraFiltro)) {
                    $query->where('obra_id', $livewire->obraFiltro);
                }

                if (filled($livewire->construtoraFiltro)) {
                    $query->where('construtora_id', $livewire->construtoraFiltro);
                }

                return $query;
            })
            ->columns([
                TextColumn::make('numero_as')
                    ->label('N AS')
                    ->limit(24)
                    ->tooltip(fn (AutorizacaoServico $record): ?string => $record->numero_as)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('obra.unidade')
                    ->label('UNIDADE')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('asEscopo.escopo')
                    ->label('ESCOPO')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('fornecedor.nome')
                    ->label('FORNECEDOR')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('obra.sigla')
                    ->label('SIGLA')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('STATUS')
                    ->badge()
                    ->sortable()
                    ->getStateUsing(fn (AutorizacaoServico $record): ?string => self::normalizeStatus($record->status))
                    ->formatStateUsing(fn (?string $state): string => AsStatus::labelFrom($state))
                    ->colors([
                        'gray' => [AsStatus::RASCUNHO->value],
                        'info' => [AsStatus::CRIADA->value, AsStatus::ENVIADA->value],
                        'success' => [AsStatus::ORCADA->value],
                        'danger' => [AsStatus::CANCELADA->value],
                    ]),

                TextColumn::make('asEscopo.grupo')
                    ->label('GRUPO')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('numero_complemento')
                    ->label('COMPL.')
                    ->alignCenter()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('valor_estimado')
                    ->label('ESTIMADO')
                    ->money('BRL')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('valor')
                    ->label('VALOR')
                    ->money('BRL')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('enviado_em')
                    ->label('ENVIO')
                    ->date('d/m/Y')
                    ->alignCenter()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('enviar_as')
                    ->label('')
                    ->tooltip('Enviar AS')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn (AutorizacaoServico $record): bool => in_array($record->status, [
                        AsStatus::CRIADA,
                        AsStatus::EM_ORCAMENTO,
                    ], true)
                        && (bool) Auth::user()?->can('Update:AutorizacaoServico'))
                    ->schema(self::schemaEnviarAs())
                    ->fillForm(fn (AutorizacaoServico $record, AutorizacaoServicoFluxoService $service): array => self::dadosPadraoEnvioAs($record, $service))
                    ->action(function (AutorizacaoServico $record, array $data, AutorizacaoServicoFluxoService $service): void {
                        $user = Auth::user();

                        if (! $user) {
                            Notification::make()
                                ->title('Usuário não autenticado')
                                ->danger()
                                ->send();

                            return;
                        }

                        try {
                            $service->enviar(
                                $record,
                                $user,
                                destinatarios: $data['para'] ?? [],
                                copias: $data['cc'] ?? [],
                                copiasOcultas: $data['cco'] ?? [],
                            );
                        } catch (DomainException $exception) {
                            Notification::make()
                                ->title('AS não enviada')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('AS enviada')
                            ->success()
                            ->send();
                    }),
                Action::make('visualizar_as')
                    ->label('')
                    ->tooltip('Visualizar AS')
                    ->icon('heroicon-o-eye')
                    ->visible(fn (AutorizacaoServico $record): bool => (bool) Auth::user()?->can('View:AutorizacaoServico'))
                    ->url(fn (AutorizacaoServico $record): string => EditAutorizacaoServico::getUrl(['record' => $record])),
            ])
            ->recordActionsPosition(RecordActionsPosition::BeforeCells)
            ->defaultSort('id', 'desc');
    }

    protected static function normalizeStatus(AsStatus|string|null $status): ?string
    {
        if ($status instanceof AsStatus) {
            return $status === AsStatus::EM_ORCAMENTO ? AsStatus::CRIADA->value : $status->value;
        }

        if (blank($status)) {
            return null;
        }

        $normalized = (string) Str::of($status)
            ->ascii()
            ->lower()
            ->trim()
            ->replace(' ', '_');

        return $normalized === AsStatus::EM_ORCAMENTO->value
            ? AsStatus::CRIADA->value
            : $normalized;
    }

    /**
     * @return array<int, mixed>
     */
    protected static function schemaEnviarAs(): array
    {
        return [
            Select::make('para')
                ->label('Para')
                ->placeholder('Digite para buscar usuários ou fornecedores')
                ->options(fn (?AutorizacaoServico $record): array => self::emailOptionsEnvioAs($record))
                ->multiple()
                ->searchable()
                ->native(false)
                ->preload()
                ->rules(['nullable', 'array'])
                ->nestedRecursiveRules(['email'])
                ->validationMessages(['email' => 'Um ou mais e-mails são inválidos.']),

            Select::make('cc')
                ->label('CC')
                ->placeholder('Digite para buscar usuários ou fornecedores')
                ->options(fn (?AutorizacaoServico $record): array => self::emailOptionsEnvioAs($record))
                ->multiple()
                ->searchable()
                ->native(false)
                ->preload()
                ->rules(['nullable', 'array'])
                ->nestedRecursiveRules(['email'])
                ->validationMessages(['email' => 'Um ou mais e-mails são inválidos.']),

            Select::make('cco')
                ->label('CCO')
                ->placeholder('Digite para buscar usuários ou fornecedores')
                ->options(fn (?AutorizacaoServico $record): array => self::emailOptionsEnvioAs($record))
                ->multiple()
                ->searchable()
                ->native(false)
                ->preload()
                ->rules(['nullable', 'array'])
                ->nestedRecursiveRules(['email'])
                ->validationMessages(['email' => 'Um ou mais e-mails são inválidos.']),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function emailOptionsEnvioAs(?AutorizacaoServico $record): array
    {
        $service = app(AutorizacaoServicoFluxoService::class);
        $record?->loadMissing('construtora');
        $emailOptions = [];

        if ($record) {
            foreach ($service->destinatariosFornecedor($record) as $email) {
                $emailOptions[$email] = "{$record->construtora?->nome} <{$email}>";
            }
        }

        User::query()
            ->where('is_active', true)
            ->whereNotNull('email')
            ->orderBy('name')
            ->get(['name', 'email'])
            ->each(function (User $user) use (&$emailOptions, $service): void {
                foreach ($service->normalizarEmails([(string) $user->email]) as $email) {
                    $emailOptions[$email] = "{$user->name} <{$email}>";
                }
            });

        return $emailOptions;
    }

    /**
     * @return array{para: array<int, string>, cc: array<int, string>, cco: array<int, string>}
     */
    protected static function dadosPadraoEnvioAs(AutorizacaoServico $record, AutorizacaoServicoFluxoService $service): array
    {
        return [
            'para' => $service->destinatariosFornecedor($record),
            'cc' => $service->emailsGestorProjeto($record),
            'cco' => [],
        ];
    }
}
