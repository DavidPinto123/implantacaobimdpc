# Módulo: Expansão & Projetos

O módulo central da plataforma. Gerencia o ciclo de vida completo de uma unidade Smart Fit, desde a prospecção até a inauguração.

## Ciclo de vida de um Projeto

```
Prospecção → Assinatura → Processo → Obras → Inauguração
```

Cada etapa é controlada pelo model `Etapa` e pela relação `projeto_etapa` (ver [Models](backend/models)).

## Model: Projeto

- **Arquivo**: `app/Models/Projeto.php`
- **Tabela**: `projetos`
- **SoftDeletes**: sim
- **100+ atributos** cobrindo todo o ciclo de vida

### Relacionamentos principais

| Relacionamento | Tipo | Destino |
|--------------|------|---------|
| etapas | BelongsToMany | `Etapa` |
| usuarios | BelongsToMany | `User` (via `projeto_user`) |
| historico | HasMany | `HistoricoProjeto` |
| documentos | HasMany | — |
| obras | HasMany | `Obras` |
| prospeccao | HasOne | `Prospeccao` |
| reunioes | BelongsToMany | `Reuniao` |

## Resource Filament: ProjetoResource

- **Arquivo**: `app/Filament/Resources/ProjetoResource.php`
- **Tamanho**: ~329KB (o maior resource da aplicação)
- **RelationManagers**: `ProspeccaoRelationManager`

### Pages

| Page | Rota | Descrição |
|------|------|-----------|
| `ListProjetos` | `/admin/projetos` | Listagem com filtros |
| `CreateProjeto` | `/admin/projetos/create` | Cadastro |
| `EditProjeto` | `/admin/projetos/{id}/edit` | Edição |
| `ViewProjeto` | `/admin/projetos/{id}` | Visualização |

## Model: Obras

- **Arquivo**: `app/Models/Obras.php`
- **Tabela**: `obras`
- **Observer**: `ObrasObserver`
- Rastreia fases da construção: civil, hidráulica, elétrica, incêndio, etc.

### Campos de acompanhamento

- `% conclusão` por disciplina
- Status atual de cada fase
- Datas de início e término previstas
- Links para documentos e fotos

### Relacionamentos

| Relacionamento | Tipo | Destino |
|--------------|------|---------|
| projeto | BelongsTo | `Projeto` |
| users | BelongsToMany | `User` (via `obra_user`) |
| documentos | HasMany | `ObraDocumento` |
| recebimentos | HasMany | `ObraRecebimento` |
| atualizacoes | HasMany | `AtualizacaoObra` |

## Model: Etapa

- **Arquivo**: `app/Models/Etapa.php`
- Representa as etapas do workflow (Prospecção, Assinatura, Processo, Obras, Inauguração)
- `BelongsToMany: projetos`

## Histórico de Projetos

- **Model**: `HistoricoProjeto`
- **Tabela**: `historico_projetos`
- Audit trail de todas as alterações relevantes em um projeto

## Relatório de Visita Técnica

- **Model**: `RelatorioVisitaTecnicaResource`
- Wizard multi-etapas com formulário extenso
- Gera PDF via `VisitaTecnicaPdfService`
- Envio por e-mail via `EnviarPdfMail`
- Job assíncrono: `GenerateVisitaTecnicaPdfJob`
- **SoftDeletes**: sim
- **Observer**: `RelatorioVisitaTecnicaObserver`

## Relatório Fotográfico

- **Model**: `RelatorioFotografico`
- Galeria de fotos com fluxo autor → gestor
- PDF com imagens S3 convertidas para base64
- Service: `RelatorioFotograficoPdfService`
- Envio de PDF apenas quando status = "concluído"

## Importação de Obras

- **Job**: `ProcessObraImportJob`
- **Page Filament**: `ImportObras` (26KB)
- Import via planilha Excel usando `Maatwebsite Excel`

## Exports

| Export | Formato | Descrição |
|--------|---------|-----------|
| `ProjetoExport` | Excel | Lista de projetos |
| `ListProjetoExport` | Excel | Listagem detalhada |

## Cronograma de obra (templates)

- **Service**: `App\Services\CronogramaTemplateService` — aplicação de template híbrido (forward/backward a partir da âncora), recálculo em cascata e simulação (`simular()`).
- **Pages Filament**: `CronogramaTemplates` (edição de templates), `Cronograma` (cronograma por projeto na obra).
- **Âncora**: campo do projeto configurável no template (ex.: `projeto.data_posse` para o template oficial).

### Gatilhos de dependência

