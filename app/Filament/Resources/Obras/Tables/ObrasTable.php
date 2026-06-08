<?php

namespace App\Filament\Resources\Obras\Tables;

use App\Filament\Resources\Obras\ObrasResource;
use App\Filament\Tables\TableExcel\TableExcelOptions;
use App\Filament\Tables\TableExcel\TableExcelPreset;
use App\Models\ColunaPersonalizada;
use App\Models\Obras;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ObrasTable
{
    protected static function getPontosAtencaoDefinitions(): Collection
    {
        return Cache::remember('obras_pontos_atencao_definitions', 600, function (): Collection {
            return ColunaPersonalizada::query()
                ->select('nome', 'tipo', 'opcoes')
                ->whereNotNull('nome')
                ->orderBy('nome')
                ->get()
                ->groupBy('nome')
                ->map(fn ($items) => $items->first());
        });
    }

    protected static function buildPontosAtencaoColumnsGroup(): array
    {
        $definicoes = static::getPontosAtencaoDefinitions();

        if ($definicoes->isEmpty()) {
            return [];
        }

        $cols = [];

        foreach ($definicoes as $nome => $definicao) {
            $key = 'ponto_atencao_'.Str::slug((string) $nome, '_');
            $tipo = (string) ($definicao->tipo ?? 'texto');

            if ($tipo === 'select') {
                $opcoes = collect($definicao->opcoes ?? [])
                    ->map(fn ($item) => trim((string) $item))
                    ->filter(fn ($item) => $item !== '')
                    ->values()
                    ->all();

                $opcoesMap = collect($opcoes)
                    ->mapWithKeys(fn ($item) => [$item => $item])
                    ->all();

                $cols[] = Tables\Columns\SelectColumn::make($key)
                    ->label((string) $nome)
                    ->options($opcoesMap)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->getStateUsing(fn (Obras $record) => $record->colunasPersonalizadas?->first(fn ($item) => (string) $item->nome === (string) $nome)?->valor)
                    ->updateStateUsing(function (Obras $record, $state) use ($nome): void {
                        static::atualizarValorColunaPersonalizada($record, (string) $nome, $state);
                    });

                continue;
            }

            $coluna = Tables\Columns\TextInputColumn::make($key)
                ->label((string) $nome)
                ->toggleable(isToggledHiddenByDefault: true)
                ->getStateUsing(fn (Obras $record) => $record->colunasPersonalizadas?->first(fn ($item) => (string) $item->nome === (string) $nome)?->valor)
                ->updateStateUsing(function (Obras $record, $state) use ($nome): void {
                    static::atualizarValorColunaPersonalizada($record, (string) $nome, $state);
                });

            if ($tipo === 'numero') {
                $coluna->type('number');
            } elseif ($tipo === 'data') {
                $coluna->type('text')
                    ->mask('99/99/9999')
                    ->rules(['nullable', 'date_format:d/m/Y'])
                    ->getStateUsing(function (Obras $record) use ($nome) {
                        $valor = $record->colunasPersonalizadas?->first(fn ($item) => (string) $item->nome === (string) $nome)?->valor;

                        return $valor ? Carbon::parse($valor)->format('d/m/Y') : null;
                    })
                    ->updateStateUsing(function (Obras $record, $state) use ($nome): void {
                        $parsed = $state ? Carbon::createFromFormat('d/m/Y', $state)->format('Y-m-d') : null;
                        static::atualizarValorColunaPersonalizada($record, (string) $nome, $parsed);
                    });
            }

            $cols[] = $coluna;
        }

        return [Tables\Columns\ColumnGroup::make('PONTOS DE ATENÇÃO', $cols)];
    }

    protected static function atualizarValorColunaPersonalizada(Obras $record, string $nome, mixed $state): void
    {
        $valor = is_string($state) ? trim($state) : $state;
        $valor = ($valor === '' || $valor === null) ? null : substr((string) $valor, 0, 255);

        ColunaPersonalizada::query()
            ->where('obra_id', $record->id)
            ->where('nome', $nome)
            ->update([
                'valor' => $valor,
                'usuario_id' => auth()->id(),
            ]);
    }

    protected static function normalizarOpcoesColunaPersonalizada(string $opcoesBrutas): array
    {
        return collect(preg_split('/[\r\n,;]+/', $opcoesBrutas) ?: [])
            ->map(fn ($item) => trim((string) $item))
            ->filter(fn ($item) => $item !== '')
            ->unique()
            ->values()
            ->all();
    }

    protected static function canManagePontosAtencao(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('Gestor')
            && $user->setores()->whereRaw('LOWER(setor) = ?', ['obras'])->exists();
    }

    public static function configure(Table $table): Table
    {
        $options = TableExcelOptions::make()
            ->dense()
            ->freezable()
            ->resizable()
            ->tableKey('obras.list');

        $table = TableExcelPreset::apply($table, $options);

        $table
            ->striped()
            ->view('filament.resources.obras.table-index')
            ->extraAttributes(['class' => 'obras-compact'], merge: true)
            ->deferLoading()
            ->paginated([50, 100, 200])
            ->defaultPaginationPageOption(50)
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'projeto:id,sigla,nova_sigla,nome,marca,status_contrato,inauguracao,tipo_imovel,empreendimento,locacao,contato_corretor',
                'colunasPersonalizadas:obra_id,nome,valor',
            ]))
            ->columns([

                Tables\Columns\ColumnGroup::make('INFORMAÇÕES DO PROJETO', [
                    Tables\Columns\TextColumn::make('codigo')
                        ->label('CÓDIGO')
                        ->sortable()
                        ->searchable()
                        ->toggleable()
                        ->alignCenter(),

                    Tables\Columns\TextColumn::make('projeto.sigla')
                        ->label('SIGLA')
                        ->sortable()
                        ->searchable()
                        ->toggleable()
                        ->alignCenter(),

                    Tables\Columns\TextColumn::make('projeto.nova_sigla')
                        ->label('NOVA SIGLA')
                        ->sortable()
                        ->searchable()
                        ->toggleable()
                        ->alignCenter(),

                    Tables\Columns\TextColumn::make('projeto.nome')
                        ->label('UNIDADE')
                        ->sortable()
                        ->searchable()
                        ->toggleable(),

                    Tables\Columns\TextColumn::make('projeto.marca')
                        ->label('MARCA')
                        ->sortable()
                        ->searchable()
                        ->toggleable()
                        ->alignCenter(),

                    Tables\Columns\TextColumn::make('pipe_land')
                        ->label('PIPE / LAND')
                        ->sortable()
                        ->toggleable()
                        ->alignCenter(),

                    Tables\Columns\SelectColumn::make('status')
                        ->label('STATUS')
                        ->options([
                            'Em processo' => 'Em processo',
                            'Obras' => 'Obras',
                            'Inaugurada' => 'Inaugurada',
                            'Cancelada' => 'Cancelada',
                            'Stand-by' => 'Stand-by',
                            'Deletar comercial' => 'Deletar comercial',
                        ])
                        ->selectablePlaceholder(false)
                        ->toggleable()
                        ->sortable(),
                ]),

                Tables\Columns\ColumnGroup::make('GESTOR', [
                    Tables\Columns\TextColumn::make('engenharia')
                        ->label('ENGENHARIA')
                        ->sortable()
                        ->searchable()
                        ->toggleable(),

                    Tables\Columns\TextColumn::make('comercial')
                        ->label('COMERCIAL')
                        ->sortable()
                        ->searchable()
                        ->toggleable(),

                    Tables\Columns\TextColumn::make('arquitetura')
                        ->label('ARQUITETURA')
                        ->sortable()
                        ->searchable()
                        ->toggleable(),

                    static::makeDateColumn('entrada_ponto')
                        ->label('ENTRADA DO PONTO')
                        ->toggleable()
                        ->sortable(),

                    Tables\Columns\TextColumn::make('projeto.status_contrato')
                        ->label('STATUS DO CONTRATO')
                        ->sortable()
                        ->toggleable(),

                    static::makeDateColumn('data_assinatura_contrato')
                        ->label('DATA DE ASSINATURA DO CONTRATO')
                        ->toggleable()
                        ->sortable(),
                ]),

                Tables\Columns\ColumnGroup::make('TOTAL DE DIAS DE PROCESSO', [
                    Tables\Columns\TextColumn::make('entrada_ponto_ate_inauguracao')
                        ->label('ENTRADA DO PONTO ATÉ INAUGURAÇÃO')
                        ->sortable()
                        ->numeric()
                        ->searchable()
                        ->alignCenter()
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->formatStateUsing(fn ($state) => $state
                            ? $state.' '.($state == 1 ? 'dia' : 'dias')
                            : null),

                    Tables\Columns\TextColumn::make('assinatura_ate_inauguracao')
                        ->label('ASSINATURA ATÉ INAUGURAÇÃO')
                        ->sortable()
                        ->numeric()
                        ->searchable()
                        ->alignCenter()
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->formatStateUsing(fn ($state) => $state
                            ? $state.' '.($state == 1 ? 'dia' : 'dias')
                            : null),
                ]),

                Tables\Columns\ColumnGroup::make('VISITA TÉCNICA', [
                    Tables\Columns\SelectColumn::make('status_visita')
                        ->label('STATUS')
                        ->options([
                            'CONCLUÍDO' => 'Concluído',
                            'EM ANDAMENTO' => 'Em Andamento',
                            'N/A' => 'N/A',
                            'NÃO INICIADO' => 'Não Iniciado',
                            'AGENDADO' => 'Agendado',
                            'PENDÊNCIAS' => 'Pendências',
                            'NÃO SOLICITADO' => 'Não Solicitado',
                            'SOLICITADO' => 'Solicitado',
                        ])
                        ->selectablePlaceholder(false)
                        ->toggleable()
                        ->sortable(),
                ]),

                Tables\Columns\ColumnGroup::make('PROJETO EXECUTIVO', [
                    Tables\Columns\SelectColumn::make('status_proj_exec')
                        ->label('STATUS')
                        ->options([
                            'CONCLUÍDO' => 'Concluído',
                            'EM ANDAMENTO' => 'Em Andamento',
                            'N/A' => 'N/A',
                            'NÃO INICIADO' => 'Não Iniciado',
                            'AGENDADO' => 'Agendado',
                            'PENDÊNCIAS' => 'Pendências',
                            'NÃO SOLICITADO' => 'Não Solicitado',
                            'SOLICITADO' => 'Solicitado',
                        ])
                        ->selectablePlaceholder(false)
                        ->toggleable()
                        ->sortable(),
                ]),

                Tables\Columns\ColumnGroup::make('POSSE', [
                    static::makeDateColumn('status_data_posse')
                        ->label('DATA DE POSSE')
                        ->toggleable()
                        ->sortable(),

                    Tables\Columns\SelectColumn::make('relatorio_fotografico')
                        ->label('RELATÓRIO FOTOGRÁFICO')
                        ->options([
                            'enviado' => 'Enviado',
                            'pendencias' => 'Enviado com Pendências',
                            'nao_enviado' => 'Não Enviado',
                        ])
                        ->toggleable()
                        ->sortable(),

                    static::makeDateColumn('data_envio_relatorio_fotografico')
                        ->label('DATA DE ENVIO DO RELATÓRIO FOTOGRÁFICO')
                        ->toggleable()
                        ->sortable(),

                    static::makeDateColumn('data_atualizacao_comentario')
                        ->label('DATA DE ATUALIZAÇÃO DO COMENTÁRIO')
                        ->toggleable()
                        ->sortable(),

                    Tables\Columns\TextInputColumn::make('comentarios')
                        ->label('COMENTÁRIOS')
                        ->toggleable(),

                    Tables\Columns\SelectColumn::make('termo_de_posse')
                        ->label('TERMO DE POSSE')
                        ->options([
                            'sim' => 'Sim',
                            'nao' => 'Não',
                        ])
                        ->toggleable()
                        ->sortable(),
                ]),

                Tables\Columns\ColumnGroup::make('EXECUÇÃO DE OBRAS', [
                    static::makeDateColumn('inicio')
                        ->label('INÍCIO')
                        ->toggleable()
                        ->sortable(),

                    static::makeDateColumn('inicio_real')
                        ->label('INÍCIO REAL')
                        ->toggleable()
                        ->sortable(),

                    static::makeDateColumn('fim')
                        ->label('FIM')
                        ->toggleable()
                        ->sortable(),

                    Tables\Columns\TextColumn::make('prazo_planejado')
                        ->label('PRAZO PLANEJADO')
                        ->sortable()
                        ->alignCenter()
                        ->toggleable()
                        ->formatStateUsing(fn ($state) => $state
                            ? $state.' '.($state == 1 ? 'dia' : 'dias')
                            : null),

                    Tables\Columns\TextColumn::make('prazo_realizado')
                        ->label('PRAZO REALIZADO')
                        ->sortable()
                        ->alignCenter()
                        ->toggleable()
                        ->formatStateUsing(fn ($state) => $state
                            ? $state.' '.($state == 1 ? 'dia' : 'dias')
                            : null),
                ]),

                Tables\Columns\ColumnGroup::make('IMPLANTAÇÃO', [
                    static::makeDateColumn('inicio_imp')
                        ->label('INÍCIO')
                        ->toggleable()
                        ->sortable(),

                    static::makeDateColumn('fim_imp')
                        ->label('FIM')
                        ->toggleable()
                        ->sortable(),

                    Tables\Columns\SelectColumn::make('cronograma_implantacao')
                        ->label('CRONOGRAMA DE IMPLANTAÇÃO')
                        ->options([
                            'enviado' => 'Enviado',
                            'nao_enviado' => 'Não Enviado',
                        ])
                        ->toggleable()
                        ->sortable(),

                    Tables\Columns\TextInputColumn::make('observacao')
                        ->label('OBSERVAÇÃO')
                        ->toggleable(),

                    Tables\Columns\TextColumn::make('projeto.inauguracao')
                        ->label('INAUGURAÇÃO')
                        ->date('d/m/Y')
                        ->toggleable()
                        ->sortable(),

                    Tables\Columns\TextColumn::make('imp_prazo_planej')
                        ->label('PRAZO PLANEJADO')
                        ->sortable()
                        ->alignCenter()
                        ->toggleable()
                        ->formatStateUsing(fn ($state) => $state
                            ? $state.' '.($state == 1 ? 'dia' : 'dias')
                            : null),

                    Tables\Columns\TextColumn::make('imp_prazo_realiz')
                        ->label('PRAZO REALIZADO')
                        ->sortable()
                        ->alignCenter()
                        ->toggleable()
                        ->formatStateUsing(fn ($state) => $state
                            ? $state.' '.($state == 1 ? 'dia' : 'dias')
                            : null),

                    Tables\Columns\TextColumn::make('mes')
                        ->label('MÊS')
                        ->sortable()
                        ->alignCenter()
                        ->toggleable()
                        ->formatStateUsing(fn ($state) => match ((int) $state) {
                            1 => 'Janeiro',
                            2 => 'Fevereiro',
                            3 => 'Março',
                            4 => 'Abril',
                            5 => 'Maio',
                            6 => 'Junho',
                            7 => 'Julho',
                            8 => 'Agosto',
                            9 => 'Setembro',
                            10 => 'Outubro',
                            11 => 'Novembro',
                            12 => 'Dezembro',
                            default => $state,
                        }),

                    Tables\Columns\TextColumn::make('ano')
                        ->label('ANO')
                        ->sortable()
                        ->toggleable()
                        ->alignCenter(),
                ]),

                Tables\Columns\ColumnGroup::make('DADOS DO IMÓVEL', [
                    Tables\Columns\TextColumn::make('projeto.tipo_imovel')
                        ->label('TIPO DO IMÓVEL')
                        ->sortable()
                        ->searchable()
                        ->toggleable(),

                    Tables\Columns\TextColumn::make('endereco')
                        ->label('ENDEREÇO')
                        ->limit(40)
                        ->tooltip(fn ($state) => $state)
                        ->searchable()
                        ->toggleable(),

                    Tables\Columns\TextColumn::make('cidade')
                        ->label('CIDADE')
                        ->sortable()
                        ->searchable()
                        ->toggleable(),

                    Tables\Columns\TextColumn::make('uf')
                        ->label('ESTADO')
                        ->sortable()
                        ->toggleable(),

                    Tables\Columns\TextColumn::make('projeto.empreendimento')
                        ->label('EMPREENDIMENTO')
                        ->sortable()
                        ->searchable()
                        ->toggleable(),

                    Tables\Columns\TextColumn::make('projeto.locacao')
                        ->label('LOCAÇÃO')
                        ->badge()
                        ->toggleable()
                        ->color(fn ($state) => match ($state) {
                            'Mono usuário' => 'info',
                            'Multiusuário' => 'success',
                            default => 'secondary',
                        }),

                    Tables\Columns\TextColumn::make('projeto.contato_corretor')
                        ->label('CONTATO DO CORRETOR / PP')
                        ->toggleable(),
                ]),

                Tables\Columns\ColumnGroup::make('% DE OBRA', [
                    Tables\Columns\TextColumn::make('dias_para_inauguracao')
                        ->label('DIAS PARA INAUGURAÇÃO')
                        ->badge()
                        ->toggleable()
                        ->color(fn ($state) => match (true) {
                            $state !== null && $state < 0 => 'danger',
                            $state !== null && $state <= 15 => 'warning',
                            $state !== null && $state > 15 => 'success',
                            default => 'secondary',
                        })
                        ->formatStateUsing(fn ($state) => $state !== null ? intval($state).' dias' : '-')
                        ->sortable(),

                    Tables\Columns\TextColumn::make('dias_obra_inicio_pmo')
                        ->label('DIAS DE OBRA (Início PMO)')
                        ->badge()
                        ->toggleable()
                        ->color(fn ($state) => match (true) {
                            $state !== null && $state < 0 => 'danger',
                            $state !== null && $state <= 15 => 'warning',
                            $state !== null && $state > 15 => 'success',
                            default => 'secondary',
                        })
                        ->formatStateUsing(fn ($state) => $state !== null ? intval($state).' dias' : '-')
                        ->sortable(),

                    Tables\Columns\TextInputColumn::make('percentual_obra')
                        ->label('% DE OBRA PREVISTO')
                        ->toggleable()
                        ->sortable()
                        ->rules(['nullable', 'numeric', 'min:0', 'max:100']),

                    Tables\Columns\TextInputColumn::make('percentual_obra_executado')
                        ->label('% DE OBRA EXECUTADO')
                        ->toggleable()
                        ->sortable()
                        ->rules(['nullable', 'numeric', 'min:0', 'max:100']),

                    Tables\Columns\TextInputColumn::make('desvio')
                        ->label('DESVIO')
                        ->toggleable()
                        ->sortable()
                        ->rules(['nullable', 'numeric']),
                ]),

                Tables\Columns\ColumnGroup::make('ACOMPANHAMENTO DE OBRA', [

                    Tables\Columns\TextInputColumn::make('itens_criticos')
                        ->label('ITENS CRÍTICOS')
                        ->toggleable(),

                    Tables\Columns\TextInputColumn::make('descricao_itens_criticos')
                        ->label('DESCRIÇÃO DOS ITENS CRÍTICOS')
                        ->toggleable(),
                ]),

                Tables\Columns\ColumnGroup::make('CRONOGRAMA VISI', [

                    Tables\Columns\SelectColumn::make('cronograma_visi')
                        ->label('CRONOGRAMA VISI')
                        ->options([
                            'enviado' => 'Enviado',
                            'nao_enviado' => 'Não Enviado',
                        ])
                        ->toggleable(),

                    Tables\Columns\SelectColumn::make('camera_unidade')
                        ->label('CÂMERA NA UNIDADE')
                        ->options([
                            'sim' => 'Sim',
                            'nao' => 'Não',
                        ])
                        ->toggleable()
                        ->sortable(),

                    Tables\Columns\TextInputColumn::make('ponto_atencao')
                        ->label('PONTO DE ATENÇÃO')
                        ->toggleable(),
                ]),

                Tables\Columns\ColumnGroup::make('CONTRATAÇÕES', [
                    Tables\Columns\TextInputColumn::make('civil')
                        ->label('Civil')
                        ->sortable()
                        ->toggleable(),

                    Tables\Columns\TextInputColumn::make('hidraulica')
                        ->label('Hidráulica')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),

                    Tables\Columns\TextInputColumn::make('eletrica')
                        ->label('Elétrica')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),

                    Tables\Columns\TextInputColumn::make('incendio')
                        ->label('Incêndio')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),

                    Tables\Columns\TextInputColumn::make('instalacao_ar_condicionado')
                        ->label('Instalação Ar Condicionado')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),

                    Tables\Columns\TextInputColumn::make('maquinas_ar_condicionado')
                        ->label('Máquinas Ar Condicionado')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),

                    Tables\Columns\SelectColumn::make('homologados_em_atraso')
                        ->label('Homologados em Atraso')
                        ->options([
                            'sim' => 'Sim',
                            'nao' => 'Não',
                        ])
                        ->toggleable(isToggledHiddenByDefault: true),
                ]),

                Tables\Columns\ColumnGroup::make('Contas de Consumo', [
                    Tables\Columns\SelectColumn::make('energia')
                        ->label('ENERGIA')
                        ->options([
                            'Ligada / Rateio' => 'Ligada / Rateio',
                            'Ligada em nome da Smart' => 'Ligada em nome da Smart',
                            'Ligada, necessário trocar titularidade' => 'Necessário trocar titularidade',
                            'Pendente, responsavel Smart' => 'Pendente, resp. Smart',
                            'Pendente, responsavel PP' => 'Pendente, resp. PP',
                            'GERADOR' => 'GERADOR',
                        ])
                        ->toggleable(),

                    static::makeDateColumn('previsao_ligacao_energia')
                        ->label('PREVISÃO DE LIGAÇÃO DE ENERGIA')
                        ->toggleable()
                        ->sortable(),

                    Tables\Columns\TextInputColumn::make('gerador_contratual')
                        ->label('GERADOR CONTRATUAL')
                        ->toggleable(),

                    Tables\Columns\SelectColumn::make('agua')
                        ->label('ÁGUA')
                        ->options([
                            'Ligada em nome da Smart' => 'Ligada em nome da Smart',
                            'Ligada, necessário trocar titularidade' => 'Necessário trocar titularidade',
                            'Pendente, responsavel Smart' => 'Pendente, resp. Smart',
                            'Pendente, responsavel PP' => 'Pendente, resp. PP',
                            'Ligada / Rateio' => 'Ligada / Rateio',
                        ])
                        ->toggleable(),

                    Tables\Columns\SelectColumn::make('gas')
                        ->label('GÁS')
                        ->options([
                            'Ligada em nome da Smart' => 'Ligada em nome da Smart',
                            'Ligada, necessário trocar titularidade' => 'Necessário trocar titularidade',
                            'Pendente, responsavel Smart' => 'Pendente, resp. Smart',
                            'Pendente, responsavel PP' => 'Pendente, resp. PP',
                            'Boiler Instalado provisório' => 'Boiler Instalado provisório',
                        ])
                        ->toggleable(),

                    Tables\Columns\TextInputColumn::make('comentario')
                        ->label('COMENTÁRIO')
                        ->toggleable(),
                ]),

                Tables\Columns\ColumnGroup::make('PÓS OBRA', [
                    Tables\Columns\SelectColumn::make('email_solicitacao_cl')
                        ->label('EMAIL SOLICITAÇÃO DE CL')
                        ->options([
                            'enviado' => 'Enviado',
                            'nao_enviado' => 'Não Enviado',
                        ])
                        ->toggleable(isToggledHiddenByDefault: true),

                    Tables\Columns\SelectColumn::make('envio_qrcod')
                        ->label('ENVIO DE QRCODE')
                        ->options([
                            'enviado' => 'Enviado',
                            'nao_enviado' => 'Não Enviado',
                        ])
                        ->toggleable(isToggledHiddenByDefault: true),

                    Tables\Columns\SelectColumn::make('checklist_manutencao')
                        ->label('CHECKLIST DE MANUTENÇÃO (TRILOGO)')
                        ->options([
                            'concluido' => 'Concluído',
                            'em_andamento' => 'Em andamento',
                            'em_atraso' => 'Em atraso',
                            'nao_iniciado' => 'Não iniciado',
                        ])
                        ->toggleable(isToggledHiddenByDefault: true),

                    static::makeDateColumn('data_check_list')
                        ->label('DATA DO CHECK LIST')
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->sortable(),

                    static::makeDateColumn('inicio_prev_pendencias')
                        ->label('INÍCIO PREVISTO PENDÊNCIAS')
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->sortable(),

                    static::makeDateColumn('termino_prev_pendencias')
                        ->label('TÉRMINO PREVISTO PENDÊNCIAS')
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->sortable(),

                    Tables\Columns\TextInputColumn::make('elevador')
                        ->label('ELEVADOR')
                        ->toggleable(isToggledHiddenByDefault: true),

                    Tables\Columns\TextInputColumn::make('comentarios_adicionais')
                        ->label('COMENTÁRIOS')
                        ->toggleable(isToggledHiddenByDefault: true),

                    Tables\Columns\TextInputColumn::make('gestor_pos_obra')
                        ->label('GESTOR PÓS OBRA')
                        ->toggleable(isToggledHiddenByDefault: true),
                ]),

                ...static::buildPontosAtencaoColumnsGroup(),

                Tables\Columns\ColumnGroup::make('Auditoria', [
                    Tables\Columns\TextColumn::make('created_at')
                        ->dateTime('d/m/Y H:i')
                        ->label('Criado em')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ]),
            ])

            ->filters(static::buildFilters())

            ->recordActions([
                Actions\ViewAction::make()
                    ->iconButton()
                    ->tooltip('Visualizar detalhes da obra'),
                Actions\Action::make('editModal')
                    ->icon('heroicon-o-pencil-square')
                    ->iconButton()
                    ->tooltip('Editar informações básicas')
                    ->slideOver()
                    ->modalHeading(fn (Obras $record) => 'Editar: '.($record->sigla ?? $record->codigo))
                    ->modalSubmitActionLabel('Salvar')
                    ->form(static::buildEditForm())
                    ->fillForm(fn (Obras $record) => array_merge(
                        $record->attributesToArray(),
                        static::getPontosAtencaoValues($record)
                    ))
                    ->action(function (Obras $record, array $data) {
                        // Separar dados de Pontos de Atenção
                        $pontosAtencaoData = [];
                        $mainData = [];

                        foreach ($data as $key => $value) {
                            if (Str::startsWith($key, 'ponto_atencao_id_')) {
                                $colunaId = (int) Str::replace('ponto_atencao_id_', '', $key);
                                $pontosAtencaoData[$colunaId] = $value;
                            } else {
                                $mainData[$key] = $value;
                            }
                        }

                        // Salvar dados principais
                        $record->update($mainData);

                        // Salvar Pontos de Atenção
                        foreach ($pontosAtencaoData as $colunaId => $valor) {
                            ColunaPersonalizada::query()
                                ->where('id', $colunaId)
                                ->where('obra_id', $record->id)
                                ->update([
                                    'valor' => filled($valor) ? substr((string) $valor, 0, 255) : null,
                                    'usuario_id' => auth()->id(),
                                ]);
                        }
                    }),
                Actions\DeleteAction::make()->iconButton(),
            ])
            ->recordActionsPosition(Tables\Enums\RecordActionsPosition::BeforeCells)
            ->recordUrl(null)
            ->toolbarActions([
                Actions\Action::make('create')
                    ->label('Criar obra')
                    ->icon('heroicon-o-plus')
                    ->url(fn () => ObrasResource::getUrl('create'))
                    ->button()
                    ->color('primary'),
                Actions\Action::make('criarCampoPontoAtencao')
                    ->label('Campo Pontos de Atenção')
                    ->icon('heroicon-o-plus-circle')
                    ->color('warning')
                    ->modalHeading('Novo campo de Pontos de Atenção')
                    ->modalSubmitActionLabel('Criar campo')
                    ->form([
                        Forms\Components\TextInput::make('nome')
                            ->label('Nome da coluna')
                            ->required()
                            ->maxLength(120),
                        Forms\Components\Select::make('tipo')
                            ->label('Tipo')
                            ->required()
                            ->native(false)
                            ->live()
                            ->options([
                                'texto' => 'Texto',
                                'numero' => 'Número',
                                'data' => 'Data',
                                'select' => 'Selecione',
                            ]),
                        Forms\Components\TextInput::make('opcoes')
                            ->label('Opções do select')
                            ->placeholder('Ex.: Pendente, Em análise, Conluído')
                            ->visible(fn ($get) => $get('tipo') === 'select'),
                    ])
                    ->action(function (array $data): void {
                        $tipo = (string) ($data['tipo'] ?? 'texto');
                        $opcoes = $tipo === 'select'
                            ? static::normalizarOpcoesColunaPersonalizada((string) ($data['opcoes'] ?? ''))
                            : null;

                        if ($tipo === 'select' && empty($opcoes)) {
                            Notification::make()->title('Informe as opções do select')->danger()->send();

                            return;
                        }

                        $nome = trim((string) ($data['nome'] ?? ''));
                        $obras = Obras::query()
                            ->whereNotNull('projeto_id')
                            ->get(['id', 'projeto_id']);

                        foreach ($obras as $obra) {
                            ColunaPersonalizada::firstOrCreate(
                                [
                                    'projeto_id' => $obra->projeto_id,
                                    'obra_id' => $obra->id,
                                    'nome' => $nome,
                                ],
                                [
                                    'tipo' => $tipo,
                                    'opcoes' => $opcoes,
                                    'valor' => null,
                                    'usuario_id' => auth()->id(),
                                ]
                            );
                        }

                        Notification::make()
                            ->title('Campo criado para todas as obras')
                            ->success()
                            ->send();
                    })
                    ->successRedirectUrl(fn () => ObrasResource::getUrl('index'))
                    ->visible(fn () => static::canManagePontosAtencao()),
                Actions\Action::make('gerenciarColunasPersonalizadas')
                    ->label('Gerenciar Colunas')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->color('gray')
                    ->modalHeading('Gerenciar Colunas Personalizadas')
                    ->modalSubmitActionLabel('Excluir coluna')
                    ->form([
                        Forms\Components\Select::make('coluna_nome')
                            ->label('Coluna personalizada')
                            ->options(function () {
                                return ColunaPersonalizada::query()
                                    ->selectRaw('nome, tipo, COUNT(DISTINCT obra_id) as total_obras')
                                    ->groupBy('nome', 'tipo')
                                    ->orderBy('nome')
                                    ->get()
                                    ->mapWithKeys(fn ($item) => [
                                        $item->nome => sprintf('%s (%s • %s obra%s)', $item->nome, ucfirst($item->tipo), $item->total_obras, $item->total_obras === 1 ? '' : 's'),
                                    ])
                                    ->toArray();
                            })
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $nome = trim((string) ($data['coluna_nome'] ?? ''));

                        if ($nome === '') {
                            return;
                        }

                        ColunaPersonalizada::query()->where('nome', $nome)->delete();

                        Notification::make()
                            ->title('Coluna excluída')
                            ->success()
                            ->send();
                    })
                    ->successRedirectUrl(fn () => ObrasResource::getUrl('index'))
                    ->requiresConfirmation()
                    ->visible(fn () => static::canManagePontosAtencao() && ColunaPersonalizada::query()->distinct('nome')->exists()),
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);

        return TableExcelPreset::applyColumnPreferences($table, $options);
    }

    protected static function fieldStats(array $fields, ?string $extra = null): \Closure
    {
        return function (Get $get) use ($fields, $extra): HtmlString {
            $filled = 0;
            foreach ($fields as $f) {
                if (filled($get($f))) {
                    $filled++;
                }
            }
            $total = count($fields);
            $pct = $total > 0 ? round(($filled / $total) * 100) : 0;

            if ($pct >= 80) {
                $color = 'text-green-500 dark:text-green-400';
                $icon = '&#10004;';
            } elseif ($pct >= 40) {
                $color = 'text-amber-500 dark:text-amber-400';
                $icon = '&#9679;';
            } else {
                $color = 'text-gray-400 dark:text-gray-500';
                $icon = '&#9675;';
            }

            $html = "<span class='{$color}' style='font-size:0.75rem'>{$icon} {$filled}/{$total} ({$pct}%)</span>";

            if ($extra) {
                $extraVal = $get($extra);
                if (filled($extraVal)) {
                    $html .= "<span class='text-gray-400 dark:text-gray-500 ml-2' style='font-size:0.7rem'>| {$extraVal}</span>";
                }
            }

            return new HtmlString($html);
        };
    }

    private static function makeDateColumn(string $name): Tables\Columns\TextInputColumn
    {
        return Tables\Columns\TextInputColumn::make($name)
            ->type('text')
            ->mask('99/99/9999')
            ->rules(['nullable', 'date_format:d/m/Y'])
            ->getStateUsing(function ($record) use ($name) {
                $val = data_get($record, $name);

                return $val ? Carbon::parse($val)->format('d/m/Y') : null;
            })
            ->updateStateUsing(function ($record, $state) use ($name) {
                $parsed = $state ? Carbon::createFromFormat('d/m/Y', $state)->format('Y-m-d') : null;
                $record->update([$name => $parsed]);
            });
    }

    protected static function buildEditForm(): array
    {
        $hintProjeto = 'Preenchido via Projetos';
        $hintCalc = 'Calculado automaticamente';

        return [
            Section::make('Informações do Projeto')
                ->description(static::fieldStats(
                    ['codigo', 'sigla', 'nova_sigla', 'unidade', 'marca', 'pipe_land', 'status'],
                    'status',
                ))
                ->schema([
                    Forms\Components\TextInput::make('codigo')->label('Código')->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\TextInput::make('sigla')->label('Sigla')->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\TextInput::make('nova_sigla')->label('Nova Sigla')->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\TextInput::make('unidade')->label('Unidade')->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\TextInput::make('marca')->label('Marca')->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\TextInput::make('pipe_land')->label('PIPE / LAND')->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\TextInput::make('status')->label('Status')->disabled()->dehydrated()->hint($hintProjeto),
                ])
                ->columns(3)
                ->collapsible(),

            Section::make('Gestor')
                ->description(static::fieldStats(
                    ['engenharia', 'comercial', 'arquitetura', 'entrada_ponto', 'status_contrato', 'data_assinatura_contrato'],
                ))
                ->schema([
                    Forms\Components\TextInput::make('engenharia')->label('Engenharia')->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\TextInput::make('comercial')->label('Comercial')->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\TextInput::make('arquitetura')->label('Arquitetura')->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\DatePicker::make('entrada_ponto')->label('Entrada do Ponto'),
                    Forms\Components\TextInput::make('status_contrato')
                        ->label('Status do Contrato')
                        ->disabled()->dehydrated(false)
                        ->hint($hintProjeto),
                    Forms\Components\DatePicker::make('data_assinatura_contrato')->label('Data Assinatura Contrato'),
                ])
                ->columns(3)
                ->collapsible(),

            Section::make('Total de Dias de Processo')
                ->description(static::fieldStats(
                    ['entrada_ponto_ate_inauguracao', 'assinatura_ate_inauguracao'],
                ))
                ->schema([
                    Forms\Components\TextInput::make('entrada_ponto_ate_inauguracao')
                        ->label('Entrada do Ponto até Inauguração')
                        ->disabled()->dehydrated(false)
                        ->suffix('dias')
                        ->hint($hintCalc),
                    Forms\Components\TextInput::make('assinatura_ate_inauguracao')
                        ->label('Assinatura até Inauguração')
                        ->disabled()->dehydrated(false)
                        ->suffix('dias')
                        ->hint($hintCalc),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(),

            Section::make('Visita Técnica / Projeto Executivo')
                ->description(function (Get $get): HtmlString {
                    $visita = $get('status_visita');
                    $proj = $get('status_proj_exec');
                    $parts = [];
                    if (filled($visita)) {
                        $parts[] = "Visita: {$visita}";
                    }
                    if (filled($proj)) {
                        $parts[] = "Proj: {$proj}";
                    }
                    $text = $parts ? implode(' | ', $parts) : 'Sem dados';
                    $color = $parts ? 'text-amber-500 dark:text-amber-400' : 'text-gray-400 dark:text-gray-500';

                    return new HtmlString("<span class='{$color}' style='font-size:0.75rem'>{$text}</span>");
                })
                ->schema([
                    Forms\Components\TextInput::make('status_visita')->label('Status Visita')
                        ->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\TextInput::make('status_proj_exec')->label('Status Projeto Executivo')
                        ->disabled()->dehydrated()->hint($hintProjeto),
                ])
                ->columns(2)
                ->collapsible(),

            Section::make('Contratações')
                ->description(function (Get $get): HtmlString {
                    $fields = ['civil', 'hidraulica', 'eletrica', 'incendio', 'instalacao_ar_condicionado', 'maquinas_ar_condicionado'];
                    $filled = 0;
                    foreach ($fields as $f) {
                        if (filled($get($f))) {
                            $filled++;
                        }
                    }
                    $total = count($fields);
                    $atraso = $get('homologados_em_atraso');

                    $color = $filled === $total ? 'text-green-500 dark:text-green-400' : 'text-amber-500 dark:text-amber-400';
                    $html = "<span class='{$color}' style='font-size:0.75rem'>{$filled}/{$total} contratados</span>";
                    if ($atraso === 'sim') {
                        $html .= "<span class='text-red-500 dark:text-red-400 ml-2' style='font-size:0.7rem'>| Homologados em atraso</span>";
                    }

                    return new HtmlString($html);
                })
                ->schema([
                    Forms\Components\TextInput::make('civil')->label('Civil'),
                    Forms\Components\TextInput::make('hidraulica')->label('Hidráulica'),
                    Forms\Components\TextInput::make('eletrica')->label('Elétrica'),
                    Forms\Components\TextInput::make('incendio')->label('Incêndio'),
                    Forms\Components\TextInput::make('instalacao_ar_condicionado')->label('Instalação Ar Condicionado'),
                    Forms\Components\TextInput::make('maquinas_ar_condicionado')->label('Máquinas Ar Condicionado'),
                    Forms\Components\Select::make('homologados_em_atraso')
                        ->label('Homologados em Atraso')
                        ->options(['sim' => 'Sim', 'nao' => 'Não'])
                        ->native(false),
                ])
                ->columns(3)
                ->collapsible()
                ->collapsed(),

            Section::make('Posse')
                ->description(function (Get $get): HtmlString {
                    $fields = ['status_data_posse', 'relatorio_fotografico', 'data_envio_relatorio_fotografico', 'data_atualizacao_comentario', 'termo_de_posse', 'comentarios'];
                    $filled = 0;
                    foreach ($fields as $f) {
                        if (filled($get($f))) {
                            $filled++;
                        }
                    }
                    $total = count($fields);
                    $pct = round(($filled / $total) * 100);
                    $color = $pct >= 80 ? 'text-green-500 dark:text-green-400' : ($pct >= 40 ? 'text-amber-500 dark:text-amber-400' : 'text-gray-400 dark:text-gray-500');

                    $html = "<span class='{$color}' style='font-size:0.75rem'>{$filled}/{$total} ({$pct}%)</span>";

                    $rel = $get('relatorio_fotografico');
                    if ($rel === 'pendencias') {
                        $html .= "<span class='text-red-500 dark:text-red-400 ml-2' style='font-size:0.7rem'>| Rel. com pendências</span>";
                    } elseif ($rel === 'nao_enviado') {
                        $html .= "<span class='text-amber-500 dark:text-amber-400 ml-2' style='font-size:0.7rem'>| Rel. não enviado</span>";
                    }
                    if ($get('termo_de_posse') === 'nao') {
                        $html .= "<span class='text-red-500 dark:text-red-400 ml-2' style='font-size:0.7rem'>| Sem termo de posse</span>";
                    }

                    return new HtmlString($html);
                })
                ->schema([
                    Forms\Components\DatePicker::make('status_data_posse')->label('Data de Posse')
                        ->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\Select::make('relatorio_fotografico')
                        ->label('Relatório Fotográfico')
                        ->options([
                            'enviado' => 'Enviado',
                            'pendencias' => 'Enviado com Pendências',
                            'nao_enviado' => 'Não Enviado',
                        ])
                        ->native(false),
                    Forms\Components\DatePicker::make('data_envio_relatorio_fotografico')->label('Data Envio Rel. Fotográfico'),
                    Forms\Components\DatePicker::make('data_atualizacao_comentario')->label('Data Atualização Comentário'),
                    Forms\Components\Select::make('termo_de_posse')
                        ->label('Termo de Posse')
                        ->options(['sim' => 'Sim', 'nao' => 'Não'])
                        ->native(false),
                    Forms\Components\Textarea::make('comentarios')->label('Comentários')->columnSpanFull(),
                ])
                ->columns(3)
                ->collapsible(),

            Section::make('Execução de Obras')
                ->description(function (Get $get): HtmlString {
                    $inicio = $get('inicio');
                    $fim = $get('fim');
                    $parts = [];
                    if (filled($inicio)) {
                        $parts[] = Carbon::parse($inicio)->format('d/m/Y');
                    }
                    if (filled($fim)) {
                        $parts[] = Carbon::parse($fim)->format('d/m/Y');
                    }
                    $periodo = $parts ? implode(' ? ', $parts) : null;
                    $prazo = $get('prazo_planejado');

                    $html = '';
                    if ($periodo) {
                        $html .= "<span class='text-amber-500 dark:text-amber-400' style='font-size:0.75rem'>{$periodo}</span>";
                    }
                    if (filled($prazo)) {
                        $html .= "<span class='text-gray-400 dark:text-gray-500 ml-2' style='font-size:0.7rem'>| Prazo: {$prazo} dias</span>";
                    }
                    if (! $html) {
                        $html = "<span class='text-gray-400 dark:text-gray-500' style='font-size:0.75rem'>Datas não definidas</span>";
                    }

                    return new HtmlString($html);
                })
                ->schema([
                    Forms\Components\DatePicker::make('inicio')->label('Início')
                        ->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\DatePicker::make('inicio_real')->label('Início Real'),
                    Forms\Components\DatePicker::make('fim')->label('Fim')
                        ->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\TextInput::make('prazo_planejado')->label('Prazo Planejado')
                        ->disabled()->dehydrated(false)->hint($hintCalc),
                    Forms\Components\TextInput::make('prazo_realizado')->label('Prazo Realizado')
                        ->disabled()->dehydrated(false)->hint($hintCalc),
                    Forms\Components\TextInput::make('link')->label('Link VISI')->url(),
                ])
                ->columns(3)
                ->collapsible(),

            Section::make('Implantação')
                ->description(function (Get $get): HtmlString {
                    $inaug = $get('inauguracao');
                    $crono = $get('cronograma_implantacao');
                    $fields = ['inicio_imp', 'fim_imp', 'cronograma_implantacao', 'inauguracao', 'mes', 'ano', 'observacao'];
                    $filled = 0;
                    foreach ($fields as $f) {
                        if (filled($get($f))) {
                            $filled++;
                        }
                    }
                    $total = count($fields);
                    $pct = round(($filled / $total) * 100);
                    $color = $pct >= 80 ? 'text-green-500 dark:text-green-400' : ($pct >= 40 ? 'text-amber-500 dark:text-amber-400' : 'text-gray-400 dark:text-gray-500');

                    $html = "<span class='{$color}' style='font-size:0.75rem'>{$filled}/{$total} ({$pct}%)</span>";
                    if (filled($inaug)) {
                        $html .= "<span class='text-green-500 dark:text-green-400 ml-2' style='font-size:0.7rem'>| Inaug: ".Carbon::parse($inaug)->format('d/m/Y').'</span>';
                    } elseif ($crono === 'nao_enviado') {
                        $html .= "<span class='text-red-500 dark:text-red-400 ml-2' style='font-size:0.7rem'>| Cronograma não enviado</span>";
                    }

                    return new HtmlString($html);
                })
                ->schema([
                    Forms\Components\DatePicker::make('inicio_imp')->label('Início')
                        ->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\DatePicker::make('fim_imp')->label('Fim')
                        ->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\Select::make('cronograma_implantacao')
                        ->label('Cronograma de Implantação')
                        ->options(['enviado' => 'Enviado', 'nao_enviado' => 'Não Enviado'])
                        ->native(false),
                    Forms\Components\TextInput::make('inauguracao')
                        ->label('Inauguração')
                        ->disabled()->dehydrated(false)
                        ->hint($hintProjeto)
                        ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('d/m/Y') : null),
                    Forms\Components\TextInput::make('imp_prazo_planej')->label('Prazo Planejado')
                        ->disabled()->dehydrated(false)->hint($hintCalc),
                    Forms\Components\TextInput::make('imp_prazo_realiz')->label('Prazo Realizado')
                        ->disabled()->dehydrated(false)->hint($hintCalc),
                    Forms\Components\TextInput::make('mes')->label('Mês'),
                    Forms\Components\TextInput::make('ano')->label('Ano'),
                    Forms\Components\Textarea::make('observacao')->label('Observação')->columnSpanFull(),
                ])
                ->columns(3)
                ->collapsible(),

            Section::make('Dados do Imóvel')
                ->description(static::fieldStats(
                    ['tipo_imovel', 'endereco', 'cidade', 'uf', 'empreendimento', 'locacao', 'contato_corretor'],
                ))
                ->schema([
                    Forms\Components\TextInput::make('tipo_imovel')->label('Tipo do Imóvel')
                        ->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\TextInput::make('endereco')->label('Endereço')
                        ->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\TextInput::make('cidade')->label('Cidade')
                        ->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\TextInput::make('uf')->label('Estado')
                        ->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\TextInput::make('empreendimento')->label('Empreendimento')
                        ->disabled()->dehydrated()->hint($hintProjeto),
                    Forms\Components\TextInput::make('locacao')
                        ->label('Locação')
                        ->disabled()->dehydrated(false)
                        ->hint($hintProjeto),
                    Forms\Components\TextInput::make('contato_corretor')
                        ->label('Contato Corretor / PP')
                        ->disabled()->dehydrated(false)
                        ->hint($hintProjeto),
                ])
                ->columns(3)
                ->collapsible()
                ->collapsed(),

            Section::make('% de Obra')
                ->description(function (Get $get): HtmlString {
                    $prev = $get('percentual_obra');
                    $exec = $get('percentual_obra_executado');
                    $desvio = $get('desvio');

                    $parts = [];
                    if (filled($prev)) {
                        $parts[] = "Previsto: {$prev}%";
                    }
                    if (filled($exec)) {
                        $parts[] = "Executado: {$exec}%";
                    }
                    if (filled($desvio)) {
                        $desvioColor = floatval($desvio) < 0 ? 'text-red-500 dark:text-red-400' : 'text-green-500 dark:text-green-400';
                        $parts[] = "<span class='{$desvioColor}'>Desvio: {$desvio}%</span>";
                    }

                    $html = $parts
                        ? "<span style='font-size:0.75rem'>".implode(' <span class="text-gray-400">|</span> ', $parts).'</span>'
                        : "<span class='text-gray-400 dark:text-gray-500' style='font-size:0.75rem'>Sem dados de progresso</span>";

                    return new HtmlString($html);
                })
                ->schema([
                    Forms\Components\TextInput::make('dias_para_inauguracao')
                        ->label('Dias para Inauguração')
                        ->disabled()->dehydrated(false)->hint($hintCalc),
                    Forms\Components\TextInput::make('dias_obra_inicio_pmo')
                        ->label('Dias de Obra (Início PMO)')
                        ->disabled()->dehydrated(false)->hint($hintCalc),
                    Forms\Components\TextInput::make('percentual_obra')->label('% Previsto')->numeric()->suffix('%'),
                    Forms\Components\TextInput::make('percentual_obra_executado')->label('% Executado')->numeric()->suffix('%'),
                    Forms\Components\TextInput::make('desvio')->label('Desvio')
                        ->disabled()->dehydrated(false)->suffix('%')->hint($hintCalc),
                ])
                ->columns(3)
                ->collapsible(),

            Section::make('Acompanhamento de Obra')
                ->description(function (Get $get): HtmlString {
                    $criticos = $get('itens_criticos');
                    if (filled($criticos)) {
                        return new HtmlString("<span class='text-red-500 dark:text-red-400' style='font-size:0.75rem'>Itens críticos: {$criticos}</span>");
                    }

                    return new HtmlString("<span class='text-green-500 dark:text-green-400' style='font-size:0.75rem'>Sem itens críticos</span>");
                })
                ->schema([
                    Forms\Components\TextInput::make('itens_criticos')->label('Itens Críticos'),
                    Forms\Components\Textarea::make('descricao_itens_criticos')->label('Descrição Itens Críticos'),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(),

            Section::make('Cronograma Visi')
                ->description(static::fieldStats(
                    ['cronograma_visi', 'camera_unidade', 'ponto_atencao'],
                ))
                ->schema([
                    Forms\Components\Select::make('cronograma_visi')
                        ->label('Cronograma Visi')
                        ->options(['enviado' => 'Enviado', 'nao_enviado' => 'Não Enviado'])
                        ->native(false),
                    Forms\Components\Select::make('camera_unidade')
                        ->label('Câmera na Unidade')
                        ->options(['sim' => 'Sim', 'nao' => 'Não'])
                        ->native(false),
                    Forms\Components\Textarea::make('ponto_atencao')->label('Ponto de Atenção')->columnSpanFull(),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(),

            Section::make('Contas de Consumo')
                ->description(function (Get $get): HtmlString {
                    $items = [
                        'E' => $get('energia'),
                        'A' => $get('agua'),
                        'G' => $get('gas'),
                    ];
                    $parts = [];
                    foreach ($items as $label => $val) {
                        if (filled($val)) {
                            $isOk = str_contains(strtolower($val), 'ligada');
                            $color = $isOk ? 'text-green-500 dark:text-green-400' : 'text-amber-500 dark:text-amber-400';
                            $parts[] = "<span class='{$color}'>{$label}</span>";
                        } else {
                            $parts[] = "<span class='text-gray-400 dark:text-gray-500'>{$label}</span>";
                        }
                    }

                    return new HtmlString("<span style='font-size:0.75rem'>".implode(' ', $parts).'</span>');
                })
                ->schema([
                    Forms\Components\Select::make('energia')
                        ->label('Energia')
                        ->options([
                            'Ligada / Rateio' => 'Ligada / Rateio',
                            'Ligada em nome da Smart' => 'Ligada em nome da Smart',
                            'Ligada, necessário trocar titularidade' => 'Necessário trocar titularidade',
                            'Pendente, responsavel Smart' => 'Pendente, resp. Smart',
                            'Pendente, responsavel PP' => 'Pendente, resp. PP',
                            'GERADOR' => 'GERADOR',
                        ])
                        ->native(false),
                    Forms\Components\DatePicker::make('previsao_ligacao_energia')->label('Previsão Ligação Energia'),
                    Forms\Components\TextInput::make('gerador_contratual')->label('Gerador Contratual'),
                    Forms\Components\Select::make('agua')
                        ->label('Água')
                        ->options([
                            'Ligada em nome da Smart' => 'Ligada em nome da Smart',
                            'Ligada, necessário trocar titularidade' => 'Necessário trocar titularidade',
                            'Pendente, responsavel Smart' => 'Pendente, resp. Smart',
                            'Pendente, responsavel PP' => 'Pendente, resp. PP',
                            'Ligada / Rateio' => 'Ligada / Rateio',
                        ])
                        ->native(false),
                    Forms\Components\Select::make('gas')
                        ->label('Gás')
                        ->options([
                            'Ligada em nome da Smart' => 'Ligada em nome da Smart',
                            'Ligada, necessário trocar titularidade' => 'Necessário trocar titularidade',
                            'Pendente, responsavel Smart' => 'Pendente, resp. Smart',
                            'Pendente, responsavel PP' => 'Pendente, resp. PP',
                            'Boiler Instalado provisório' => 'Boiler Instalado provisório',
                        ])
                        ->native(false),
                    Forms\Components\Textarea::make('comentario')->label('Comentário')->columnSpanFull(),
                ])
                ->columns(3)
                ->collapsible(),

            Section::make('Pós-Obra')
                ->description(function (Get $get): HtmlString {
                    $fields = ['email_solicitacao_cl', 'envio_qrcod', 'checklist_manutencao', 'data_check_list', 'inicio_prev_pendencias', 'termino_prev_pendencias', 'elevador', 'gestor_pos_obra', 'comentarios_adicionais'];
                    $filled = 0;
                    foreach ($fields as $f) {
                        if (filled($get($f))) {
                            $filled++;
                        }
                    }
                    $total = count($fields);
                    $pct = round(($filled / $total) * 100);
                    $color = $pct >= 80 ? 'text-green-500 dark:text-green-400' : ($pct >= 40 ? 'text-amber-500 dark:text-amber-400' : 'text-gray-400 dark:text-gray-500');

                    $html = "<span class='{$color}' style='font-size:0.75rem'>{$filled}/{$total} ({$pct}%)</span>";

                    $checklist = $get('checklist_manutencao');
                    if ($checklist === 'em_atraso') {
                        $html .= "<span class='text-red-500 dark:text-red-400 ml-2' style='font-size:0.7rem'>| Checklist em atraso</span>";
                    } elseif ($checklist === 'concluido') {
                        $html .= "<span class='text-green-500 dark:text-green-400 ml-2' style='font-size:0.7rem'>| Checklist concluído</span>";
                    }

                    return new HtmlString($html);
                })
                ->schema([
                    Forms\Components\Select::make('email_solicitacao_cl')
                        ->label('E-mail Solicitação CL')
                        ->options(['enviado' => 'Enviado', 'nao_enviado' => 'Não Enviado'])
                        ->native(false),
                    Forms\Components\Select::make('envio_qrcod')
                        ->label('Envio QRCODE')
                        ->options(['enviado' => 'Enviado', 'nao_enviado' => 'Não Enviado'])
                        ->native(false),
                    Forms\Components\Select::make('checklist_manutencao')
                        ->label('Checklist Manutenção')
                        ->options([
                            'concluido' => 'Concluído',
                            'em_andamento' => 'Em andamento',
                            'em_atraso' => 'Em atraso',
                            'nao_iniciado' => 'Não iniciado',
                        ])
                        ->native(false),
                    Forms\Components\DatePicker::make('data_check_list')->label('Data Check List'),
                    Forms\Components\DatePicker::make('inicio_prev_pendencias')->label('Início Prev. Pendências'),
                    Forms\Components\DatePicker::make('termino_prev_pendencias')->label('Término Prev. Pendências'),
                    Forms\Components\TextInput::make('elevador')->label('Elevador'),
                    Forms\Components\TextInput::make('gestor_pos_obra')->label('Gestor Pós Obra'),
                    Forms\Components\Textarea::make('comentarios_adicionais')->label('Comentários Adicionais')->columnSpanFull(),
                ])
                ->columns(3)
                ->collapsible()
                ->collapsed(),

            static::buildPontosAtencaoSection(),
        ];
    }

    protected static function getPontosAtencaoValues(Obras $record): array
    {
        $result = [];
        $colunas = ColunaPersonalizada::query()
            ->where('obra_id', $record->id)
            ->whereNotNull('nome')
            ->get();

        foreach ($colunas as $coluna) {
            // Usar ID como chave para evitar problemas com slugs
            $key = 'ponto_atencao_id_'.$coluna->id;
            $result[$key] = $coluna->valor;
        }

        return $result;
    }

    protected static function buildPontosAtencaoSection(): Section
    {
        return Section::make('Pontos de Atenção')
            ->description('Campos personalizados por projeto')
            ->schema(function (Get $get) {
                $obrasId = $get('id');
                if (! $obrasId) {
                    return [];
                }

                $colunas = ColunaPersonalizada::query()
                    ->where('obra_id', $obrasId)
                    ->whereNotNull('nome')
                    ->orderBy('nome')
                    ->get();

                if ($colunas->isEmpty()) {
                    return [
                        Forms\Components\Placeholder::make('sem_colunas')
                            ->label('')
                            ->content('Nenhum campo personalizado cadastrado para esta obra.'),
                    ];
                }

                $fields = [];
                foreach ($colunas as $coluna) {
                    $key = 'ponto_atencao_id_'.$coluna->id;
                    $tipo = (string) ($coluna->tipo ?? 'texto');

                    if ($tipo === 'select') {
                        $opcoes = collect($coluna->opcoes ?? [])
                            ->map(fn ($item) => trim((string) $item))
                            ->filter(fn ($item) => $item !== '')
                            ->values()
                            ->all();

                        $opcoesMap = collect($opcoes)
                            ->mapWithKeys(fn ($item) => [$item => $item])
                            ->all();

                        $fields[] = Forms\Components\Select::make($key)
                            ->label((string) $coluna->nome)
                            ->options($opcoesMap)
                            ->native(false)
                            ->default($coluna->valor);
                    } elseif ($tipo === 'numero') {
                        $fields[] = Forms\Components\TextInput::make($key)
                            ->label((string) $coluna->nome)
                            ->numeric()
                            ->default($coluna->valor);
                    } elseif ($tipo === 'data') {
                        $fields[] = Forms\Components\DatePicker::make($key)
                            ->label((string) $coluna->nome)
                            ->default($coluna->valor);
                    } else {
                        $fields[] = Forms\Components\TextInput::make($key)
                            ->label((string) $coluna->nome)
                            ->default($coluna->valor);
                    }
                }

                return $fields;
            })
            ->columns(2)
            ->collapsible();
    }

    protected static function buildFilters(): array
    {
        $filters = [];

        foreach (ObrasColumnFilters::getSelectFilters() as $column => $options) {
            if (Str::startsWith($column, 'ponto_atencao_')) {
                continue;
            }

            $filter = Tables\Filters\SelectFilter::make($column)
                ->multiple()
                ->options($options);

            if ($column === 'status') {
                $filter->default(['Em processo', 'Inaugurada', 'Obras']);
            }

            $filters[] = $filter;
        }

        $filters = array_merge($filters, static::buildPontosAtencaoFilters());

        foreach (ObrasColumnFilters::getDateRangeColumns() as $column) {
            if (Str::startsWith($column, 'ponto_atencao_')) {
                continue;
            }

            $filters[] = Tables\Filters\Filter::make($column)
                ->form([
                    Forms\Components\DatePicker::make('from')->label('De'),
                    Forms\Components\DatePicker::make('until')->label('Até'),
                ])
                ->query(function (Builder $query, array $data) use ($column): Builder {
                    return $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate($column, '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate($column, '<=', $date));
                });
        }

        return $filters;
    }

    protected static function buildPontosAtencaoFilters(): array
    {
        $definicoes = static::getPontosAtencaoDefinitions();

        if ($definicoes->isEmpty()) {
            return [];
        }

        $filters = [];

        foreach ($definicoes as $nome => $definicao) {
            $key = 'ponto_atencao_'.Str::slug((string) $nome, '_');
            $tipo = (string) ($definicao->tipo ?? 'texto');

            if ($tipo === 'select') {
                $opcoes = collect($definicao->opcoes ?? [])
                    ->map(fn ($item) => trim((string) $item))
                    ->filter(fn ($item) => $item !== '')
                    ->values()
                    ->all();

                $filters[] = Tables\Filters\SelectFilter::make($key)
                    ->label((string) $nome)
                    ->multiple()
                    ->options(collect($opcoes)->mapWithKeys(fn ($item) => [$item => $item])->all())
                    ->query(function (Builder $query, array $data) use ($nome): Builder {
                        $values = $data['values'] ?? [];

                        if (empty($values)) {
                            return $query;
                        }

                        return $query->whereHas('colunasPersonalizadas', function (Builder $subQuery) use ($nome, $values): void {
                            $subQuery->where('nome', $nome)
                                ->whereIn('valor', $values);
                        });
                    });
            } elseif ($tipo === 'data') {
                $filters[] = Tables\Filters\Filter::make($key)
                    ->label((string) $nome)
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('De'),
                        Forms\Components\DatePicker::make('until')->label('Até'),
                    ])
                    ->query(function (Builder $query, array $data) use ($nome): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereHas('colunasPersonalizadas', function (Builder $subQuery) use ($nome, $date): void {
                                $subQuery->where('nome', $nome)
                                    ->whereDate('valor', '>=', $date);
                            }))
                            ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereHas('colunasPersonalizadas', function (Builder $subQuery) use ($nome, $date): void {
                                $subQuery->where('nome', $nome)
                                    ->whereDate('valor', '<=', $date);
                            }));
                    });
            } else {
                $filters[] = Tables\Filters\Filter::make($key)
                    ->label((string) $nome)
                    ->form([
                        Forms\Components\TextInput::make('value')
                            ->label((string) $nome)
                            ->numeric($tipo === 'numero'),
                    ])
                    ->query(function (Builder $query, array $data) use ($nome): Builder {
                        $value = $data['value'] ?? null;

                        if ($value === null || $value === '') {
                            return $query;
                        }

                        return $query->whereHas('colunasPersonalizadas', function (Builder $subQuery) use ($nome, $value): void {
                            $subQuery->where('nome', $nome)
                                ->where('valor', $value);
                        });
                    });
            }
        }

        return $filters;
    }
}
