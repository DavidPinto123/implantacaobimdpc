<?php

namespace App\Filament\Resources\AutorizacaoServicos\Schemas;

use App\Enums\AsStatus;
use App\Filament\Components\Forms\MoneyInput;
use App\Models\AsEscopo;
use App\Models\AutorizacaoServico;
use App\Models\Construtora;
use App\Models\Obras;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Support\RawJs;

class AutorizacaoServicoForm
{
    private static function asDirectory(?AutorizacaoServico $record): string
    {
        return filled($record?->id)
            ? 'autorizacao-servico/'.$record->id.'/anexos'
            : 'autorizacao-servico/tmp/anexos';
    }

    private static function isEditable(?AutorizacaoServico $record): bool
    {
        return false;
    }

    public static function obraOptionLabel(Obras $obra): string
    {
        $unidade = trim((string) ($obra->unidade ?? ''));

        return $unidade !== '' ? $unidade : self::fallbackOptionLabel('Unidade', $obra->id);
    }

    public static function asEscopoOptionLabel(AsEscopo $escopo): string
    {
        $descricao = trim((string) ($escopo->escopo ?? ''));

        if ($descricao !== '') {
            return $descricao;
        }

        $numeroAs = trim((string) ($escopo->numero_as ?? ''));

        return $numeroAs !== '' ? $numeroAs : self::fallbackOptionLabel('Escopo', $escopo->id);
    }

    public static function construtoraOptionLabel(Construtora $construtora): string
    {
        $nome = trim((string) ($construtora->nome ?? ''));

        return $nome !== '' ? $nome : self::fallbackOptionLabel('Fornecedor', $construtora->id);
    }

    public static function usuarioOptionLabel(User $user): string
    {
        $nome = trim((string) ($user->name ?? ''));

        if ($nome !== '') {
            return $nome;
        }

        $email = trim((string) ($user->email ?? ''));

        return $email !== '' ? $email : self::fallbackOptionLabel('Usuário', $user->id);
    }

