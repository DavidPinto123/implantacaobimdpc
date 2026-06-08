<?php

namespace App\Filament\Resources\RelatorioFotograficos\Tables;

use App\Filament\Resources\RelatorioFotograficos\RelatorioFotograficoResource;
use App\Mail\EnviarPdfMail;
use App\Models\ListaEmail;
use App\Models\RelatorioFotografico;
use App\Services\RelatorioFotograficoPdfService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class RelatorioFotograficosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                $lixeira = session('relatorio_fotografico_view', request()->query('lixeira', 'without'));

                return match ($lixeira) {
                    'only' => $query->onlyTrashed(),
                    default => $query->withoutTrashed(),
                };
            })
            ->columns([
                TextColumn::make('sigla')
                    ->label('Sigla')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('projeto.nome')
                    ->label('Unidade')
                    ->limit(35)
                    ->tooltip(fn ($state) => $state)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('autor.name')
                    ->label('Autor')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'aprovado_com_pendencia' => 'Aprovado com pendência',
                        'concluido' => 'Concluído',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'concluido',
                        'danger' => 'aprovado_com_pendencia',
                    ])
                    ->sortable(),

                TextColumn::make('status_termo_de_posse')
                    ->label('Status Termo de Posse')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pendente' => 'Pendente',
                        'em_validacao' => 'Em validação',
                        'em_assinatura' => 'Em assinatura',
                        'assinado' => 'Assinado',
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'pendente',
                        'info' => 'em_validacao',
                        'primary' => 'em_assinatura',
                        'success' => 'assinado',
                    ])
                    ->sortable(),

                TextColumn::make('agendado_em')
                    ->label('Agendado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->date('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->date('d/m/Y H:i')
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('lixeira')
                    ->label(fn () => 'Lixeira ('.RelatorioFotografico::onlyTrashed()->count().')')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->action(function () {
                        session(['relatorio_fotografico_view' => 'only']);

                        redirect(RelatorioFotograficoResource::getUrl('index', [
                            'lixeira' => 'only',
                        ]));
                    }),

                Action::make('ativos')
                    ->label('Ver ativos')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function () {
                        session(['relatorio_fotografico_view' => 'without']);

                        redirect(RelatorioFotograficoResource::getUrl('index', [
                            'lixeira' => 'without',
                        ]));
                    }),
            ])

            ->filters([

                SelectFilter::make('projeto_id')
                    ->label('Projeto')
                    ->relationship(
                        'projeto',
                        'nome',
                        fn ($query) => $query->whereHas('relatorioFotograficos', function ($q) {
                            if (! auth()->user()->hasRole('gestor_obra')) {
                                $q->where('autor_id', auth()->id());
                            }
                        })
                    )
                    ->searchable()
                    ->preload(),

                SelectFilter::make('autor_id')
                    ->label('Autor')
                    ->relationship(
                        'autor',
                        'name',
                        fn ($query) => $query->whereHas('relatoriosCriados', function ($q) {
                            if (! auth()->user()->hasRole('gestor_obra')) {
                                $q->where('autor_id', auth()->id());
                            }
                        })
                    )
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'rascunho' => 'Rascunho',
                        'aprovado_com_pendencia' => 'Aprovado com pendência',
                        'concluido' => 'Concluído',
                    ]),

            ])->filtersLayout(FiltersLayout::AboveContent)->deferFilters(false)

            ->actions([
                ViewAction::make()->label('')->tooltip('Visualizar'),

                EditAction::make()
                    ->label('')
                    ->tooltip('Editar')
                    ->visible(fn ($record) => ! $record->trashed()),

                RestoreAction::make()
                    ->label('')
                    ->tooltip('Restaurar')
                    ->visible(fn ($record) => $record->trashed()),

                ForceDeleteAction::make()
                    ->label('')
                    ->tooltip('Excluir definitivamente')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn ($record) => $record->trashed())
                    ->requiresConfirmation()
                    ->modalHeading('Excluir permanentemente')
                    ->modalDescription('Essa ação não pode ser desfeita.'),

                Action::make('enviar_email')
                    ->label(' ')
                    ->tooltip('Enviar por email')
                    ->icon('heroicon-o-envelope')
                    ->color('primary')
                    ->visible(fn ($record) => $record->status !== 'Rascunho' && ! $record->trashed())
                    ->form([

                        Select::make('lista_email_id')
                            ->label('Lista de e-mails')
                            ->options(
                                ListaEmail::query()
                                    ->where('ativo', true)
                                    ->orderBy('nome')
                                    ->pluck('nome', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                $lista = ListaEmail::find($state);

                                $emails = $lista?->emails ?? [];

                                $usuarioEmail = Filament::auth()->user()?->email;

                                if ($usuarioEmail) {
                                    $emails[] = $usuarioEmail;
                                }

                                $set('emails', collect($emails)->filter()->unique()->values()->all());
                            }),
                        TagsInput::make('emails')
                            ->label('E-mails')
                            ->placeholder('Digite e pressione enter')
                            ->required()
                            ->rules(['required', 'array'])
                            ->nestedRecursiveRules(['email'])
                            ->validationMessages([
                                'email' => 'Um ou mais e-mails são inválidos.',
                            ])
                            ->suffixAction(
                                Action::make('limparEmails')
                                    ->label('Limpar')
                                    ->icon('heroicon-o-x-mark')
                                    ->color('danger')
                                    ->action(function (Set $set) {
                                        $usuarioEmail = Filament::auth()->user()?->email;

                                        $set('emails', $usuarioEmail ? [$usuarioEmail] : []);
                                    })
                            )
                            ->afterStateUpdated(function ($state, Set $set) {
                                $usuarioEmail = Filament::auth()->user()?->email;

                                $emails = $state ?? [];

                                if ($usuarioEmail) {
                                    $emails[] = $usuarioEmail;
                                }

                                $set('emails', collect($emails)->filter()->unique()->values()->all());
                            }),

                        Hidden::make('assunto')
                            ->required(),

                        Hidden::make('mensagem')
                            ->required(),
                    ])
                    ->fillForm(function ($record) {
                        $entregas = $record->entregas_contratuais ?? [];
                        $premissasHtml = '';

                        if (! empty($entregas) && is_array($entregas)) {
                            $premissasHtml .= '<ul style="margin: 10px 0 10px 20px; padding: 0;">';

                            foreach ($entregas as $entrega) {
                                $titulo = $entrega['titulo'] ?? 'Sem título';
                                $status = $entrega['status'] ?? null;
                                $comentario = $entrega['comentario'] ?? null;
                                $dataPrevista = $entrega['data_prevista'] ?? null;

                                $statusFormatado = match ($status) {
                                    'entregue' => 'Entregue',
                                    'nao_entregue' => 'Não entregue',
                                    default => 'Não informado',
                                };

                                $linha = "<li style='margin-bottom: 8px;'>";
                                $linha .= "<strong>{$titulo}</strong> - {$statusFormatado}";

                                if ($status === 'nao_entregue' && ! empty($dataPrevista)) {
                                    $linha .= ' | Data prevista: '.Carbon::parse($dataPrevista)->format('d/m/Y');
                                }

                                if (! empty($comentario)) {
                                    $linha .= ' | Comentário: '.e($comentario);
                                }

                                $linha .= '</li>';

                                $premissasHtml .= $linha;
                            }

                            $premissasHtml .= '</ul>';
                        } else {
                            $premissasHtml = '<p>Não há premissas contratuais cadastradas.</p>';
                        }

                        return [
                            'lista_email_id' => null,
                            'emails' => [Filament::auth()->user()?->email],

                            'assunto' => 'RELATÓRIO FOTOGRÁFICO DE POSSE DO IMÓVEL - UNIDADE  '.($record->projeto?->nome ?? 'Sem projeto').' '.($record->sigla ?? ' (SEM SIGLA) '),

                            'mensagem' => 'Olá,<br><br>'.
                                'Prezados,<br><br>'.
                                'Segue o relatório fotográfico para compor o Termo de Posse da unidade <strong>'.($record->projeto?->nome ?? 'Não informado').'</strong>.<br><br>'.
                                'Informo que o imóvel foi recebido em <strong>'.
                                (
                                    $record->data_posse
                                    ? Carbon::parse($record->data_posse)->format('d/m/Y')
                                    : 'Não informada'
                                ).
                                '</strong>.<br><br>'.
                                '<strong>Premissas contratuais:</strong><br>'.
                                $premissasHtml.
                                '<br>'.
                                'Imóvel considerado como <strong>liberado</strong> pela engenharia para seguir com as assinaturas do termo.<br><br>'.
                                'Atenciosamente,<br>'.
                                'Gestão Smartfit',
                        ];
                    })
                    ->action(function ($record, array $data, RelatorioFotograficoPdfService $pdfService) {
                        try {
                            $usuario = Filament::auth()->user();
                            $pdf = $pdfService->makePdf($record);
                            $pdfBinary = $pdf->output();
                            /** @var FilesystemAdapter $disk */
                            $disk = Storage::disk((string) config('filesystems.media_disk', 'r2'));

                            $tamanhoMB = strlen($pdfBinary) / 1024 / 1024;
                            $nomeArquivo = RelatorioFotograficoPdfService::pdfFileName($record);

                            $path = RelatorioFotograficoPdfService::pdfStoragePath($record);

                            $disk->put($path, $pdfBinary, [
                                'ContentType' => 'application/pdf',
                            ]);

                            $link = $disk->url($path);
                            $mensagem = $data['mensagem']
                            .'<h4>O arquivo está disponível no link.<br>'
                            .'<a href="'.$link.'" target="_blank">Baixar PDF</a></h4>'
                            .'<h4>Este email foi enviado por,<br>'
                            .($usuario?->name ?? 'Não informado').'<br>'
                            .($usuario?->email ?? '')
                            .'</h4>';

                            Mail::to($data['emails'])->send(
                                new EnviarPdfMail(
                                    assunto: $data['assunto'],
                                    mensagemEmail: $mensagem,
                                    pdfBinary: '',
                                    nomeArquivo: '',
                                )
                            );

                            Notification::make()
                                ->title('E-mail enviado com link do PDF')
                                // ->body('O arquivo tem ' . number_format($tamanhoMB, 2, ',', '.') . ' MB, então foi enviado um link no corpo do e-mail')
                                ->success()
                                ->send();

                            return;

                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Erro ao enviar e-mail')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

            ])->recordActionsPosition(RecordActionsPosition::BeforeCells)

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
