# Arquitetura do Sistema

## Visão Geral

```
┌─────────────────────────────────────────────────────┐
│                    Navegador                         │
│           (Filament / Livewire / Blade)              │
└────────────────────┬────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────┐
│              Laravel Application                     │
│                                                     │
│  ┌────────────┐  ┌──────────────┐  ┌─────────────┐ │
│  │  Filament  │  │  Controllers │  │   Webhooks  │ │
│  │  Resources │  │  (slim)      │  │  (WhatsApp) │ │
│  └─────┬──────┘  └──────┬───────┘  └──────┬──────┘ │
│        │                │                  │        │
│  ┌─────▼──────────────────────────────────▼──────┐  │
│  │              Services / Support               │  │
│  │  VisitaTecnicaPdfService, WhatsAppService,    │  │
│  │  PendenciaService, AsaService, etc.           │  │
│  └─────────────────────┬─────────────────────────┘  │
│                        │                            │
│  ┌─────────────────────▼─────────────────────────┐  │
│  │              Models (Eloquent)                │  │
│  │  Projeto, Obras, Pendencia, User, etc.        │  │
│  └─────────────────────┬─────────────────────────┘  │
│                        │                            │
│  ┌─────────────────────▼─────────────────────────┐  │
│  │            Banco de Dados (MariaDB)           │  │
│  └───────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘
```

## Camadas da Aplicação

### 1. Filament Resources (camada principal)
A maior parte da lógica de negócio vive nos Filament Resources. Não há controllers intermediários para operações CRUD — tudo é gerenciado pelo Filament diretamente.

- 34+ Resources com Pages (List, Create, Edit, View)
- RelationManagers para relacionamentos
- Schemas customizados para formulários complexos

### 2. Services
Lógica de negócio reutilizável e operações complexas:
- Geração de PDFs
- Integração com WhatsApp
- Processamento de planilhas
- Integração com APS/Autodesk

### 3. Models
Eloquent models com:
- Relacionamentos declarados
- Observers para side-effects
- Casts para Enums
- SoftDeletes onde necessário

### 4. Events & Listeners (módulo Pós Obra)
Arquitetura event-driven para o módulo de Pós Obra:
- 7 eventos de domínio
- Listeners disparam notificações automáticas

### 5. Jobs (fila assíncrona)
Operações pesadas em background:
- Geração de PDF
- Import de obras

## Fluxo de autorização

```
Request → Middleware → Gate/Policy → Resource/Controller → Model
```

- **Spatie Permission**: roles e permissions no banco
- **Filament Shield**: gera policies automaticamente por CRUD
- **Gate policies**: controle granular por modelo
- **Filtro por setor**: usuários veem apenas dados dos seus setores

## Módulos principais

| Módulo | Localização | Descrição |
|--------|------------|-----------|
| Expansão | `Resources/ProjetoResource` | Ciclo completo de projetos |
| Pós Obra | `Resources/PosObra/` | Pendências + WhatsApp |
| Comercial | `Resources/PipeResource` | Pipeline de vendas |
| CAPEX | `Resources/CapexSimulacaosResource` | Simulações de orçamento |
| ASA | `Resources/AsasResource` | Assessments |
| BIM/3D | `Pages/Viewer3D` + APS | Visualização 3D |
| Mapas | `Pages/Mapa*` | Visualização geográfica |

## Padrões arquiteturais

- Controllers **slim** — apenas para rotas especiais (PDF download, webhooks)
- Lógica de negócio em **Resources** e **Services**
- Resources desativados têm prefixo `.` no nome do arquivo
- Sem Repository pattern — Eloquent direto com Services
