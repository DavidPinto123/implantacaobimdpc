# Módulo: CAPEX

Simulação e controle de investimentos (Capital Expenditure) para novos projetos.

## Models

### CapexSimulacao
- **Arquivo**: `app/Models/CapexSimulacao.php`
- Cabeçalho de uma simulação de CAPEX por projeto
- Método `ordenarItensPorCustoEstimado()`: reordena itens por tipo (auto → manual) e custo estimado decrescente, atualizando o campo `ordem` de cada item
- Relação `shellItem()`: `hasOne(CapexSimulacaoItem)` filtrando por `nome_escopo = 'SHELL (OBRA CIVIL)'`; usada para exibir custo e percentual do shell na tabela

### CapexSimulacaoItem
- **Arquivo**: `app/Models/CapexSimulacaoItem.php`
- Itens individuais da simulação (por disciplina/categoria)

### CapexDisciplina
- **Arquivo**: `app/Models/CapexDisciplina.php`
- Disciplinas de custo (civil, elétrica, HVAC, etc.)

## Filament Resources

### CapexSimulacaosResource
- **Pasta**: `app/Filament/Resources/CapexSimulacaos/`
- CRUD de simulações
- RelationManager para itens da simulação
- **Após criar** (`CreateCapexSimulacao`): executa `importarEscoposAutomaticos()` + `ordenarItensPorCustoEstimado()`
- **Após salvar** (`EditCapexSimulacao`): executa `ordenarItensPorCustoEstimado()` e dispara evento `capex-itens-recarregados` para atualizar o RelationManager
- **Formulário**: campos `nome`, `sigla`, `endereco` e `area_unidade` ficam somente leitura enquanto `projeto_id` estiver preenchido; ao vincular projeto, preenche automaticamente esses campos com os dados do projeto; ao editar, o preenchimento automático (`afterStateHydrated`) só ocorre se os campos estiverem em branco; campo `nome` tem validação `unique(capex_simulacoes, nome)` com escopo `projeto_id IS NULL` (evita duplicatas em simulações sem projeto)
- **Tabela**: exibe colunas `Shell - Custo` e `Shell - %` derivadas da relação `shellItem` (escopo SHELL OBRA CIVIL); coluna `Atualizado em` (`updated_at`); ordenação padrão por `updated_at desc`

## Pages

### SimuladorCapex
- **Arquivo**: `app/Filament/Pages/SimuladorCapex.php` (14KB)
- Página interativa para simulação de CAPEX
- Cálculos em tempo real

## Services

### CapexSimulacaoPdfService
- **Arquivo**: `app/Services/CapexSimulacaoPdfService.php`
- Gera PDF da simulação CAPEX usando DomPDF
- View: `resources/views/invoices/pdfCapexSimulacao.blade.php`
- Carrega relações `itens`, `projeto` e `faixaArea` antes de renderizar
- Disponibiliza botão de exportação PDF na página `EditCapexSimulacao`
- Fluxo: `EditCapexSimulacao` → `CapexSimulacaoPdfService::makePdf()` → download do arquivo

## Seeder

### CapexEstruturaSeeder
- Popula disciplinas e estrutura base do CAPEX
