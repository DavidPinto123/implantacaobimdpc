# Filament — Resources

Todos os 34+ Filament Resources da aplicação.

## Grupo: Expansão

| Resource | Arquivo | Descrição |
|---------|---------|-----------|
| `ProjetoResource` | `Resources/ProjetoResource.php` | Ciclo completo de projetos (~329KB) |
| `ObrasResource` | `Resources/Obras/ObrasResource.php` | Gestão de obras/construções |
| `RelatorioVisitaTecnicaResource` | `Resources/RelatorioVisitaTecnicaResource.php` | Relatórios de visita (~220KB) |
| `RelatorioFotograficosResource` | `Resources/RelatorioFotograficosResource/` | Relatórios fotográficos |
| `EtapaResource` | `Resources/EtapaResource.php` | Etapas do workflow |
| `MatterportResource` | `Resources/MatterportResource.php` | Tours 3D |
| `ReuniaoResource` | `Resources/ReuniaoResource.php` | Reuniões |
| `ControleNotaFiscalResource` | `Resources/ControleNotaFiscals/` | Controle de medição de notas fiscais por ASA/aditivo (subitem: Engenharia) |

## Grupo: Retrofit / Ampliação

| Resource | Descrição |
|---------|-----------|
| `ElaboracaoAdditivosResource` | Elaboração de aditivos contratuais |
| `ControlePedidosResource` | Controle de pedidos |

## Grupo: Orçamento

| Resource | Descrição |
|---------|-----------|
| `CapexSimulacaosResource` | Simulações CAPEX |
| `AsasResource` | Assessments (ASA) |
| `AsEscopjResource` | Escopos ASA |
| `AsFaixaAreasResource` | Faixas de área |

## Grupo: Pós Obra

| Resource | Arquivo | Descrição |
|---------|---------|-----------|
| `PendenciaResource` | `Resources/PosObra/PendenciaResource.php` | Pendências |
| `ConfiguracaoSlaResource` | `Resources/PosObra/ConfiguracaoSlaResource.php` | Configuração de SLAs |
| `DisciplinaConfigResource` | `Resources/PosObra/DisciplinaConfigResource.php` | Disciplinas |

## Grupo: Comercial / Pipeline

| Resource | Descrição |
|---------|-----------|
| `PipeResource` | Pipeline de vendas |
| `MarcaResource` | Marcas/bandeiras |
| `RegiaoInteresseResource` | Regiões de interesse |

## Grupo: Outros / Construtora

| Resource | Arquivo | Descrição |
|---------|---------|-----------|
| `ImportacaoNotaFiscalResource` | `Resources/ImportacaoNotaFiscals/` | Importação de notas fiscais pela construtora (subitem: Construtora) |

## Grupo: Gestão Predial / Tarefas

| Resource | Descrição |
|---------|-----------|
| `TasksResource` | Tarefas |
| `TaskCategoriesResource` | Categorias de tarefas |
| `ListaEmailsResource` | Listas de e-mail |

## Grupo: Cadastros

| Resource | Descrição |
|---------|-----------|
| `UserResource` | Usuários |
| `EmpresasResource` | Empresas |
| `ConstrutoraResource` | Construtoras |
| `SetorResource` | Setores |
| `DepartamentosResource` | Departamentos |
| `PaisResource` | Países |
| `EstadoResource` | Estados |
| `CidadeResource` | Cidades |
| `DadosResource` | Dados gerais |
| `AmbientesResource` | Ambientes/salas |

## Resources desativados (prefixo `.`)

| Resource | Motivo |
|---------|--------|
| `.GestaoObraResource` | Desativado |
| `.NotaFiscalResource` | Desativado |

## Estrutura padrão de um Resource

```
ExemploResource.php
ExemploResource/
├── Pages/
│   ├── ListExemplos.php
│   ├── CreateExemplo.php
│   ├── EditExemplo.php
│   └── ViewExemplo.php (opcional)
├── RelationManagers/
│   └── RelacaoRelationManager.php
├── Schemas/         (opcional)
├── Tables/          (opcional)
└── Widgets/         (opcional)
```

## RelationManagers notáveis

| Resource | RelationManager |
|---------|----------------|
| `ProjetoResource` | `ProspeccaoRelationManager` |
| `ReuniaoResource` | `ProjetosRelationManager` |
| `PendenciaResource` | `AnexosRelationManager`, `HistoricoStatusRelationManager` |
| `AsEscopjResource` | `FaixasAreaRelationManager` |
| `CapexSimulacaosResource` | RelationManager de itens |

## Grupos de navegação (AdminPanelProvider)

```
Expansão
Retrofit/Ampliação
Telão
Painel Global
Orçamento
Contratos
Construtora
Pós Obra
Mapas
Gestão Predial
Cadastros
Outros
Configurações
```
