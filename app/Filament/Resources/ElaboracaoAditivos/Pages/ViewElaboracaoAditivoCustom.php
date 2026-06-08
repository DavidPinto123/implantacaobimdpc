<?php

namespace App\Filament\Resources\ElaboracaoAditivos\Pages;

use App\Exports\ElaboracaoAditivoPlanilhaExport;
use App\Filament\Resources\Asas\AsaResource;
use App\Filament\Resources\ElaboracaoAditivos\ElaboracaoAditivoResource;
use App\Mail\EnviarPdfMail;
use App\Models\Asa;
use App\Models\ElaboracaoAditivo;
use App\Models\Obras;
use App\Models\User;
use App\Services\AsaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ViewElaboracaoAditivoCustom extends Page
{
    protected static string $resource = ElaboracaoAditivoResource::class;

    protected string $view = 'filament.resources.elaboracao-aditivos.pages.view-elaboracao-aditivo-custom';

    protected static ?string $title = '';

    public ElaboracaoAditivo $record;

    public function mount(ElaboracaoAditivo $record): void
    {
        $this->record = $record->load([
            'obra',
            'construtora',
            'gestor',
            'asEscopo',
            'itens',
        ]);
        $this->record->setRelation(
            'obra',
            Obras::withoutGlobalScopes()->find($this->record->obra_id)
        );
    }

    public function getTitle(): string
    {
        return 'ELABORAÇÃO DE ADITIVOS DE OBRA';
    }

    public function getBreadcrumb(): string
    {
        return 'Planilha de aditivos';
    }

    public function getHeading(): string
    {
        return 'ELABORAÇÃO DE ADITIVOS DE OBRA';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('criarAsa')
                ->label('Enviar para Gestor')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->visible(fn () => ! $this->hasAsaCriada())
                ->form([
                    Textarea::make('justificativa')
                        ->label('Justificativa da ASA')
                        ->required()
                        ->placeholder('Digite a justificativa para criação desta ASA...')
                        ->rows(4)
                        ->maxLength(1000),
                ])
                ->action(function (array $data) {
                    try {
                        $this->record->load([
                            'obra',
                            'construtora',
                            'asEscopo',
                            'itens',
                        ]);

                        $asaService = app(AsaService::class);
                        $asa = $asaService->criarAPartirDoAditivo($this->record, trim($data['justificativa'] ?? ''));

                        $usuarioEngenharia = null;

                        if ($this->record->obra && filled($this->record->obra->engenharia)) {
                            $usuarioEngenharia = User::query()
                                ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($this->record->obra->engenharia))])
                                ->first();
                        }

                        if ($usuarioEngenharia) {
                            $mensagem = 'O fornecedor '.($this->record->construtora?->nome ?? '-').
                                ' criou a ASA '.$asa->numero_asa.
                                ' para a unidade '.($this->record->obra?->unidade ?? '-').'.';

                            Notification::make()
                                ->title('Nova ASA criada')
                                ->icon('heroicon-o-document-plus')
                                ->body($mensagem)
                                ->actions([
                                    Action::make('ver')
                                        ->label('Ver ASA')
                                        ->icon('heroicon-o-eye')
                                        ->url(AsaResource::getUrl('edit', [
                                            'record' => $asa->id,
                                        ]))
                                        ->markAsRead(),
                                ])
                                ->sendToDatabase($usuarioEngenharia);

                            if (filled($usuarioEngenharia->email)) {
                                Mail::to($usuarioEngenharia->email)
                                    ->send(new EnviarPdfMail(
                                        assunto: 'Nova ASA criada '.$asa->numero_asa,
                                        mensagemEmail: '<p>'.e($mensagem).'</p>',
                                        pdfBinary: '',
                                        nomeArquivo: '',
                                    ));
                            }
                        }

                        Notification::make()
                            ->title('Aditivo enviado para o gestor')
                            ->body('A ASA foi criada e enviada para aprovação do gestor.')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Log::error('Erro ao criar ASA a partir do aditivo', [
                            'aditivo_id' => $this->record->id,
                            'message' => $e->getMessage(),
                        ]);

                        Notification::make()
                            ->title('Erro ao enviar para o gestor')
                            ->body('Não foi possível criar a ASA para aprovação. Verifique os dados e tente novamente.')
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('exportarExcel')
                ->label('Exportar Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $unidade = $this->record->obra?->unidade ?? 'sem-unidade';
                    $escopo = $this->record->asEscopo?->escopo ?? 'sem-escopo';

                    $nomeArquivo = Str::of($unidade.' - '.$escopo)
                        ->ascii()
                        ->replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-')
                        ->replace('  ', ' ')
                        ->trim()
                        ->lower()
                        ->append('.xlsx')
                        ->toString();

                    return Excel::download(
                        new ElaboracaoAditivoPlanilhaExport($this->record->id),
                        $nomeArquivo
                    );
                }),

            Action::make('exportarPdf')
                ->label('Exportar PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action(function () {
                    $record = $this->record->load([
                        'obra',
                        'construtora',
                        'gestor',
                        'asEscopo',
                        'itens',
                    ]);

                    $unidade = $record->obra?->unidade ?? 'sem-unidade';
                    $escopo = $record->asEscopo?->escopo ?? 'sem-escopo';

                    $nomeArquivo = Str::slug($unidade.' '.$escopo).'.pdf';

                    $pdf = Pdf::loadView('pdf.elaboracao-aditivo', [
                        'record' => $record,
                    ])->setPaper('a4', 'landscape');

                    return response()->streamDownload(
                        fn () => print ($pdf->output()),
                        $nomeArquivo
                    );
                }),

            EditAction::make()
                ->record($this->record),

            DeleteAction::make()
                ->record($this->record),
        ];
    }

    protected function hasAsaCriada(): bool
    {
        return Asa::query()
            ->where('elaboracao_aditivo_id', $this->record->id)
            ->exists();
    }
}
