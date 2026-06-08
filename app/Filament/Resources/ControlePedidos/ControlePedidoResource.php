<?php

namespace App\Filament\Resources\ControlePedidos;

use App\Filament\Resources\ControlePedidos\Pages\CreateControlePedido;
use App\Filament\Resources\ControlePedidos\Pages\EditControlePedido;
use App\Filament\Resources\ControlePedidos\Pages\ListControlePedidos;
use App\Filament\Resources\ControlePedidos\Schemas\ControlePedidoForm;
use App\Filament\Resources\ControlePedidos\Tables\ControlePedidosTable;
use App\Models\ControlePedido;
use App\Models\OrdemInvestimento;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ControlePedidoResource extends Resource
{
    protected static ?string $model = ControlePedido::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ClipboardDocumentList;

    protected static ?string $navigationLabel = 'Controle de Pedidos';

    protected static ?string $pluralModelLabel = 'Controle de Pedidos';

    protected static ?string $modelLabel = 'Controle de Pedido';

    protected static string|null|UnitEnum $navigationGroup = 'Implantação BIM';

    protected static ?string $navigationParentItem = 'Orçamentos';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return ControlePedidoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ControlePedidosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListControlePedidos::route('/'),
            'create' => CreateControlePedido::route('/create'),
            'edit' => EditControlePedido::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'Pedido';
    }

    /*
    |--------------------------------------------------------------------------
    | MAPA CENTRAL DOS PEDIDOS
    |--------------------------------------------------------------------------
    */

    public static function pedidosMap(): array
    {
        return [

            'EXECUÇÃO DE OBRA CIVIL - RECHEIO' => ['1.1'],
            'EXECUÇÃO DE OBRA CIVIL - SHELL' => ['2.1'],
            'INSTAL. AR CONDICIONADO' => ['3.1'],
            'MÁQ. AR CONDICIONADO' => ['4.1'],
            'INSTAL. ELÉTRICA' => ['5.1'],
            'INSTAL. COMBATE INCÊNDIO' => ['6.1'],
            'FORN. EQUIPAMENTO NO BREAK' => ['7.1'],
            'GERENCIAMENTO' => ['8.1'],
            'FORN. E INSTAL. - DIVISÓRIAS BANHEIRO' => ['9.1'],
            'FORN. PISO VINILICO' => ['10.1'],
            'FORN. PISO DE BORRACHA' => ['11.1'],
            'FORN. LUMINÁRIAS' => ['12.1'],
            'FORN. E INSTAL. - AQUECEDORES' => ['13.1'],
            'FORN. E INSTAL. - MARCENARIA' => ['14.1'],
            'FORN. BALANÇA' => ['15.1'],
            'BEBEDOURO ACESSÍVEL' => ['16.1'],
            'BEBEDOURO INDUSTRIAL' => ['17.1'],

            'FORN. E INSTAL. - ESPALDAR' => ['18.1'],
            'FORN. E INSTAL. - RACK FUNCIONAL' => ['19.1'],
            'FORN. TELEVISORES' => ['20.1'],
            'FORN. ELETRODOMÉSTICOS' => ['21.1'],
            'FORN. POLTRONAS DE MASSAGEM' => ['22.1'],
            'FORN. SECADOR DE MÃOS' => ['23.1'],
            'FORN. DUCHAS' => ['24.1'],

            'RELÓGIO DE PAREDE' => ['27.1'],
            'BICICLETÁRIO' => ['29.1'],

            'FORN. E INSTAL. - FACHADA' => ['30.1'],
            'FORN. E INSTAL. - PORTA AUTOMÁTICA' => ['31.1'],
            'PLATAFORMA PNE' => ['32.1'],
            'FORN. E INSTAL. - ESTRUTURA METÁLICA' => ['33.1'],
            'FORN. E INSTAL. - ELEVADOR' => ['34.1'],

            'CONSULTORIA - ENTRADA DE ENERGIA' => ['35.1'],
            'FORN. E INSTAL. - ENTRADA DE ENERGIA' => ['36.1'],
            'FORN. E INSTAL. - ACÚSTICA' => ['38.1'],
            'LOCAÇÃO DE GERADOR' => ['39.1'],
            'COMUNICAÇÃO VISUAL INTERNA' => ['40.1'],

            'ENXOVAL' => ['45.1'],
            'SERV. SEGURANÇA' => ['46.1'],
            'TI E SONORIZAÇÃO' => ['47.1'],
            'PRÉ - OBRA' => ['48.1'],

            'DESFIBRILADOR' => ['50.1'],
            'PISO DRENANTE' => ['51.1'],
            'INSTAL. HIDRÁULICAS' => ['52.1'],

            // Itens não encontrados na planilha de controle (sim ou não)

            'VENTILADORES' => ['53.1'],
            'INSTAL. VENTILADORES' => ['54.1'],

            'LIMPEZA FINA' => ['55.1'],

            'PELÍCULA DA FACHADA' => ['56.1'],

            'CADEIRAS' => ['57.1'],
            'CADEIRAS - OPERAÇÕES' => ['58.1'],

            'QUADRO ACRÍLICO - OPERAÇÕES' => ['59.1'],

            'CAPACHO DA ENTRADA - OPERAÇÕES' => ['60.1'],

            'LIXEIRAS - OPERAÇÕES' => ['61.1'],

            'ADITIVO' => ['62.1'],

        ];
    }

    protected static function buscarValorSimulacao($projetoId, $nomeItem): float
    {
        $simulacao = OrdemInvestimento::where('projeto_id', $projetoId)
            ->latest()
            ->first();

        if (! $simulacao || ! $simulacao->estrutura) {
            return 0;
        }

        foreach ($simulacao->estrutura as $linha) {

            if ($linha['nome'] === $nomeItem) {

                $padrao = is_numeric($linha['padrao'] ?? null)
                    ? (float) $linha['padrao']
                    : 0;

                $ad = is_numeric($linha['ad'] ?? null)
                    ? (float) $linha['ad']
                    : 0;

                return $padrao + $ad;
            }
        }

        return 0;
    }

    public static function afterSave($record)
    {
        $record->itens()->delete();

        $map = self::pedidosMap();

        foreach ($map as $nome => $codigos) {

            $codigo = $codigos[0];
            $codigoKey = str_replace('.', '_', $codigo);
            $contratado = (bool) data_get($record->pedidos, $codigoKey, false);
            $valor = $contratado ? self::buscarValorSimulacao($record->projeto_id, $nome) : 0;

            $record->itens()->create([
                'codigo' => $codigo,
                'nome' => $nome,
                'contratado' => $contratado,
                'valor' => $valor,
            ]);
        }
    }
}