    private static function fallbackOptionLabel(string $prefixo, mixed $id): string
    {
        return filled($id) ? "{$prefixo} #{$id}" : "{$prefixo} sem identificação";
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('AS')
                            ->icon(Heroicon::OutlinedDocumentText)
                            ->description('AUTORIZAÇÃO DE SERVIÇO')
                            ->schema([
                                Group::make()
                                    ->schema([
                                        Textarea::make('numero_as')
                                            ->label('Número da AS')
                                            ->required()
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->columnSpanFull(),

                                        Select::make('obra_id')
                                            ->label('Unidade')
                                            ->relationship('obra', 'unidade')
                                            ->getOptionLabelFromRecordUsing(fn (Obras $record): string => self::obraOptionLabel($record))
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),

                                Group::make()
                                    ->schema([
                                        Select::make('as_escopo_id')
                                            ->label('Escopo')
                                            ->relationship(
                                                'asEscopo',
                                                'escopo',
                                                modifyQueryUsing: fn ($query) => $query->globais(),
                                            )
                                            ->getOptionLabelFromRecordUsing(fn (AsEscopo $record): string => self::asEscopoOptionLabel($record))
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->disabled()
                                            ->dehydrated(false),

                                        Select::make('construtora_id')
                                            ->label('Fornecedor')
                                            ->relationship('construtora', 'nome')
                                            ->getOptionLabelFromRecordUsing(fn (Construtora $record): string => self::construtoraOptionLabel($record))
                                            ->searchable()
                                            ->preload()
                                            ->disabled(fn (?AutorizacaoServico $record): bool => ! self::isEditable($record)),

                                        TextInput::make('numero_complemento')
                                            ->label('Complemento')
                                            ->maxLength(10)
                                            ->default('')
                                            ->disabled()
                                            ->dehydrated(false),

                                        TextInput::make('status')
                                            ->label('Status')
                                            ->required()
                                            ->default(AsStatus::RASCUNHO->value)
                                            ->disabled()
                                            ->dehydrated(fn (?AutorizacaoServico $record): bool => blank($record?->id)),
                                    ])
                                    ->columns(2),

                                Textarea::make('observacoes')
                                    ->label('Observações')
                                    ->rows(4)
                                    ->disabled(fn (?AutorizacaoServico $record): bool => ! self::isEditable($record))
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Dados do PDF')
                            ->compact()
                            ->schema([
                                DatePicker::make('data_inicio_servico')
                                    ->label('Data início')
                                    ->disabled(fn (?AutorizacaoServico $record): bool => ! self::isEditable($record)),

                                DatePicker::make('data_termino_servico')
                                    ->label('Data término')
                                    ->rules(['nullable', 'after_or_equal:data_inicio_servico'])
                                    ->disabled(fn (?AutorizacaoServico $record): bool => ! self::isEditable($record)),

                                DatePicker::make('data_entrega_material')
                                    ->label('Data entrega')
                                    ->disabled(fn (?AutorizacaoServico $record): bool => ! self::isEditable($record)),

                                RichEditor::make('descricao_servico_pdf')
                                    ->label('Descrição no PDF')
                                    ->fileAttachmentsDisk((string) config('filesystems.media_disk', 'r2'))
                                    ->fileAttachmentsDirectory('autorizacao-servico/rich-editor')
                                    ->fileAttachmentsVisibility('public')
                                    ->fileAttachmentsAcceptedFileTypes([
                                        'image/png',
                                        'image/jpeg',
                                        'image/gif',
                                        'image/webp',
                                        'image/avif',
                                    ])
                                    ->resizableImages()
                                    ->maxLength(5000)
                                    ->disabled(fn (?AutorizacaoServico $record): bool => ! self::isEditable($record))
                                    ->columnSpanFull(),

                                Repeater::make('parcelamento_autorizacao_servico')
                                    ->label('Parcelamento')
                                    ->table([
                                        TableColumn::make('Parcela'),
                                        TableColumn::make('%'),
                                        TableColumn::make('Valor'),
                                        TableColumn::make('Observação'),
                                    ])
                                    ->schema([
                                        TextInput::make('parcela')
                                            ->label('Parcela'),
                                        TextInput::make('percentual')
                                            ->label('%')
                                            ->required()
                                            ->maxLength(20)
                                            ->inputMode('decimal')
                                            ->mask(self::percentualMask())
                                            ->afterStateUpdatedJs(self::atualizarValorParcelaJs()),
                                        TextInput::make('valor')
                                            ->label('Valor')
                                            ->disabled()
                                            ->dehydrated(false),
                                        TextInput::make('observacao')
                                            ->label('Observação'),
                                    ])
                                    ->defaultItems(0)
                                    ->disabled(fn (?AutorizacaoServico $record): bool => ! self::isEditable($record))
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpan(2),

                Group::make()
                    ->schema([
                        Section::make('Valores')
                            ->schema([
                                MoneyInput::make('valor_estimado', 'Valor estimado')
                                    ->disabled(fn (?AutorizacaoServico $record): bool => ! self::isEditable($record)),
                                MoneyInput::make('valor', 'Valor fechado')
                                    ->afterStateUpdatedJs(self::atualizarValoresParcelasJs())
                                    ->disabled(fn (?AutorizacaoServico $record): bool => ! self::isEditable($record)),
                                MoneyInput::make('desconto_autorizacao_servico', 'Desconto')
                                    ->afterStateUpdatedJs(self::atualizarValoresParcelasJs())
                                    ->disabled(fn (?AutorizacaoServico $record): bool => ! self::isEditable($record)),
                            ]),

                        Section::make('Anexos')
                            ->compact()
                            ->schema([
                                FileUpload::make('anexo_autorizacao_servico')
                                    ->label('PDF gerado')
                                    ->disk((string) config('filesystems.media_disk', 'r2'))
                                    ->directory(fn (?AutorizacaoServico $record) => self::asDirectory($record))
                                    ->downloadable()
                                    ->openable()
                                    ->preserveFilenames()
                                    ->disabled(fn (?AutorizacaoServico $record): bool => ! self::isEditable($record)),

                                FileUpload::make('anexos_autorizacao_servico')
                                    ->label('Anexos adicionais')
                                    ->helperText('Inclua aqui arquivos que serão enviados como anexo no e-mail ao enviar AS.')
                                    ->disk((string) config('filesystems.media_disk', 'r2'))
                                    ->directory(fn (?AutorizacaoServico $record) => self::asDirectory($record))
                                    ->multiple()
                                    ->panelLayout('grid')
                                    ->downloadable()
                                    ->openable()
                                    ->preserveFilenames()
                                    ->disabled(fn (?AutorizacaoServico $record): bool => ! self::isEditable($record)),
                            ]),

                        Section::make('Dados do sistema')
                            ->compact()
                            ->schema([
                                Select::make('created_by_id')
                                    ->label('Criado por')
                                    ->relationship('createdBy', 'name')
                                    ->getOptionLabelFromRecordUsing(fn (User $record): string => self::usuarioOptionLabel($record))
                                    ->searchable()
                                    ->preload()
                                    ->disabled()
                                    ->dehydrated(false),

                                DateTimePicker::make('created_at')
                                    ->label('Criado em')
                                    ->seconds(false)
                                    ->disabled()
                                    ->dehydrated(false),

                                DateTimePicker::make('updated_at')
                                    ->label('Atualizado em')
                                    ->seconds(false)
                                    ->disabled()
                                    ->dehydrated(false),

                                DateTimePicker::make('enviado_em')
                                    ->label('Enviado em')
                                    ->seconds(false)
                                    ->disabled()
                                    ->dehydrated(false),

                                Select::make('enviado_por_id')
                                    ->label('Enviado por')
                                    ->relationship('enviadoPor', 'name')
                                    ->getOptionLabelFromRecordUsing(fn (User $record): string => self::usuarioOptionLabel($record))
                                    ->searchable()
                                    ->preload()
                                    ->disabled()
                                    ->dehydrated(false),

                                DateTimePicker::make('cancelado_em')
                                    ->label('Cancelado em')
                                    ->seconds(false)
                                    ->disabled()
                                    ->dehydrated(false),

                                Select::make('cancelado_por_id')
                                    ->label('Cancelado por')
                                    ->relationship('canceladoPor', 'name')
                                    ->getOptionLabelFromRecordUsing(fn (User $record): string => self::usuarioOptionLabel($record))
                                    ->searchable()
                                    ->preload()
                                    ->disabled()
                                    ->dehydrated(false),

                                Textarea::make('motivo_cancelamento')
                                    ->label('Motivo do cancelamento')
                                    ->rows(3)
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpan(1),

            ])
            ->columns(3);
    }

    private static function percentualMask(): RawJs
    {
        return RawJs::make(<<<'JS'
            (() => {
                let value = String($input ?? '')
                    .replace(/[^\d,]/g, '')
                    .replace(/,+/g, ',');

                const parts = value.split(',');
                let integer = parts[0] || '';
                const decimal = parts.length > 1 ? parts.slice(1).join('').slice(0, 2) : null;

                integer = integer.replace(/^0+(?=\d)/, '');

                if (integer === '') {
                    integer = decimal === null ? '' : '0';
                }

                return decimal === null ? integer : `${integer},${decimal}`;
            })()
        JS);
    }

    private static function atualizarValorParcelaJs(): string
    {
        return <<<'JS'
            const parseNumero = (valor) => {
                if (valor === null || valor === undefined || valor === '') {
                    return 0;
                }

                if (typeof valor === 'number') {
                    return valor;
                }

                const normalizado = String(valor).replace(/\./g, '').replace(',', '.');
                const numero = Number(normalizado);

                return Number.isFinite(numero) ? numero : 0;
            };

            const formatMoeda = (valor) => Number(valor || 0).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

            const valorFechado = parseNumero($get('../../valor'));
            const desconto = parseNumero($get('../../desconto_autorizacao_servico'));
            const valorLiquido = Math.max(valorFechado - Math.max(desconto, 0), 0);
            const percentual = parseNumero($state);

            $set('valor', formatMoeda(Math.round((valorLiquido * (percentual / 100)) * 100) / 100));
        JS;
    }

    private static function atualizarValoresParcelasJs(): string
    {
        return <<<'JS'
            const parseNumero = (valor) => {
                if (valor === null || valor === undefined || valor === '') {
                    return 0;
                }

                if (typeof valor === 'number') {
                    return valor;
                }

                const normalizado = String(valor).replace(/\./g, '').replace(',', '.');
                const numero = Number(normalizado);

                return Number.isFinite(numero) ? numero : 0;
            };

            const formatMoeda = (valor) => Number(valor || 0).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

            const valorFechado = parseNumero($get('valor'));
            const desconto = parseNumero($get('desconto_autorizacao_servico'));
            const valorLiquido = Math.max(valorFechado - Math.max(desconto, 0), 0);

            Object.entries($get('parcelamento_autorizacao_servico') || {}).forEach(([chave, parcela]) => {
                const percentual = parseNumero(parcela?.percentual);
                const valor = Math.round((valorLiquido * (percentual / 100)) * 100) / 100;

                $set(`parcelamento_autorizacao_servico.${chave}.valor`, formatMoeda(valor));
            });
        JS;
    }
}
