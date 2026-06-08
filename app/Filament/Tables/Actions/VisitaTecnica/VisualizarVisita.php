<?php

namespace App\Filament\Tables\Actions\VisitaTecnica;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Wizard\Step;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Storage;

class VisualizarVisita
{
    public static function make(): Action
    {
        return Action::make('visualizar')
            ->skippableSteps()
            ->steps([
                Step::make('Informações Iniciais')
                    ->schema([
                        Section::make('Dados do Relatório')
                            ->schema([
                                Grid::make(2)->schema([
                                    Select::make('projeto_id')
                                        ->label('Projeto')
                                        ->default(fn ($record) => optional($record->projeto)->id)
                                        ->relationship('projeto', 'nome')
                                        ->disabled(),
                                    TextInput::make('numero_relatorio_vt')
                                        ->label('Número do relatório')
                                        ->default(fn ($record) => $record->numero_relatorio_vt)
                                        ->disabled(),
                                    DatePicker::make('iniciado_em')
                                        ->label('Iniciado em')
                                        ->default(fn ($record) => $record->iniciado_em)
                                        ->disabled(),
                                    DatePicker::make('concluido_em')
                                        ->label('Concluído em')
                                        ->default(fn ($record) => $record->concluido_em)
                                        ->disabled(),
                                    DatePicker::make('sicronizado_em')
                                        ->label('Sincronizado em')
                                        ->default(fn ($record) => $record->sicronizado_em)
                                        ->disabled(),
                                    TextInput::make('autor')
                                        ->label('Autor')
                                        ->default(fn ($record) => $record->autor)
                                        ->disabled(),
                                    TextInput::make('unidade_relatorio')
                                        ->label('Unidade do relatório')
                                        ->default(fn ($record) => $record->unidade_relatorio)
                                        ->disabled(),
                                ]),
                            ]),
                    ]),
                Step::make('Área 1 – Informações Técnicas')
                    ->schema([
                        Section::make('Dados Técnicos da Unidade')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('unidade')
                                        ->label('Unidade')
                                        ->default(fn ($record) => $record->unidade)
                                        ->disabled(),
                                    TextInput::make('marca')
                                        ->label('Marca')
                                        ->default(fn ($record) => $record->marca)
                                        ->disabled(),
                                    TextInput::make('endereco')
                                        ->label('Endereço')
                                        ->default(fn ($record) => $record->endereco)
                                        ->disabled(),
                                    TextInput::make('responsavel_tecnico')
                                        ->label('Responsável técnico')
                                        ->default(fn ($record) => $record->responsavel_tecnico)
                                        ->disabled(),
                                    TextInput::make('prazo_de_obras')
                                        ->label('Prazo de obras')
                                        ->default(fn ($record) => $record->prazo_de_obras)
                                        ->disabled(),
                                    TextInput::make('link_drive_fotos_e_videos')
                                        ->label('Link do Drive (fotos e vídeos)')
                                        ->default(fn ($record) => $record->link_drive_fotos_e_videos)
                                        ->url()
                                        ->disabled(),
                                ]),
                            ]),
                    ]),
                Step::make('Área 2 – Elétrica / Telefonia / Internet')
                    ->schema([
                        Section::make('Energia Principal')
                            ->schema([
                                Grid::make(2)->schema([
                                    ToggleButtons::make('entrada_de_energia')
                                        ->label('Entrada de energia 220/380V - 150Kva')
                                        ->options([
                                            true => 'Sim',
                                            false => 'Não',
                                            'na' => 'Não se aplica',
                                        ])
                                        ->colors([
                                            true => 'success',
                                            false => 'danger',
                                            'na' => 'gray',
                                        ])
                                        ->inline()
                                        ->default(function ($record) {
                                            return match (true) {
                                                $record->entrada_de_energia === true, $record->entrada_de_energia === 1 => true,
                                                $record->entrada_de_energia === false, $record->entrada_de_energia === 0 => false,
                                                $record->entrada_de_energia === null => 'na',
                                                default => 'na',
                                            };
                                        })
                                        ->inline()
                                        ->disabled(),

                                    Textarea::make('descricao_energia')
                                        ->label('Descrição da entrada de energia')
                                        ->default(fn ($record) => $record->descricao_energia)
                                        ->disabled(),

                                    Section::make('Imagens - Elétrica / Telefonia / Internet')
                                        ->schema([
                                            Placeholder::make('foto_entrada_de_energia')
                                                ->label('Foto da entrada de energia')
                                                ->content(
                                                    fn ($record) => $record && $record->foto_entrada_de_energia
                                                        ? collect($record->foto_entrada_de_energia)
                                                            ->map(fn ($path) => '<img src="http://127.0.0.1:8000'.Storage::url($path).'" class="rounded-lg w-32 inline-block m-1">')
                                                            ->implode('')
                                                        : 'Sem imagem'
                                                )
                                                ->extraAttributes(['class' => 'prose'])
                                                ->columnSpanFull(),
                                        ])
                                        ->columns(2)
                                        ->collapsible()
                                        ->collapsed(fn () => true),

                                    ToggleButtons::make('energia_provisoria')
                                        ->label('Energia provisória para obra')
                                        ->options([
                                            true => 'Sim',
                                            false => 'Não',
                                            'na' => 'Não se aplica',
                                        ])
                                        ->colors([
                                            true => 'success',   // Verde
                                            false => 'danger',   // Vermelho
                                            'na' => 'gray',
                                        ])
                                        ->inline()
                                        ->default(function ($record) {
                                            return match (true) {
                                                $record->energia_provisoria === true, $record->energia_provisoria === 1 => true,
                                                $record->energia_provisoria === false, $record->energia_provisoria === 0 => false,
                                                $record->energia_provisoria === null => 'na',
                                                default => 'na',
                                            };
                                        })
                                        ->disabled(),
                                    Textarea::make('descricao_energia_provisoria')
                                        ->label('Descrição da energia provisória')
                                        ->default(fn ($record) => $record->descricao_energia_provisoria)
                                        ->disabled(),
                                ]),
                            ]),

                        Section::make('Medição e Proteção')
                            ->schema([
                                Grid::make(2)->schema([
                                    ToggleButtons::make('unica_medicao')
                                        ->label('Única medição?')
                                        ->options([
                                            true => 'Sim',
                                            false => 'Não',
                                            'na' => 'Não se aplica',
                                        ])
                                        ->colors([
                                            true => 'success',   // Verde
                                            false => 'danger',   // Vermelho
                                            'na' => 'gray',
                                        ])
                                        ->inline()
                                        ->default(function ($record) {
                                            return match (true) {
                                                $record->unica_medicao === true, $record->unica_medicao === 1 => true,
                                                $record->unica_medicao === false, $record->unica_medicao === 0 => false,
                                                $record->unica_medicao === null => 'na',
                                                default => 'na',
                                            };
                                        })
                                        ->disabled(),
                                    Textarea::make('descricao_medicao')
                                        ->label('Descrição da medição')
                                        ->default(fn ($record) => $record->descricao_medicao)
                                        ->disabled(),
                                    ToggleButtons::make('spda')
                                        ->label('SPDA (Sistema de Proteção contra Descargas Atmosféricas)?')
                                        ->options([
                                            true => 'Sim',
                                            false => 'Não',
                                            'na' => 'Não se aplica',
                                        ])
                                        ->colors([
                                            true => 'success',   // Verde
                                            false => 'danger',   // Vermelho
                                            'na' => 'gray',
                                        ])
                                        ->inline()
                                        ->default(function ($record) {
                                            return match (true) {
                                                $record->spda === true, $record->spda === 1 => true,
                                                $record->spda === false, $record->spda === 0 => false,
                                                $record->spda === null => 'na',
                                                default => 'na',
                                            };
                                        })
                                        ->disabled(),
                                    Textarea::make('descricao_spda')
                                        ->label('Descrição do SPDA')
                                        ->default(fn ($record) => $record->descricao_spda)
                                        ->disabled(),
                                ]),
                            ]),
                        Section::make('Telefonia')
                            ->schema([
                                Grid::make(2)->schema([
                                    ToggleButtons::make('telegonia_dg')
                                        ->label('Telefonia (DG) disponível?')
                                        ->options([
                                            true => 'Sim',
                                            false => 'Não',
                                            'na' => 'Não se aplica',
                                        ])
                                        ->colors([
                                            true => 'success',   // Verde
                                            false => 'danger',   // Vermelho
                                            'na' => 'gray',
                                        ])
                                        ->inline()
                                        ->default(function ($record) {
                                            return match (true) {
                                                $record->telegonia_dg === true, $record->telegonia_dg === 1 => true,
                                                $record->telegonia_dg === false, $record->telegonia_dg === 0 => false,
                                                $record->telegonia_dg === null => 'na',
                                                default => 'na',
                                            };
                                        })
                                        ->disabled(),
                                    Textarea::make('descricao_telefonia')
                                        ->label('Descrição da telefonia')
                                        ->default(fn ($record) => $record->descricao_telefonia)
                                        ->disabled(),
                                ]),
                            ]),
                    ]),
                Step::make('Área 3 - Estrutura / Cobertura / Acústica')
                    ->schema([
                        Section::make('Estrutura')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('tipo_estrutura')
                                        ->label('Tipo da estrutura')
                                        ->default(fn ($record) => $record->tipo_estrutura)
                                        ->disabled()
                                        ->columnSpanFull(),
                                    ToggleButtons::make('necessario_estrutura_auxiliar')
                                        ->label('Necessário estrutura auxiliar')
                                        ->options([
                                            true => 'Sim',
                                            false => 'Não',
                                            'na' => 'Não se aplica',
                                        ])
                                        ->colors([
                                            true => 'success',   // Verde
                                            false => 'danger',   // Vermelho
                                            'na' => 'gray',
                                        ])
                                        ->inline()
                                        ->default(function ($record) {
                                            return match (true) {
                                                $record->necessario_estrutura_auxiliar === true, $record->necessario_estrutura_auxiliar === 1 => true,
                                                $record->necessario_estrutura_auxiliar === false, $record->necessario_estrutura_auxiliar === 0 => false,
                                                $record->necessario_estrutura_auxiliar === null => 'na',
                                                default => 'na',
                                            };
                                        })
                                        ->disabled(),
                                    Textarea::make('descricao_estrutura_auxiliar')
                                        ->label('Descrição da estrutura auxiliar')
                                        ->default(fn ($record) => $record->descricao_estrutura_auxiliar)
                                        ->disabled(),
                                    ToggleButtons::make('estrutura_fachada')
                                        ->label('Estrutura da fachada')
                                        ->options([
                                            true => 'Sim',
                                            false => 'Não',
                                            'na' => 'Não se aplica',
                                        ])
                                        ->colors([
                                            true => 'success',   // Verde
                                            false => 'danger',   // Vermelho
                                            'na' => 'gray',
                                        ])
                                        ->inline()
                                        ->default(function ($record) {
                                            return match (true) {
                                                $record->estrutura_fachada === true, $record->estrutura_fachada === 1 => true,
                                                $record->estrutura_fachada === false, $record->estrutura_fachada === 0 => false,
                                                $record->estrutura_fachada === null => 'na',
                                                default => 'na',
                                            };
                                        })
                                        ->disabled(),
                                    Textarea::make('descricao_estrutura_fachada')
                                        ->label('Descrição da estrutura de fachada')
                                        ->default(fn ($record) => $record->descricao_estrutura_fachada)
                                        ->disabled(),
                                ]),
                            ]),
                        Section::make('Cobertura')
                            ->schema([
                                Grid::make(2)->schema([
                                    ToggleButtons::make('cobertura_isolamento')
                                        ->label('Cobertura com isolamento térmico')
                                        ->options([
                                            true => 'Sim',
                                            false => 'Não',
                                            'na' => 'Não se aplica',
                                        ])
                                        ->colors([
                                            true => 'success',   // Verde
                                            false => 'danger',   // Vermelho
                                            'na' => 'gray',
                                        ])
                                        ->inline()
                                        ->default(function ($record) {
                                            return match (true) {
                                                $record->cobertura_isolamento === true, $record->cobertura_isolamento === 1 => true,
                                                $record->cobertura_isolamento === false, $record->cobertura_isolamento === 0 => false,
                                                $record->cobertura_isolamento === null => 'na',
                                                default => 'na',
                                            };
                                        })
                                        ->disabled(),
                                    Textarea::make('descricao_cobertura_isolamento')
                                        ->label('Descrição do isolamento da cobertura')
                                        ->default(fn ($record) => $record->descricao_cobertura_isolamento)
                                        ->disabled(),
                                ]),
                            ]),
                        Section::make('Lajes e Sobrecargas')
                            ->schema([
                                Grid::make(2)->schema([
                                    ToggleButtons::make('permitidas_furacoes_laje')
                                        ->label('Permitidas furações de laje')
                                        ->options([
                                            true => 'Sim',
                                            false => 'Não',
                                            'na' => 'Não se aplica',
                                        ])
                                        ->colors([
                                            true => 'success',   // Verde
                                            false => 'danger',   // Vermelho
                                            'na' => 'gray',
                                        ])
                                        ->inline()
                                        ->default(function ($record) {
                                            return match (true) {
                                                $record->permitidas_furacoes_laje === true, $record->permitidas_furacoes_laje === 1 => true,
                                                $record->permitidas_furacoes_laje === false, $record->permitidas_furacoes_laje === 0 => false,
                                                $record->permitidas_furacoes_laje === null => 'na',
                                                default => 'na',
                                            };
                                        })
                                        ->disabled(),
                                    Textarea::make('descricao_furacoes_laje')
                                        ->label('Descrição das furações na laje')
                                        ->default(fn ($record) => $record->descricao_furacoes_laje)
                                        ->disabled(),
                                    ToggleButtons::make('sobrecarga_minima_laje')
                                        ->label('Sobrecarga mínima da laje (500kg/m²)')
                                        ->options([
                                            true => 'Sim',
                                            false => 'Não',
                                            'na' => 'Não se aplica',
                                        ])
                                        ->colors([
                                            true => 'success',   // Verde
                                            false => 'danger',   // Vermelho
                                            'na' => 'gray',
                                        ])
                                        ->inline()
                                        ->default(function ($record) {
                                            return match (true) {
                                                $record->sobrecarga_minima_laje === true, $record->sobrecarga_minima_laje === 1 => true,
                                                $record->sobrecarga_minima_laje === false, $record->sobrecarga_minima_laje === 0 => false,
                                                $record->sobrecarga_minima_laje === null => 'na',
                                                default => 'na',
                                            };
                                        })
                                        ->disabled(),
                                    Textarea::make('descricao_sobrecarga_minima_laje')
                                        ->label('Descrição da sobrecarga da laje')
                                        ->default(fn ($record) => $record->descricao_sobrecarga_minima_laje)
                                        ->disabled(),
                                    ToggleButtons::make('sobrecarga_minima_laje_teto')
                                        ->label('Sobrecarga mínima de laje de teto (35kg/m²)')
                                        ->options([
                                            true => 'Sim',
                                            false => 'Não',
                                            'na' => 'Não se aplica',
                                        ])
                                        ->colors([
                                            true => 'success',   // Verde
                                            false => 'danger',   // Vermelho
                                            'na' => 'gray',
                                        ])
                                        ->inline()
                                        ->default(function ($record) {
                                            return match (true) {
                                                $record->sobrecarga_minima_laje_teto === true, $record->sobrecarga_minima_laje_teto === 1 => true,
                                                $record->sobrecarga_minima_laje_teto === false, $record->sobrecarga_minima_laje_teto === 0 => false,
                                                $record->sobrecarga_minima_laje_teto === null => 'na',
                                                default => 'na',
                                            };
                                        })
                                        ->disabled(),
                                    Textarea::make('descricao_sobrecarga_minima_laje_teto')
                                        ->label('Descrição da sobrecarga no teto')
                                        ->default(fn ($record) => $record->descricao_sobrecarga_minima_laje_teto)
                                        ->disabled(),
                                ]),
                            ]),
                        Section::make('Exaustão e Vedação')
                            ->schema([
                                Grid::make(2)->schema([
                                    ToggleButtons::make('local_tomada_ar_externo_exaustao')
                                        ->label('Existe local para tomada de ar externo/ exaustão ')
                                        ->options([
                                            true => 'Sim',
                                            false => 'Não',
                                            'na' => 'Não se aplica',
                                        ])
                                        ->colors([
                                            true => 'success',   // Verde
                                            false => 'danger',   // Vermelho
                                            'na' => 'gray',
                                        ])
                                        ->inline()
                                        ->default(function ($record) {
                                            return match (true) {
                                                $record->local_tomada_ar_externo_exaustao === true, $record->local_tomada_ar_externo_exaustao === 1 => true,
                                                $record->local_tomada_ar_externo_exaustao === false, $record->local_tomada_ar_externo_exaustao === 0 => false,
                                                $record->local_tomada_ar_externo_exaustao === null => 'na',
                                                default => 'na',
                                            };
                                        })
                                        ->disabled(),
                                    Textarea::make('descricao_local_tomada_ar_externo_exaustao')
                                        ->label('Descrição do ponto de exaustão/ar')
                                        ->default(fn ($record) => $record->descricao_local_tomada_ar_externo_exaustao)
                                        ->disabled(),
                                    ToggleButtons::make('alvenaria_periferia_existente')
                                        ->label('Alvenaria de periferia existente')
                                        ->options([
                                            true => 'Sim',
                                            false => 'Não',
                                            'na' => 'Não se aplica',
                                        ])
                                        ->colors([
                                            true => 'success',   // Verde
                                            false => 'danger',   // Vermelho
                                            'na' => 'gray',
                                        ])
                                        ->inline()
                                        ->default(function ($record) {
                                            return match (true) {
                                                $record->alvenaria_periferia_existente === true, $record->alvenaria_periferia_existente === 1 => true,
                                                $record->alvenaria_periferia_existente === false, $record->alvenaria_periferia_existente === 0 => false,
                                                $record->alvenaria_periferia_existente === null => 'na',
                                                default => 'na',
                                            };
                                        })
                                        ->disabled(),
                                    Textarea::make('descricao_alvenaria_periferia_existente')
                                        ->label('Descrição da alvenaria da periferia')
                                        ->default(fn ($record) => $record->descricao_alvenaria_periferia_existente)
                                        ->disabled(),
                                    ToggleButtons::make('reboco_interno_externo_existente')
                                        ->label('Reboco interno e externo existente')
                                        ->options([
                                            true => 'Sim',
                                            false => 'Não',
                                            'na' => 'Não se aplica',
                                        ])
                                        ->colors([
                                            true => 'success',   // Verde
                                            false => 'danger',   // Vermelho
                                            'na' => 'gray',
                                        ])
                                        ->inline()
                                        ->default(function ($record) {
                                            return match (true) {
                                                $record->reboco_interno_externo_existente === true, $record->reboco_interno_externo_existente === 1 => true,
                                                $record->reboco_interno_externo_existente === false, $record->reboco_interno_externo_existente === 0 => false,
                                                $record->reboco_interno_externo_existente === null => 'na',
                                                default => 'na',
                                            };
                                        })
                                        ->disabled(),
                                    Textarea::make('descricao_reboco_interno_externo_existente')
                                        ->label('Descrição do reboco interno/externo')
                                        ->default(fn ($record) => $record->descricao_reboco_interno_externo_existente)
                                        ->disabled(),
                                    ToggleButtons::make('estanqueidade')
                                        ->label('Estanqueidade')
                                        ->options([
                                            true => 'Sim',
                                            false => 'Não',
                                            'na' => 'Não se aplica',
                                        ])
                                        ->colors([
                                            true => 'success',   // Verde
                                            false => 'danger',   // Vermelho
                                            'na' => 'gray',
                                        ])
                                        ->inline()
                                        ->default(function ($record) {
                                            return match (true) {
                                                $record->estanqueidade === true, $record->estanqueidade === 1 => true,
                                                $record->estanqueidade === false, $record->estanqueidade === 0 => false,
                                                $record->estanqueidade === null => 'na',
                                                default => 'na',
                                            };
                                        })
                                        ->disabled(),
                                    Textarea::make('descricao_estanqueidade')
                                        ->label('Descrição da estanqueidade')
                                        ->default(fn ($record) => $record->descricao_estanqueidade)
                                        ->disabled(),
                                ]),
                            ]),
                    ]),
                Step::make('Área 4 – Área Técnica')
                    ->schema([
                        Section::make('Área Técnica Externa e Interna')
                            ->schema([
                                Grid::make(2)->schema([
                                    ToggleButtons::make('area_tecnica_externa_existente')
                                        ->label('Área técnica externa existente')
                                        ->options([
                                            true => 'Sim',
                                            false => 'Não',
                                            'na' => 'Não se aplica',
                                        ])
                                        ->colors([
                                            true => 'success',   // Verde
                                            false => 'danger',   // Vermelho
                                            'na' => 'gray',
                                        ])
                                        ->inline()
                                        ->default(function ($record) {
                                            return match (true) {
                                                $record->area_tecnica_externa_existente === true, $record->area_tecnica_externa_existente === 1 => true,
                                                $record->area_tecnica_externa_existente === false, $record->area_tecnica_externa_existente === 0 => false,
                                                $record->area_tecnica_externa_existente === null => 'na',
                                                default => 'na',
                                            };
                                        })
                                        ->disabled(),
                                    Textarea::make('descricao_area_tecnica_externa_existente')
                                        ->label('Descrição da área técnica externa')
                                        ->default(fn ($record) => $record->descricao_area_tecnica_externa_existente)
                                        ->disabled(),
                                    ToggleButtons::make('sugestao_area_tecnica_interna')
                                        ->label('Sugestão para área técnica interna')
                                        ->options([
                                            true => 'Sim',
                                            false => 'Não',
                                            'na' => 'Não se aplica',
                                        ])
                                        ->colors([
                                            true => 'success',   // Verde
                                            false => 'danger',   // Vermelho
                                            'na' => 'gray',
                                        ])
                                        ->inline()
                                        ->default(function ($record) {
                                            return match (true) {
                                                $record->sugestao_area_tecnica_interna === true, $record->sugestao_area_tecnica_interna === 1 => true,
                                                $record->sugestao_area_tecnica_interna === false, $record->sugestao_area_tecnica_interna === 0 => false,
                                                $record->sugestao_area_tecnica_interna === null => 'na',
                                                default => 'na',
                                            };
                                        })
                                        ->disabled(),
                                    Textarea::make('descricao_sugestao_area_tecnica_interna')
                                        ->label('Descrição da sugestão de área interna')
                                        ->default(fn ($record) => $record->descricao_sugestao_area_tecnica_interna)
                                        ->disabled(),
                                ]),
                            ]),
                        Section::make('Condensadores')
                            ->schema([
                                Grid::make(2)->schema([
                                    ToggleButtons::make('prever_acustica_condensadores')
                                        ->label('Prever acústica de condensadoras')
                                        ->options([
                                            true => 'Sim',
                                            false => 'Não',
                                            'na' => 'Não se aplica',
                                        ])
                                        ->colors([
                                            true => 'success',   // Verde
                                            false => 'danger',   // Vermelho
                                            'na' => 'gray',
                                        ])
                                        ->inline()
                                        ->default(function ($record) {
                                            return match (true) {
                                                $record->prever_acustica_condensadores === true, $record->prever_acustica_condensadores === 1 => true,
                                                $record->prever_acustica_condensadores === false, $record->prever_acustica_condensadores === 0 => false,
                                                $record->prever_acustica_condensadores === null => 'na',
                                                default => 'na',
                                            };
                                        })
                                        ->disabled(),
                                    Textarea::make('descricao_prever_acustica_condensadores')
                                        ->label('Descrição do tratamento acústico')
                                        ->default(fn ($record) => $record->descricao_prever_acustica_condensadores)
                                        ->disabled(),
                                    ToggleButtons::make('prever_protecao_condensadores')
                                        ->label('Prever proteção para condensadoras')
                                        ->options([
                                            true => 'Sim',
                                            false => 'Não',
                                            'na' => 'Não se aplica',
                                        ])
                                        ->colors([
                                            true => 'success',   // Verde
                                            false => 'danger',   // Vermelho
                                            'na' => 'gray',
                                        ])
                                        ->inline()
                                        ->default(function ($record) {
                                            return match (true) {
                                                $record->prever_protecao_condensadores === true, $record->prever_protecao_condensadores === 1 => true,
                                                $record->prever_protecao_condensadores === false, $record->prever_protecao_condensadores === 0 => false,
                                                $record->prever_protecao_condensadores === null => 'na',
                                                default => 'na',
                                            };
                                        })
                                        ->disabled(),
                                    Textarea::make('descricao_prever_protecao_condensadores')
                                        ->label('Descrição da proteção para condensadores')
                                        ->default(fn ($record) => $record->descricao_prever_protecao_condensadores)
                                        ->disabled(),
                                ]),
                            ]),
                    ]),
                Step::make('Área 5 – Hidráulica / Esgoto / Gás')
                    ->schema([
                        Section::make('Reservatórios')
                            ->schema([
                                Grid::make(2)->schema([
                                    ToggleButtons::make('reservatorio_agua_existente')
                                        ->label('Reservatório de água existente')
                                        ->options([
                                            true => 'Sim',
                                            false => 'Não',
                                            'na' => 'Não se aplica',
                                        ])
                                        ->colors([
                                            true => 'success',   // Verde
                                            false => 'danger',   // Vermelho
                                            'na' => 'gray',
                                        ])
                                        ->inline()
                                        ->default(function ($record) {
                                            return match (true) {
                                                $record->reservatorio_agua_existente === true, $record->reservatorio_agua_existente === 1 => true,
                                                $record->reservatorio_agua_existente === false, $record->reservatorio_agua_existente === 0 => false,
                                                $record->reservatorio_agua_existente === null => 'na',
                                                default => 'na',
                                            };
                                        })
                                        ->disabled(),
                                    Textarea::make('descricao_reservatorio_agua_existente')
                                        ->label('Descrição do reservatório de água')
                                        ->default(fn ($record) => $record->descricao_reservatorio_agua_existente)
                                        ->disabled(),
                                    ToggleButtons::make('reservatorio_incendio_existente')
                                        ->label('Reservatório de incêndio existente')
                                        ->options([
                                            true => 'Sim',
                                            false => 'Não',
                                            'na' => 'Não se aplica',
                                        ])
                                        ->colors([
                                            true => 'success',   // Verde
                                            false => 'danger',   // Vermelho
                                            'na' => 'gray',
                                        ])
                                        ->inline()
                                        ->default(function ($record) {
                                            return match (true) {
                                                $record->reservatorio_incendio_existente === true, $record->reservatorio_incendio_existente === 1 => true,
                                                $record->reservatorio_incendio_existente === false, $record->reservatorio_incendio_existente === 0 => false,
                                                $record->reservatorio_incendio_existente === null => 'na',
                                                default => 'na',
                                            };
                                        })
                                        ->disabled(),
                                    Textarea::make('descricao_reservatorio_incendio_existente')
                                        ->label('Descrição do reservatório de incêndio')
                                        ->default(fn ($record) => $record->descricao_reservatorio_incendio_existente)
                                        ->disabled(),
                                ]),
                            ]),
                        Section::make('Esgoto e Gás')
                            ->schema([
                                Grid::make(2)->schema([
                                    ToggleButtons::make('ponto_esgoto_existente_shell')
                                        ->label('Ponto de esgoto existente dentro do shell')
                                        ->options([
                                            true => 'Sim',
                                            false => 'Não',
                                            'na' => 'Não se aplica',
                                        ])
                                        ->colors([
                                            true => 'success',   // Verde
                                            false => 'danger',   // Vermelho
                                            'na' => 'gray',
                                        ])
                                        ->inline()
                                        ->default(function ($record) {
                                            return match (true) {
                                                $record->ponto_esgoto_existente_shell === true, $record->ponto_esgoto_existente_shell === 1 => true,
                                                $record->ponto_esgoto_existente_shell === false, $record->ponto_esgoto_existente_shell === 0 => false,
                                                $record->ponto_esgoto_existente_shell === null => 'na',
                                                default => 'na',
                                            };
                                        })
                                        ->disabled(),
                                    Textarea::make('descricao_ponto_esgoto_existente_shell')
                                        ->label('Descrição do ponto de esgoto')
                                        ->default(fn ($record) => $record->descricao_ponto_esgoto_existente_shell)
                                        ->disabled(),
                                    ToggleButtons::make('rede_gas_disponivel')
                                        ->label('Rede de gás disponível')
                                        ->options([
                                            true => 'Sim',
                                            false => 'Não',
                                            'na' => 'Não se aplica',
                                        ])
                                        ->colors([
                                            true => 'success',   // Verde
                                            false => 'danger',   // Vermelho
                                            'na' => 'gray',
                                        ])
                                        ->inline()
                                        ->default(function ($record) {
                                            return match (true) {
                                                $record->rede_gas_disponivel === true, $record->rede_gas_disponivel === 1 => true,
                                                $record->rede_gas_disponivel === false, $record->rede_gas_disponivel === 0 => false,
                                                $record->rede_gas_disponivel === null => 'na',
                                                default => 'na',
                                            };
                                        })
                                        ->disabled(),
                                    Textarea::make('descricao_rede_gas_disponivel')
                                        ->label('Descrição da rede de gás')
                                        ->default(fn ($record) => $record->descricao_rede_gas_disponivel)
                                        ->disabled(),
                                ]),
                            ]),
                        Section::make('Medição e Sistema de Incêndio')
                            ->schema([
                                Grid::make(2)->schema([
                                    ToggleButtons::make('medidor_agua_instalado_ligado')
                                        ->label('Medidor de água instalado e ligado')
                                        ->options([
                                            true => 'Sim',
                                            false => 'Não',
                                            'na' => 'Não se aplica',
                                        ])
                                        ->colors([
                                            true => 'success',   // Verde
                                            false => 'danger',   // Vermelho
                                            'na' => 'gray',
                                        ])
                                        ->inline()
                                        ->default(function ($record) {
                                            return match (true) {
                                                $record->medidor_agua_instalado_ligado === true, $record->medidor_agua_instalado_ligado === 1 => true,
                                                $record->medidor_agua_instalado_ligado === false, $record->medidor_agua_instalado_ligado === 0 => false,
                                                $record->medidor_agua_instalado_ligado === null => 'na',
                                                default => 'na',
                                            };
                                        })
                                        ->disabled(),
                                    Textarea::make('descricao_medidor_agua_instalado_ligado')
                                        ->label('Descrição do medidor de água')
                                        ->default(fn ($record) => $record->descricao_medidor_agua_instalado_ligado)
                                        ->disabled(),
                                    TextInput::make('sistema_incendio_existente')
                                        ->label('Sistema de incêndio existente')
                                        ->default(fn ($record) => $record->sistema_incendio_existente)
                                        ->disabled(),
                                    Textarea::make('descricao_sistema_incendio_existente')
                                        ->label('Descrição do sistema de incêndio')
                                        ->default(fn ($record) => $record->descricao_sistema_incendio_existente)
                                        ->disabled(),
                                ]),
                            ]),
                    ]),
                Step::make('Área 6 – Arquitetura / Civil')
                    ->schema([
                        Section::make('Altura e Acessibilidade')
                            ->schema([
                                Grid::make(2)->schema([
                                    ToggleButtons::make('pd_acima_livre')
                                        ->label('PD acima de 3,5 m livres')
                                        ->options([
                                            true => 'Sim',
                                            false => 'Não',
                                            'na' => 'Não se aplica',
                                        ])
                                        ->colors([
                                            true => 'success',   // Verde
                                            false => 'danger',   // Vermelho
                                            'na' => 'gray',
                                        ])
                                        ->inline()
                                        ->default(function ($record) {
                                            return match (true) {
                                                $record->pd_acima_livre === true, $record->pd_acima_livre === 1 => true,
                                                $record->pd_acima_livre === false, $record->pd_acima_livre === 0 => false,
                                                $record->pd_acima_livre === null => 'na',
                                                default => 'na',
                                            };
                                        })
                                        ->disabled(),
                                    Textarea::make('descricao_pd_acima_livre')
                                        ->label('Descrição do pé-direito')
                                        ->default(fn ($record) => $record->descricao_pd_acima_livre)
                                        ->disabled(),
                                    ToggleButtons::make('necessario_elevador_plataforma')
                                        ->label('Necessário elevador ou plataforma')
                                        ->options([
                                            true => 'Sim',
                                            false => 'Não',
                                            'na' => 'Não se aplica',
                                        ])
                                        ->colors([
                                            true => 'success',   // Verde
                                            false => 'danger',   // Vermelho
                                            'na' => 'gray',
                                        ])
                                        ->inline()
                                        ->default(function ($record) {
                                            return match (true) {
                                                $record->necessario_elevador_plataforma === true, $record->necessario_elevador_plataforma === 1 => true,
                                                $record->necessario_elevador_plataforma === false, $record->necessario_elevador_plataforma === 0 => false,
                                                $record->necessario_elevador_plataforma === null => 'na',
                                                default => 'na',
                                            };
                                        })
                                        ->disabled(),
                                    Textarea::make('descricao_necessario_elevador_plataforma')
                                        ->label('Descrição sobre acessibilidade vertical')
                                        ->default(fn ($record) => $record->descricao_necessario_elevador_plataforma)
                                        ->disabled(),
                                ]),
                            ]),
                        Section::make('Acabamento e Fachada')
                            ->schema([
                                Grid::make(2)->schema([
                                    ToggleButtons::make('piso_acabamento_polido')
                                        ->label('Piso com acabamento polido')
                                        ->options([
                                            true => 'Sim',
                                            false => 'Não',
                                            'na' => 'Não se aplica',
                                        ])
                                        ->colors([
                                            true => 'success',   // Verde
                                            false => 'danger',   // Vermelho
                                            'na' => 'gray',
                                        ])
                                        ->inline()
                                        ->default(function ($record) {
                                            return match (true) {
                                                $record->piso_acabamento_polido === true, $record->piso_acabamento_polido === 1 => true,
                                                $record->piso_acabamento_polido === false, $record->piso_acabamento_polido === 0 => false,
                                                $record->piso_acabamento_polido === null => 'na',
                                                default => 'na',
                                            };
                                        })
                                        ->disabled(),
                                    Textarea::make('descricao_piso_acabamento_polido')
                                        ->label('Descrição do piso polido')
                                        ->default(fn ($record) => $record->descricao_piso_acabamento_polido)
                                        ->disabled(),
                                    ToggleButtons::make('necessario_pelicula_fachada')
                                        ->label('Necessário película na fachada')
                                        ->options([
                                            true => 'Sim',
                                            false => 'Não',
                                            'na' => 'Não se aplica',
                                        ])
                                        ->colors([
                                            true => 'success',   // Verde
                                            false => 'danger',   // Vermelho
                                            'na' => 'gray',
                                        ])
                                        ->inline()
                                        ->default(function ($record) {
                                            return match (true) {
                                                $record->necessario_pelicula_fachada === true, $record->necessario_pelicula_fachada === 1 => true,
                                                $record->necessario_pelicula_fachada === false, $record->necessario_pelicula_fachada === 0 => false,
                                                $record->necessario_pelicula_fachada === null => 'na',
                                                default => 'na',
                                            };
                                        })
                                        ->disabled(),
                                    Textarea::make('descricao_necessario_pelicula_fachada')
                                        ->label('Descrição da película na fachada')
                                        ->default(fn ($record) => $record->descricao_necessario_pelicula_fachada)
                                        ->disabled(),
                                ]),
                            ]),
                        Section::make('Elementos Arquitetônicos')
                            ->schema([
                                Grid::make(2)->schema([
                                    ToggleButtons::make('prever_marquise')
                                        ->label('Prever marquise')
                                        ->options([
                                            true => 'Sim',
                                            false => 'Não',
                                            'na' => 'Não se aplica',
                                        ])
                                        ->colors([
                                            true => 'success',   // Verde
                                            false => 'danger',   // Vermelho
                                            'na' => 'gray',
                                        ])
                                        ->inline()
                                        ->default(function ($record) {
                                            return match (true) {
                                                $record->prever_marquise === true, $record->prever_marquise === 1 => true,
                                                $record->prever_marquise === false, $record->prever_marquise === 0 => false,
                                                $record->prever_marquise === null => 'na',
                                                default => 'na',
                                            };
                                        })
                                        ->disabled(),
                                    Textarea::make('descricao_prever_marquise')
                                        ->label('Descrição da marquise')
                                        ->default(fn ($record) => $record->descricao_prever_marquise)
                                        ->disabled(),
                                    ToggleButtons::make('prever_porta_enrolar')
                                        ->label('Prever porta de enrolar')
                                        ->options([
                                            true => 'Sim',
                                            false => 'Não',
                                            'na' => 'Não se aplica',
                                        ])
                                        ->colors([
                                            true => 'success',   // Verde
                                            false => 'danger',   // Vermelho
                                            'na' => 'gray',
                                        ])
                                        ->inline()
                                        ->default(function ($record) {
                                            return match (true) {
                                                $record->prever_porta_enrolar === true, $record->prever_porta_enrolar === 1 => true,
                                                $record->prever_porta_enrolar === false, $record->prever_porta_enrolar === 0 => false,
                                                $record->prever_porta_enrolar === null => 'na',
                                                default => 'na',
                                            };
                                        })
                                        ->disabled(),
                                    Textarea::make('descricao_prever_porta_enrolar')
                                        ->label('Descrição da porta de enrolar')
                                        ->default(fn ($record) => $record->descricao_prever_porta_enrolar)
                                        ->disabled(),
                                ]),
                            ]),
                        Section::make('Vedação e Impermeabilização')
                            ->schema([
                                Grid::make(2)->schema([
                                    ToggleButtons::make('caixilhos_vidros_existentes')
                                        ->label('Caixilhos e vidros existentes')
                                        ->options([
                                            true => 'Sim',
                                            false => 'Não',
                                            'na' => 'Não se aplica',
                                        ])
                                        ->colors([
                                            true => 'success',   // Verde
                                            false => 'danger',   // Vermelho
                                            'na' => 'gray',
                                        ])
                                        ->inline()
                                        ->default(function ($record) {
                                            return match (true) {
                                                $record->caixilhos_vidros_existentes === true, $record->caixilhos_vidros_existentes === 1 => true,
                                                $record->caixilhos_vidros_existentes === false, $record->caixilhos_vidros_existentes === 0 => false,
                                                $record->caixilhos_vidros_existentes === null => 'na',
                                                default => 'na',
                                            };
                                        })
                                        ->disabled(),
                                    Textarea::make('descricao_caixilhos_vidros_existentes')
                                        ->label('Descrição dos caixilhos')
                                        ->default(fn ($record) => $record->descricao_caixilhos_vidros_existentes)
                                        ->disabled(),
                                    ToggleButtons::make('prever_impermeabilizacao')
                                        ->label('Prever impermeabilização externa')
                                        ->options([
                                            true => 'Sim',
                                            false => 'Não',
                                            'na' => 'Não se aplica',
                                        ])
                                        ->colors([
                                            true => 'success',   // Verde
                                            false => 'danger',   // Vermelho
                                            'na' => 'gray',
                                        ])
                                        ->inline()
                                        ->default(function ($record) {
                                            return match (true) {
                                                $record->prever_impermeabilizacao === true, $record->prever_impermeabilizacao === 1 => true,
                                                $record->prever_impermeabilizacao === false, $record->prever_impermeabilizacao === 0 => false,
                                                $record->prever_impermeabilizacao === null => 'na',
                                                default => 'na',
                                            };
                                        })
                                        ->disabled(),
                                    Textarea::make('descricao_prever_impermeabilizacao')
                                        ->label('Descrição da impermeabilização')
                                        ->default(fn ($record) => $record->descricao_prever_impermeabilizacao)
                                        ->disabled(),
                                ]),
                            ]),
                    ]),
            ]);
    }
}
