# Models — Visão Geral

Todos os Eloquent models da aplicação, organizados por domínio.

## Domínio: Projetos & Obras

| Model | Arquivo | Tabela | Observações |
|-------|---------|--------|-------------|
| `Projeto` | `app/Models/Projeto.php` | `projetos` | SoftDeletes, 100+ campos |
| `Obras` | `app/Models/Obras.php` | `obras` | Observer: ObrasObserver |
| `Midia` | `app/Models/Midia.php` | `midias` | Polimórfica (MorphTo: mediavel), scopes: imagens, categoria |
| `Etapa` | `app/Models/Etapa.php` | `etapas` | BelongsToMany: projetos |
| `HistoricoProjeto` | `app/Models/HistoricoProjeto.php` | `historico_projetos` | Audit trail |
| `AtualizacaoObra` | `app/Models/AtualizacaoObra.php` | — | Observer: ObrasObserver |
| `ObraDocumento` | `app/Models/ObraDocumento.php` | — | Documentos vinculados a obras |
| `ObraRecebimento` | `app/Models/ObraRecebimento.php` | — | Recebimentos por obra |

## Domínio: Relatórios

| Model | Arquivo | Observações |
|-------|---------|-------------|
| `RelatorioVisitaTecnica` | `app/Models/RelatorioVisitaTecnica.php` | SoftDeletes, Observer |
| `RelatorioFotografico` | `app/Models/RelatorioFotografico.php` | HasMany: autor, gestor |

## Domínio: Pipeline Comercial

| Model | Arquivo | Tabela |
|-------|---------|--------|
| `Prospeccao` | `app/Models/Prospeccao.php` | `prospeccoes` |
| `Acompanhamento` | `app/Models/Acompanhamento.php` | `acompanhamentos` |
| `Reuniao` | `app/Models/Reuniao.php` | `reunioes` |
| `ReuniaoComite` | `app/Models/ReuniaoComite.php` | — |
| `Pipe` | `app/Models/Pipe.php` | `pipes` |
| `AprovacaoViabilidade` | `app/Models/AprovacaoViabilidade.php` | — |
| `AprovacaoReuniaoComite` | `app/Models/AprovacaoReuniaoComite.php` | — |

## Domínio: Usuários & Organizações

| Model | Arquivo | Observações |
|-------|---------|-------------|
| `User` | `app/Models/User.php` | HasRoles (Spatie), FilamentUser |
| `Empresa` / `Empresas` | `app/Models/Empresa/Empresas.php` | — |
| `Construtora` | `app/Models/Construtora.php` | — |
| `Departamentos` | `app/Models/Departamentos.php` | — |
| `Setor` | `app/Models/Setor.php` | BelongsToMany: users |
| `Marca` | `app/Models/Marca.php` | — |

## Domínio: Localização

| Model | Arquivo | Tabela |
|-------|---------|--------|
| `Pais` | `app/Models/Pais.php` | `pais` |
| `Estado` | `app/Models/Estado.php` | `estados` |
| `Cidade` | `app/Models/Cidade.php` | `cidades` |
| `RegiaoInteresse` | `app/Models/RegiaoInteresse.php` | — |

## Domínio: Financeiro

| Model | Arquivo | Observações |
|-------|---------|-------------|
| `ControlePedido` | `app/Models/ControlePedido.php` | Observer: ControlePedidoObserver |
| `ControlePedidoItem` | `app/Models/ControlePedidoItem.php` | — |
| `ControleNotaFiscal` | `app/Models/ControleNotaFiscal.php` | — | Controle de medição por ASA/aditivo |
| `ControleNotaFiscalItem` | `app/Models/ControleNotaFiscalItem.php` | — | Itens da medição; campo `numero_complemento` (nullable) |
| `ControleNotaFiscalNota` | `app/Models/ControleNotaFiscalNota.php` | `controle_nota_fiscal_notas` | Notas fiscais importadas; `decidido_por` (BelongsTo User); helpers `getStatusOptions/Label/Color()`; constantes `STATUS_PENDENTE/EM_ANALISE/APROVADO/REPROVADO` |
| `ControleNotaFiscalAuxiliar` | `app/Models/ControleNotaFiscalAuxiliar.php` | — | Dados auxiliares de medição |
| `Faturamento` | `app/Models/Faturamento.php` | — |
| `TipoFaturamento` | `app/Models/TipoFaturamento.php` | — |
| `NotaFiscal` | `app/Models/NotaFiscal.php` | Resource desativado |
| `OrdemInvestimento` | `app/Models/OrdemInvestimento.php` | — |
| `ListaEmail` | `app/Models/ListaEmail.php` | — |

## Domínio: CAPEX

