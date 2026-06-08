# Banco de Dados

Documentação completa de tabelas e colunas na pasta `tabelas/`. Cada arquivo cobre um domínio com todas as tabelas, colunas, tipos, constraints e usos.

---

## Índice de tabelas por domínio

| Arquivo | Tabelas |
|---------|---------|
| [Usuários e Autenticação](tabelas/usuarios-e-autenticacao) | `users`, `password_reset_tokens`, `sessions`, `notifications` |
| [Localização](tabelas/localizacao) | `pais`, `estados`, `cidades`, `regiao_interesses` |
| [Empresas e Organizações](tabelas/empresas-e-organizacoes) | `empresas`, `construtoras`, `setores`, `setor_user`, `departamentos`, `marcas`, `pipes` |
| [Projetos](tabelas/projetos) | `projetos`, `etapas`, `etapa_projeto`, `projeto_user`, `projeto_setor`, `historico_projetos` |
| [Obras](tabelas/obras) | `obras`, `obra_construtora`, `obra_user`, `obra_documentos`, `obra_recebimentos`, `atualizacoes_obra`, `midias` |
| [Relatórios](tabelas/relatorios) | `relatorio_visita_tecnicas`, `relatorio_fotograficos` |
| [Pipeline Comercial](tabelas/pipeline-comercial) | `prospeccoes`, `acompanhamentos`, `reuniaos`, `reuniao_projeto`, `reuniao_comites`, `aprovacao_reuniao_comite`, `aprovacao_viabilidades` |
| [Tarefas](tabelas/tarefas) | `task_categories`, `tasks`, `task_user` |
| [Financeiro](tabelas/financeiro) | `controle_pedidos`, `controle_pedido_itens`, `gestao_obras`, `nota_fiscals`, `tipo_faturamentos`, `nota_fiscal_tipo_faturamento`, `faturamentos`, `ordem_investimentos`, `lista_emails`, `controle_nota_fiscal_notas` |
| [CAPEX e ASA](tabelas/capex-e-asa) | `capex_disciplinas`, `capex_simulacoes`, `capex_simulacao_itens`, `as_escopos`, `as_faixa_areas`, `as_escopo_faixa_area`, `asas`, `asa_items`, `elaboracao_aditivos`, `elaboracao_aditivo_items` |
| [Pós Obra](tabelas/pos-obra) | `po_disciplinas_config`, `construtora_disciplina`, `po_configuracoes_sla`, `po_pendencias`, `po_anexos_pendencias`, `po_atualizacoes_status`, `po_aprovacoes_finalizacao`, `po_mensagens_whatsapp`, `po_conversas_whatsapp`, `po_whatsapp_config`, `po_whatsapp_bot_mensagens` |
| [Outros Módulos](tabelas/outros-modulos) | `matterports`, `dados`, `ambientes`, `planejamento_estrategicos`, `importacao_templates`, `importacao_logs` |
| [Sistema e Infraestrutura](tabelas/sistema-e-infraestrutura) | `jobs`, `job_batches`, `failed_jobs`, `cache`, `cache_locks`, `permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions` |

---

## Estatísticas

| Item | Quantidade |
|------|-----------|
| Tabelas totais | 89+ |
| Colunas totais | 1.500+ |
| Chaves estrangeiras | 200+ |
| Tabelas com SoftDeletes | 3 (`projetos`, `relatorio_visita_tecnicas`, `relatorio_fotograficos`) |
| Colunas JSON | 50+ |
| Tabelas pivot (M:N) | 12+ |

---

## Convenção de nomes de tabelas

| Domínio | Prefixo | Exemplo |
|---------|---------|---------|
| Pós Obra | `po_` | `po_pendencias`, `po_mensagens_whatsapp` |
| Laravel (infra) | — | `jobs`, `cache`, `notifications` |
| Spatie (permissões) | — | `permissions`, `roles`, `model_has_roles` |
| Demais | plural | `projetos`, `obras`, `users` |

## Tabelas pivot (BelongsToMany)

| Tabela | Relacionamento |
|--------|--------------|
| `projeto_user` | Projeto ↔ User |
| `setor_user` | Setor ↔ User |
| `obra_user` | Obra ↔ User |
| `obra_construtora` | Obra ↔ Construtora |
| `etapa_projeto` | Etapa ↔ Projeto |
| `projeto_setor` | Projeto ↔ Setor |
| `task_user` | Task ↔ User |
| `reuniao_projeto` | Reuniao ↔ Projeto |
| `as_escopo_faixa_area` | AsEscopo ↔ AsFaixaArea |
| `construtora_disciplina` | Construtora ↔ DisciplinaConfig |
| `nota_fiscal_tipo_faturamento` | NotaFiscal ↔ TipoFaturamento |

## Models com SoftDeletes

| Model | Tabela |
|-------|--------|
| `Projeto` | `projetos` |
| `RelatorioVisitaTecnica` | `relatorio_visita_tecnicas` |
| `RelatorioFotografico` | `relatorio_fotograficos` |

## Seeders

```bash
php artisan db:seed                                              # Tudo
php artisan db:seed --class=DepartamentosSeeder
php artisan db:seed --class=AmbientesSeeder
php artisan db:seed --class=CapexEstruturaSeeder
php artisan db:seed --class=AsEscopoSeeder
php artisan db:seed --class=PosObraSeeder
php artisan db:seed --class=PosObraPermissionsSeeder
php artisan db:seed --class=AtualizacaoObraPermissionSeeder
```

## Comandos úteis

```bash
php artisan migrate                  # Rodar migrations
php artisan migrate:fresh --seed     # Recriar banco do zero + seeds
php artisan migrate:status           # Ver status das migrations
php artisan migrate:rollback         # Rollback da última migration
```
