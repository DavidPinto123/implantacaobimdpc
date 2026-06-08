# Módulo: Financeiro

Controle financeiro dos projetos: pedidos, faturamento, notas fiscais, ordens de investimento.

## Models

### ControlePedido
- **Arquivo**: `app/Models/ControlePedido.php`
- **Observer**: `ControlePedidoObserver`
- Controle de pedidos/compras por projeto

### ControlePedidoItem
- **Arquivo**: `app/Models/ControlePedidoItem.php`
- Itens individuais de um pedido

### Faturamento
- **Arquivo**: `app/Models/Faturamento.php`
- Controle de faturamento

### TipoFaturamento
- **Arquivo**: `app/Models/TipoFaturamento.php`
- Tipos/categorias de faturamento

### NotaFiscal
- **Arquivo**: `app/Models/NotaFiscal.php`
- **Resource**: desativado (prefixo `.`)

### OrdemInvestimento
- **Arquivo**: `app/Models/OrdemInvestimento.php`
- Ordens de investimento (OI)

### ObraRecebimento
- **Arquivo**: `app/Models/ObraRecebimento.php`
- Recebimentos vinculados às obras

### ControleNotaFiscalNota
- **Arquivo**: `app/Models/ControleNotaFiscalNota.php`
- **Tabela**: `controle_nota_fiscal_notas`
- Notas fiscais importadas pela construtora
- Relacionamentos: `item` (BelongsTo ControleNotaFiscalItem), `auxiliar` (BelongsTo ControleNotaFiscalAuxiliar), `importadoPor` (BelongsTo User), `decididoPor` (BelongsTo User)
- Status centralizados via `getStatusOptions/Label/Color()`: `pendente`, `em_analise`, `aprovado`, `reprovado`
- Campos de auditoria: `importado_por_id`, `decidido_por_id`, `decidido_em`

## Filament Resources

### ControlePedidosResource
- **Pasta**: `app/Filament/Resources/ControlePedidosResource/`
- CRUD de pedidos e seus itens

### ListaEmailsResource
- **Pasta**: `app/Filament/Resources/ListaEmailsResource/`
- Listas de e-mail para comunicações financeiras

### ImportacaoNotaFiscalResource
- **Pasta**: `app/Filament/Resources/ImportacaoNotaFiscals/`
- Importação de notas fiscais pela construtora (grupo Construtora)
- Bloqueia edição de notas já decididas (aprovadas/reprovadas)

## Pages

### DashboardPedidos
- **Arquivo**: `app/Filament/Pages/DashboardPedidos.php`
- Visão geral dos pedidos e status financeiro

### AprovacaoNotasFiscaisPage
- **Arquivo**: `app/Filament/Pages/AprovacaoNotasFiscaisPage.php`
- **Grupo**: Expansão > Engenharia
- Fila paginada de notas fiscais pendentes de aprovação/reprovação
- Histórico de decisões com filtros por obra, status e período
- Acesso restrito a gestores via `HasPageShield`
- Fluxo: Construtora importa NF → status `em_analise` → Gestor aprova/reprova → `decidido_por_id` e `decidido_em` registrados

## Imports & Exports

### Import
- `ControlePedidosBaseImport` — importação de base de pedidos via planilha

### Export
- `ElaboracaoAditivoPlanilhaExport` — export de aditivos para planilha

## Observer: ControlePedidoObserver
- **Arquivo**: `app/Observers/ControlePedidoObserver.php`
- Side-effects em mudanças de pedidos (notificações, atualizações de status)