| Model | Arquivo | Observações |
|-------|---------|-------------|
| `CapexSimulacao` | `app/Models/CapexSimulacao.php` | Métodos: `ordenarItensPorCustoEstimado()`, `recalcularItensAutomaticosETotais()`, `importarEscoposAutomaticos()`; relação `shellItem()` — `hasOne(CapexSimulacaoItem)` filtrando pelo escopo `SHELL (OBRA CIVIL)` |
| `CapexSimulacaoItem` | `app/Models/CapexSimulacaoItem.php` | Campo `ordem` atualizado automaticamente |
| `CapexDisciplina` | `app/Models/CapexDisciplina.php` | — |

## Domínio: ASA & Aditivos

| Model | Arquivo | Tabela | Observações |
|-------|---------|--------|-------------|
| `Asa` | `app/Models/Asa.php` | `asas` | — |
| `AsaItem` | `app/Models/AsaItem.php` | `asa_items` | — |
| `AsEscopo` | `app/Models/AsEscopo.php` | `as_escopos` | — |
| `AsFaixaArea` | `app/Models/AsFaixaArea.php` | `as_faixa_areas` | — |
| `ElaboracaoAditivo` | `app/Models/ElaboracaoAditivo.php` | `elaboracao_aditivos` | — |
| `ElaboracaoAditivoItem` | `app/Models/ElaboracaoAditivoItem.php` | `elaboracao_aditivo_items` | — |
| `AutorizacaoServico` | `app/Models/AutorizacaoServico.php` | `autorizacao_servicos` | Campo `numero_complemento` (nullable); unique composta `(obra_id, numero_as_hash, numero_complemento)`; `numero_as_hash` gerado automaticamente via `booted()` |

## Domínio: Tarefas

| Model | Arquivo |
|-------|---------|
| `Task` | `app/Models/Task.php` |
| `TaskCategory` | `app/Models/TaskCategory.php` |

## Domínio: Pós Obra

| Model | Arquivo | Observações |
|-------|---------|-------------|
| `Pendencia` | `app/Models/PosObra/Pendencia.php` | Observer, Enums |
| `AnexoPendencia` | `app/Models/PosObra/AnexoPendencia.php` | — |
| `AtualizacaoStatus` | `app/Models/PosObra/AtualizacaoStatus.php` | — |
| `ConfiguracaoSla` | `app/Models/PosObra/ConfiguracaoSla.php` | — |
| `DisciplinaConfig` | `app/Models/PosObra/DisciplinaConfig.php` | — |
| `ConversaWhatsapp` | `app/Models/PosObra/ConversaWhatsapp.php` | — |
| `MensagemWhatsapp` | `app/Models/PosObra/MensagemWhatsapp.php` | — |
| `WhatsappBotMensagem` | `app/Models/PosObra/WhatsappBotMensagem.php` | — |
| `WhatsappConfig` | `app/Models/PosObra/WhatsappConfig.php` | — |
| `AprovacaoFinalizacao` | `app/Models/PosObra/AprovacaoFinalizacao.php` | — |

## Domínio: Outros

| Model | Arquivo |
|-------|---------|
| `Dados` | `app/Models/Dados.php` |
| `Ambientes` | `app/Models/Ambientes.php` |
| `Matterport` | `app/Models/Matterport.php` |
| `GestaoObra` | `app/Models/GestaoObra.php` |
| `PlanejamentoEstrategico` | `app/Models/PlanejamentoEstrategico.php` |
| `RiscoResumo` | `app/Models/RiscoResumo.php` |
| `ImportacaoLog` | `app/Models/ImportacaoLog.php` |
| `ImportacaoTemplate` | `app/Models/ImportacaoTemplate.php` |
| `ColunaPersonalizada` | `app/Models/ColunaPersonalizada.php` |

## Model: User (detalhado)

O model central de autenticação e autorização.

### Traits & Interfaces
- `HasRoles` (Spatie Permission)
- `FilamentUser`
- `HasAvatar`

### Relacionamentos principais

| Relacionamento | Tipo | Destino |
|--------------|------|---------|
| projetos | BelongsToMany | `Projeto` (via `projeto_user`) |
| setores | BelongsToMany | `Setor` (via `setor_user`) |
| construtora | BelongsTo | `Construtora` |
| pais | BelongsTo | `Pais` |
| estado | BelongsTo | `Estado` |
| cidade | BelongsTo | `Cidade` |
| relatoriosCriados | HasMany | `RelatorioVisitaTecnica` |
| relatoriosComoGestor | HasMany | `RelatorioVisitaTecnica` |
| pendenciasComoGestor | HasMany | `Pendencia` |
| pendenciasComoLider | HasMany | `Pendencia` |
| obrasComoLider | HasMany | `Obras` |
| tarefasTemporarias | HasMany | — |
| tarefasComoResponsavelPrincipal | HasMany | — |
