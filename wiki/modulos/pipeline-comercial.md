# Módulo: Pipeline Comercial

Gerenciamento do funil de vendas e prospecção de novas unidades Smart Fit.

## Visão Geral

```
Prospecção → Acompanhamento → Reunião → Comitê → Projeto
```

## Models

### Prospeccao
- **Arquivo**: `app/Models/Prospeccao.php`
- **Tabela**: `prospeccoes`
- Primeiro estágio do funil: leads e oportunidades
- Relacionada ao `Projeto` via `ProspeccaoRelationManager`

### Acompanhamento
- **Arquivo**: `app/Models/Acompanhamento.php`
- **Tabela**: `acompanhamentos`
- Follow-up de oportunidades em andamento

### Reuniao
- **Arquivo**: `app/Models/Reuniao.php`
- **Tabela**: `reunioes`
- Reuniões comerciais
- `BelongsToMany: projetos` via `ReuniaoResource`
- Relacionada a `ReuniaoComite`

### ReuniaoComite
- **Arquivo**: `app/Models/ReuniaoComite.php`
- Reuniões de comitê para aprovação de viabilidade

### Pipe
- **Arquivo**: `app/Models/Pipe.php`
- **Tabela**: `pipes`
- Estágios do pipeline de vendas

### Marca
- **Arquivo**: `app/Models/Marca.php`
- **Tabela**: `marcas`
- Marcas/bandeiras (Smart Fit, Bio Ritmo, etc.)

### RegiaoInteresse
- **Arquivo**: `app/Models/RegiaoInteresse.php`
- Regiões de interesse para expansão

## Filament Resources

| Resource | Descrição |
|---------|-----------|
| `ProjetoResource` | Inclui `ProspeccaoRelationManager` |
| `PipeResource` | Visualização e gestão do pipeline |
| `ReuniaoResource` | Reuniões com `ProjetosRelationManager` |
| `MarcaResource` | Cadastro de marcas |
| `RegiaoInteresseResource` | Cadastro de regiões |
| `AcompanhamentoResource` | Follow-up |

## Pages especializadas

### VisualizarPipe
- **Arquivo**: `app/Filament/Pages/VisualizarPipe.php`
- Visualização kanban/visual do pipeline

### Pipeline
- **Arquivo**: `app/Filament/Pages/Pipeline.php`
- Visão geral do pipeline comercial

### AgendaVtComercial
- **Arquivo**: `app/Filament/Pages/AgendaVtComercial.php`
- Agenda de visitas técnicas comerciais

### LandBank
- **Arquivo**: `app/Filament/Pages/LandBank.php`
- Banco de terrenos disponíveis

### CadastrarPonto
- **Arquivo**: `app/Filament/Pages/CadastrarPonto.php` (54KB)
- Formulário complexo para cadastro de ponto/localização

## Ações especializadas

### AvancoEtapa
- **Arquivo**: `app/Filament/Tables/Actions/AvancoEtapa.php`
- Avança o projeto para a próxima etapa do funil

### ReuniaoComiteAction
- **Arquivo**: `app/Filament/Tables/Actions/ReuniaoComiteAction.php`
- Ação para agendar/registrar reunião de comitê

### ViabilidadeAction
- **Arquivo**: `app/Filament/Tables/Actions/ViabilidadeAction.php`
- Análise de viabilidade do ponto

## Mapas

### MapaGeral
- **Page**: `app/Filament/Pages/MapaGeral.php`
- Mapa geral com todos os pontos

### MapaObras
- **Page**: `app/Filament/Pages/MapaObras.php`
- Mapa focado em obras em andamento

### MapaStatus
- **Page**: `app/Filament/Pages/MapaStatus.php`
- Mapa por status dos projetos

### ProjetosMapa
- **Page**: `app/Filament/Pages/ProjetosMapa.php`
- Mapa de projetos

### Rota por estado
```
GET /projetos-por-estado/{sigla}
```
Retorna projetos filtrados por estado/UF (query complexa com múltiplos status).

## Aprovações

### AprovacaoViabilidade
- **Model**: `app/Models/AprovacaoViabilidade.php`
- Registro de aprovações de viabilidade

### AprovacaoReuniaoComite
- **Model**: `app/Models/AprovacaoReuniaoComite.php`
- Aprovações de reuniões de comitê
