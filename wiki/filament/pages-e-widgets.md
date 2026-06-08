# Filament — Pages & Widgets

## Pages (app/Filament/Pages/)

### Dashboards

| Page | Arquivo | Descrição |
|------|---------|-----------|
| `Dashboard` | `Pages/Dashboard.php` | Dashboard principal |
| `DashboardColaborador` | `Pages/DashboardColaborador.php` | Dashboard do colaborador |
| `DashboardColaOrc` | `Pages/DashboardColaOrc.php` | Dashboard de orçamento |
| `DashboardComercial` | `Pages/DashboardComercial.php` | Dashboard comercial |
| `DashboardComercialCoordenacao` | `Pages/DashboardComercialCoordenacao.php` | Coordenação comercial |
| `DashboardTarefas` | `Pages/DashboardTarefas.php` | Dashboard de tarefas |
| `DashboardPedidos` | `Pages/DashboardPedidos.php` | Dashboard de pedidos |
| `ProjetosDashboard` | `Pages/ProjetosDashboard.php` | Visão geral de projetos |

### Mapas

| Page | Descrição |
|------|-----------|
| `MapaGeral` | Mapa geral com todos os pontos |
| `MapaObras` | Mapa de obras em andamento |
| `MapaStatus` | Mapa por status dos projetos |
| `ProjetosMapa` | Mapa de projetos |

### Ferramentas de Projeto

| Page | Tamanho | Descrição |
|------|---------|-----------|
| `CadastrarPonto` | 54KB | Formulário complexo de cadastro de ponto; após criação normaliza os caminhos de mídia para `arquivos-pt/{id}/midia/` no R2 |
| `AgendaVtComercial` | — | Agenda de visitas técnicas |
| `HistoricoProjetoCustom` | — | Histórico personalizado |
| `ImportObras` | 26KB | Importação de obras via planilha |

### Visualização

| Page | Descrição |
|------|-----------|
| `Viewer3D` | Viewer 3D APS (Autodesk) |
| `VisualizarPipe` | Visualização kanban do pipeline |
| `Pipeline` | Visão geral do pipeline |
| `LandBank` | Banco de terrenos |
| `SimuladorCapex` | Simulador CAPEX interativo (14KB) |

### Pipeline Comercial

| Page | Descrição |
|------|-----------|
| `Pipeline` | Funil de vendas |
| `LandBank` | Banco de terrenos |

### Engenharia / Controle de Notas Fiscais

| Page | Arquivo | Descrição |
|------|---------|-----------|
| `AprovacaoNotasFiscaisPage` | `Pages/AprovacaoNotasFiscaisPage.php` | Fila paginada de aprovação/reprovação de notas fiscais importadas; histórico de decisões; filtros por obra, status e período; acesso restrito a gestores (grupo Expansão > Engenharia) |

### Pós Obra

| Page | Descrição |
|------|-----------|
| `WhatsAppConfigPage` | Configuração do WhatsApp |
| `FluxoBotPage` | Configuração do fluxo do bot |

### Autenticação

| Page | Descrição |
|------|-----------|
| `Auth/Login` | Página de login customizada (branding Smart Fit) |
| `Auth/EditProfile` | Edição de perfil |

### Desativadas

| Page | Descrição |
|------|-----------|
| `Matterport` (prefixo `.`) | Configuração Matterport |

---

## Widgets (app/Filament/Widgets/)

| Widget | Arquivo | Descrição |
|--------|---------|-----------|
| `ApexGraficoAcompanhamento` | `Widgets/ApexGraficoAcompanhamento.php` | Gráfico de acompanhamento (ApexCharts) |
| `ProjetosInauguradosOverview` | `Widgets/ProjetosInauguradosOverview.php` | Overview de projetos inaugurados |
| `ResumoSemanalTasks` | `Widgets/ResumoSemanalTasks.php` | Resumo semanal de tarefas |
| `RiscosDetalhadosTable` | `Widgets/RiscosDetalhadosTable.php` | Tabela detalhada de riscos |
| `RiscosDonutChart` | `Widgets/RiscosDonutChart.php` | Gráfico donut de riscos |
| `RiscosTable` | `Widgets/RiscosTable.php` | Tabela de riscos |
| `TableAcompanhamento` | `Widgets/TableAcompanhamento.php` | Tabela de acompanhamento (13KB) |

### Dashboard Widgets
- Pasta `Widgets/Dashboard/` com widgets específicos por dashboard

---

## Componentes Filament customizados

### Forms (app/Filament/Components/Forms/)
- `DownloadPdfButton` — botão customizado para download de PDF

### Tables (app/Filament/Components/Tables/)
- Ações customizadas de tabela

### Table Actions (app/Filament/Tables/Actions/)

| Action | Descrição |
|--------|-----------|
| `AvancoEtapa` | Avança projeto para próxima etapa |
| `ReuniaoComiteAction` | Agenda reunião de comitê |
| `ViabilidadeAction` | Análise de viabilidade |
| `VisitaTecnica/` | Ações de visita técnica |
