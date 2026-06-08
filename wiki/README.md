# GestãoSmart — Wiki

Plataforma SaaS em Laravel + Filament para gestão de expansão de unidades Smart Fit no Brasil.

> **Branch de referência:** `desenvolvimento` — esta documentação reflete o estado atual da branch de desenvolvimento, podendo conter funcionalidades ainda não disponíveis em produção.

---

## Navegação

### 01 - Visão Geral
- [Stack Tecnológica](visao-geral/stack-tecnologica)
- [Arquitetura do Sistema](visao-geral/arquitetura-do-sistema)
- [Ambiente de Desenvolvimento](visao-geral/ambiente-de-desenvolvimento)
- [Guia de Desenvolvimento](visao-geral/guia-de-desenvolvimento)

### 02 - Módulos
- [Expansão & Projetos](modulos/expansao-e-projetos)
- [Pós Obra](modulos/pos-obra)
- [Pipeline Comercial](modulos/pipeline-comercial)
- [Financeiro](modulos/financeiro)
- [CAPEX](modulos/capex)
- [ASA](modulos/asa)
- [Integrações](modulos/integracoes)

### 03 - Filament
- [Filament — Resources](filament/resources)
- [Filament — Pages & Widgets](filament/pages-e-widgets)

### 04 - Backend
- [Models — Visão Geral](backend/models)
- [Rotas & Controllers](backend/rotas-e-controllers)
- [Services](backend/services)
- [Eventos, Listeners & Observers](backend/eventos-listeners-e-observers)
- [Jobs & Filas](backend/jobs-e-filas)
- [Banco de Dados](backend/banco-de-dados)
- [Autorização & Permissões](backend/autorizacao-e-permissoes)

### 05 - Segurança
- [Auditoria 2026-04-19](seguranca/auditoria-2026-04-19)

> Para documentação formal (requisitos, arquitetura, testes, API), consulte a pasta `docs/` no repositório principal.

---

## Resumo Executivo

| Item | Valor |
|------|-------|
| Framework | Laravel 11.31 |
| PHP | ^8.2 |
| Painel Admin | Filament v4 |
| Banco (dev) | MariaDB 11.8 (via DDEV) |
| Banco (prod) | MySQL/MariaDB |
| Storage | Cloudflare R2 (S3-compatible) |
| E-mail | Resend |
| CSS | Tailwind CSS v4 |
| Build | Vite 6 |

## Integrações externas

- **Autodesk APS** — BIM/3D models
- **Matterport** — Tours 3D
- **WhatsApp Cloud API** — Comunicação Pós Obra
- **Cloudflare R2** — Armazenamento de arquivos
- **Resend** — Envio de e-mails

## URL local (DDEV)

http://gestaosmart.ddev.site
