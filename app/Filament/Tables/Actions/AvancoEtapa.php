<?php

namespace App\Filament\Tables\Actions;

use App\Models\EtapaProjeto;
use App\Models\HistoricoProjeto;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class AvancoEtapa
{
    public static function make(): Action
    {
        return self::makeAction();
    }

    public static function makeAction(): Action
    {
        return Action::make('avancar_fase')
            ->label('')
            ->tooltip('Clique para avançar o projeto para a próxima fase')
            ->icon('heroicon-o-arrow-right-circle')
            ->visible(function ($livewire) {
                // Só esconde/mostra por tab quando estivermos numa página com tabs (ListProjetos)
                if (property_exists($livewire, 'activeTab')) {
                    // ATENÇÃO: aqui usamos a **chave** da tab definida em ListProjetos->getTabs()
                    return $livewire->activeTab === 'Prospecção';
                }

                // Em outras telas (sem tabs), mantemos a coluna visível
                return true;
            })
            ->color('green')
            ->requiresConfirmation()
            ->modalHeading('Confirmação')
            ->modalDescription(fn ($record) => 'Você tem certeza que deseja avançar o '.$record->nome.' para a Reunião de comitê ?')
            ->action(function ($record) {
                $etapaProjeto = EtapaProjeto::where('projeto_id', $record->id)
                    ->where('etapa_id', 1) // pega a etapa Prospecção
                    ->first();

                if (! $etapaProjeto) {
                    Notification::make()
                        ->title("Projeto {$record->nome} não possui etapa vinculada.")
                        ->danger()
                        ->send();

                    return;
                }

                // verifica se já existe Reunião de Comitê
                $temReuniao = EtapaProjeto::where('projeto_id', $record->id)
                    ->where('etapa_id', 2)
                    ->exists();

                // se já está em reunião de comitê, não avança
                if ($temReuniao) {
                    Notification::make()
                        ->title("Projeto {$record->nome} já está na etapa Reunião de Comitê!")
                        ->danger()
                        ->send();

                    return;
                }

                if ($etapaProjeto->etapa_id == 1) {
                    $faseAntiga = $etapaProjeto->etapa?->nome;
                    $etapaProjeto->etapa_id = 2; // Reunião de Comitê
                    $etapaProjeto->save();

                    $etapaProjeto->load('etapa');
                    $faseNova = $etapaProjeto->etapa?->nome;

                    HistoricoProjeto::create([
                        'projeto_id' => $record->id,
                        'usuario_id' => auth()->id() ?? 1,
                        'setor' => auth()->user()?->roles->pluck('name')->first() ?? 'Desconhecido',
                        'status' => $record->status ?? 'pendente',
                        'etapa' => $etapaProjeto->etapa?->nome,
                        'acao' => 'alterou_fase',
                        'descricao' => "Projeto avançado da etapa {$faseAntiga} para {$faseNova}",
                        'fase_antiga' => $faseAntiga,
                        'fase_nova' => $faseNova,
                        'fase' => $faseNova,
                    ]);

                    $user = Auth::user();

                    if ($user->hasAnyRole('Planejamento Estratégico', 'PMO', 'Comercial', 'super_admin')) {

                        $destinatarios = User::role([
                            'Planejamento Estratégico',
                            'PMO',
                            'Comercial',
                            'super_admin',
                        ])->get();

                        Notification::make()
                            ->title('Projeto Atualizado')
                            ->body("Projeto {$record->nome} avançado para a etapa {$faseNova}!")
                            ->icon('heroicon-o-pencil-square')
                            ->warning()
                            ->sendToDatabase($destinatarios);
                    }

                    Notification::make()
                        ->title("Projeto {$record->nome} avançado para a etapa {$faseNova}!")
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title("Projeto {$record->nome} não está em Prospecção, não pode avançar.")
                        ->danger()
                        ->send();
                }
            });
    }

    public static function makeBulkAction(): BulkAction
    {
        return BulkAction::make('avancar_fase_bulk')
            ->label('Avançar fase')
            ->icon('heroicon-o-arrow-right-circle')
            ->color('green')
            ->requiresConfirmation()
            ->modalHeading('Confirmação')
            ->modalDescription(fn ($records) => 'Você tem certeza que deseja avançar '.count($records).' projeto(s) para a Reunião de comitê ?')
            ->visible(function ($livewire) {
                // Só esconde/mostra por tab quando estivermos numa página com tabs (ListProjetos)
                if (property_exists($livewire, 'activeTab')) {
                    // ATENÇÃO: aqui usamos a **chave** da tab definida em ListProjetos->getTabs()
                    return $livewire->activeTab === 'Prospecção';
                }

                // Em outras telas (sem tabs), mantemos a coluna visível
                return true;
            })
            ->action(function ($records) {
                $idsAvancados = [];
                $naoAvancados = [];
                $jaTemReuniao = [];

                foreach ($records as $record) {
                    $etapaProjeto = EtapaProjeto::where('projeto_id', $record->id)->first();

                    if (! $etapaProjeto) {
                        $naoAvancados[] = $record->nome;

                        continue;
                    }

                    // verifica se já tem a etapa de Reunião de Comitê (ex: etapa_id = 2)
                    $temReuniao = EtapaProjeto::where('projeto_id', $record->id)
                        ->where('etapa_id', 2) // <-- id da etapa "Reunião de Comitê"
                        ->exists();

                    if ($temReuniao) {
                        $jaTemReuniao[] = $record->nome;

                        continue;
                    }

                    if ($etapaProjeto->etapa_id == 1) {
                        $faseAntiga = $etapaProjeto->etapa?->nome;
                        $etapaProjeto->etapa_id = 2; // Reunião de Comitê
                        $etapaProjeto->save();

                        $etapaProjeto->load('etapa');
                        $faseNova = $etapaProjeto->etapa?->nome;

                        HistoricoProjeto::create([
                            'projeto_id' => $record->id,
                            'usuario_id' => auth()->id() ?? 1,
                            'setor' => auth()->user()?->roles->pluck('name')->first() ?? 'Desconhecido',
                            'acao' => 'alterou_fase',
                            'fase_antiga' => $faseAntiga,
                            'fase_nova' => $faseNova,
                            'status' => $record->status ?? 'pendente',
                            'etapa' => $etapaProjeto->etapa?->nome,
                            'created_at' => now(),
                            'updated_at' => now(),
                            'fase' => $faseNova,
                        ]);

                        $idsAvancados[] = $record->nome;
                    } else {
                        $naoAvancados[] = $record->nome;
                    }
                }

                if (! empty($idsAvancados)) {

                    $destinatarios = User::role([
                        'Planejamento Estratégico',
                        'PMO',
                        'Comercial',
                        'super_admin',
                    ])->get();
                    $listaProjetos = implode(', ', $idsAvancados);
                    foreach ($destinatarios as $destinatario) {
                        Notification::make()
                            ->title('Projetos Atualizados')
                            ->body("Os projetos [{$listaProjetos}] foram avançados para a etapa {$faseNova}!")
                            ->icon('heroicon-o-pencil-square')
                            ->warning()
                            ->sendToDatabase($destinatario);
                    }
                    Notification::make()
                        ->title(count($idsAvancados).' projeto(s) avançados!')
                        ->body(implode(', ', $idsAvancados))
                        ->success()
                        ->send();
                }

                if (! empty($naoAvancados)) {
                    Notification::make()
                        ->title('Projetos não avançados por não estarem na etapa Prospecção:')
                        ->body(implode(', ', $naoAvancados))
                        ->warning()
                        ->send();
                }

                if (! empty($jaTemReuniao)) {
                    Notification::make()
                        ->title('Projetos não avançados por já possuírem etapa "Reunião de Comitê":')
                        ->body(implode(', ', $jaTemReuniao))
                        ->danger()
                        ->send();
                }
            });
    }
}