| Gatilho | Semântica | Uso típico |
|---|---|---|
| `INICIO_ANTERIOR` | `B.inicio = A.inicio + gap` | Start-to-start (paralelas) |
| `FIM_ANTERIOR` | `B.inicio = A.fim + 1 + gap` | Sequencial natural — dia seguinte |
| `FIM_ANTERIOR_MESMO_DIA` | `B.inicio = A.fim + gap` | Sobreposição no último dia |
| `FIM_JUNTO` | `B.fim = A.fim + gap` | Finish-to-finish (elástica) |
| `FIM_ANTES_INICIO` | `B.fim = A.inicio + gap` | "Termina X dias antes do início de A" — datas-limite, retroplanejamento |

### Fase elástica (`regra_elastica`)

Usada quando a duração emerge das dependências em vez de ser fixa. Combina gatilhos que **drenam o início** (`INICIO_ANTERIOR`, `FIM_ANTERIOR`, `FIM_ANTERIOR_MESMO_DIA`) com gatilhos que **drenam o fim** (`FIM_JUNTO`, `FIM_ANTES_INICIO`). Com o checkbox **"Fase elástica"** ativo, a duração em dias é ignorada no cálculo. Atraso da fase elástica é um sinal informativo (controle manual de status pelo gestor) — ela não trava o consumidor automaticamente.

### Marcos

Marcos são fases com duração 0 ou 1 dia que representam eventos pontuais. O método `FaseCronograma::marco()` retorna `true` para: `INICIO_PROJETO`, `ASSINATURA_CONTRATO`, `CODIGO_ORACLE`, `BRIEFING`, `START_PROJETOS_EXECUTIVOS`, `KICKOFF`, `PIN_SUFRAMA`, `POSSE`, `INAUGURACAO`. Usado pelo Gantt para estilo visual (ícone, sem barra de duração).

### Template oficial Smart Fit

- **Seeder**: `Database\Seeders\CronogramaTemplateSmartFitSeeder` — nome `Expansão A partir da posse Reuniao 30/04`, âncora `projeto.data_posse`, 22 fases.
- **Modelado conforme reuniões com a cliente em 30/04/2026 e 05/05/2026.**
- **Posse → Orçamentos**: âncora depende SÓ de Orçamentos (termina 1d antes da Posse).
- **Início de Obras**: depende de **POSSE+61d** (1d natural + 60d de gordura) **E** **PRAZO_LEGAL+1d** (compliance). Se Prazo Legal aumentar, Obras atrasa proporcionalmente. A gordura de 60d entre Posse e início de Obras é convenção atual da cliente (vai sendo "absorvida" conforme o cronograma se concretiza).
- **Briefing**: dep dupla **AND** com `LEVANTAMENTO_CADASTRAL` E `RECEBIMENTO_PROJETOS_ARQUITETURA` (Fase 1) — espera o último dos dois. Se um for marcado como "não se aplica", o algoritmo usa apenas o outro (max dos candidatos restantes funciona como OR efetivo).
- **Recebimento de Projetos (Fase 1 e Fase 2)**: fases elásticas — começam com Início do Projeto e terminam 1d antes do Briefing / Start Executivo via gatilho `FIM_ANTES_INICIO`. A aresta `FIM_ANTES_INICIO` é tratada como "soft" no grafo (ignorada pela detecção de ciclos e pela ordem topológica) e resolvida pelo **elastic resolve pass** ao final do cálculo.
- **Marketing / Ativação Pré-vendas**: elástica — SS com Início do Projeto, FF com Inauguração (cobre toda a campanha de pré-venda).
- **Prazo Legal**: duração default 30 dias (ajustar 60/90/120 por projeto após consulta prévia). Início = Executivo+10d **E** Assinatura+1d.
- **SUFRAMA (opcional)**: uma única fase pai `SUFRAMA` (visível=false por padrão) com três subitens — **CNPJ Suframa**, **PIN Suframa** e **Compras Suframa**. O bloco fica oculto no Gantt até o usuário marcar a fase como visível no projeto. Dependências dos subitens:
  - **CNPJ Suframa** → `ASSINATURA_CONTRATO` via `FIM_ANTERIOR gap=30` (data-limite: ~30d após assinatura).
  - **PIN Suframa** → `IMPLANTACAO` via `FIM_ANTES_INICIO gap=-45` (termina 45d antes do início da implantação).
  - **Compras Suframa** → subitem **PIN Suframa** via `FIM_ANTERIOR gap=0` (inicia 1d após PIN).

Para carregar o template no ambiente: `php artisan db:seed --class=CronogramaTemplateSmartFitSeeder`.

## Matterport

- **Model**: `Matterport`
- **Resource**: `MatterportResource`
- Integração com tours 3D do Matterport
- Gera QR Code via rota `/projetos/{projeto}/matterport-qrcode`

## 3D Viewer (APS)

- **Controller**: `Viewer3DProjetoController` (9.5KB)
- **Page**: `Viewer3D`
- Integração com Autodesk Platform Services
- Rota: `/admin/projetos/{projeto}/viewer-3d`
- Suporta arquivos `.rvt` (Revit)
