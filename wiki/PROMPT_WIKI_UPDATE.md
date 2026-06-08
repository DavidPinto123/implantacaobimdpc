# Prompt — Atualização da Wiki após PR

> Este prompt deve ser usado como rotina no Claude Code para documentar alterações após merge de PRs.

---

```
Você é responsável por manter a wiki do projeto GestãoSmart atualizada.

## Contexto

A wiki documenta o estado da branch `desenvolvimento`. Toda leitura de código-fonte deve ser feita a partir dessa branch.

Antes de iniciar, certifique-se de estar na branch `desenvolvimento` atualizada:
```bash
git checkout desenvolvimento && git pull origin desenvolvimento
```

## Tarefa

1. Identifique as PRs mergeadas recentemente em `desenvolvimento` que ainda não foram documentadas na wiki. Use `git log desenvolvimento --oneline -20` para ver os commits recentes e `gh pr list --state merged --base desenvolvimento --limit 10` para listar as PRs.

2. Para cada PR, analise os arquivos alterados na branch `desenvolvimento` (`git diff <commit-antes>..<commit-depois> --stat` e leia os arquivos relevantes) e identifique:
   - Novos models criados ou alterados (`app/Models/`)
   - Novos resources Filament criados ou alterados (`app/Filament/Resources/`)
   - Novos services ou alterações em services (`app/Services/`, `app/Support/`)
   - Novas pages ou widgets Filament (`app/Filament/Pages/`, `app/Filament/Widgets/`)
   - Novas migrations ou alterações no banco (`database/migrations/`)
   - Novos observers, events, listeners (`app/Observers/`, `app/Events/`, `app/Listeners/`)
   - Novos jobs, commands, exports, imports (`app/Jobs/`, `app/Commands/`, `app/Exports/`, `app/Imports/`)
   - Novas rotas ou controllers (`routes/`, `app/Http/Controllers/`)
   - Novas policies (`app/Policies/`)
   - Novos enums (`app/Enums/`)
   - Novas integrações externas

3. Atualize as páginas correspondentes na wiki (`wiki/`), seguindo rigorosamente os padrões abaixo.

## Estrutura da wiki

```
wiki/
├── README.md              ← Índice geral (atualizar se novo módulo)
├── _Sidebar.md            ← Navegação lateral (atualizar se nova página)
├── visao-geral/           ← Stack, arquitetura, ambiente, guia
├── modulos/               ← Um arquivo por módulo de negócio
├── filament/              ← resources.md e pages-e-widgets.md
├── backend/               ← models.md, services.md, rotas, eventos, jobs, banco, auth
└── tabelas/               ← Um arquivo por domínio de tabelas
```

## Padrões de documentação

### Formato geral
- Escrita em **português**
- Markdown padrão (sem wikilinks `[[]]`)
- Links entre páginas: `[Texto](pasta/arquivo)` — sem `.md`, sem `../`
- Referências cruzadas: `(ver [Models](backend/models))`
- Backticks para código: `NomeModel`, `nome_tabela`, `app/Models/Arquivo.php`
- Tabelas pipe-delimited para listas estruturadas
- Headings: H1 (título), H2 (seções), H3 (subseções)

### Models (wiki/backend/models.md)
Adicionar na tabela do domínio correspondente:
```markdown
| `NomeModel` | `app/Models/NomeModel.php` | `nome_tabela` | Observações |
```
Se o model não pertence a nenhum domínio existente, criar novo H2:
```markdown
## Domínio: Nome do Domínio

| Model | Arquivo | Tabela | Observações |
|-------|---------|--------|-------------|
| `NomeModel` | `app/Models/NomeModel.php` | `nome_tabela` | Traits, Observer, etc. |
```

### Resources Filament (wiki/filament/resources.md)
Adicionar na tabela do grupo correspondente:
```markdown
| `NomeResource` | `app/Filament/Resources/NomeResource.php` | Descrição curta |
```
Se for um grupo novo, criar H2:
```markdown
## Grupo: Nome do Grupo

| Resource | Arquivo | Descrição |
|----------|---------|-----------|
```

### Pages e Widgets (wiki/filament/pages-e-widgets.md)
Seguir o mesmo padrão — tabela com Page/Widget, Arquivo, Descrição.

### Services (wiki/backend/services.md)
Adicionar na seção correspondente ou criar nova:
```markdown
| `NomeService` | `app/Services/NomeService.php` | Responsabilidade |
```

### Módulos (wiki/modulos/*.md)
Se um módulo recebeu funcionalidade significativa (nova page, novo fluxo, nova integração), atualizar o arquivo do módulo. Manter o padrão:
- Metadados em lista com bold: `- **Arquivo**: ...`, `- **Tabela**: ...`
- Relacionamentos em tabela: Relacionamento | Tipo | Destino
- Fluxos em ASCII: `Etapa A → Etapa B → Etapa C`

### Banco de Dados (wiki/backend/banco-de-dados.md)
Se novas tabelas foram criadas, atualizar o índice e o arquivo de tabelas correspondente em `wiki/tabelas/`.

### Tabelas (wiki/tabelas/*.md)
Documentar novas tabelas com colunas, tipos, constraints. Seguir padrão dos arquivos existentes.

### Eventos, Observers, Jobs (wiki/backend/eventos-listeners-e-observers.md, wiki/backend/jobs-e-filas.md)
Adicionar na tabela existente ou criar nova seção.

### Autorização (wiki/backend/autorizacao-e-permissoes.md)
Novas policies devem ser listadas na tabela.

## Regras

- **Não criar páginas novas** sem necessidade — preferir atualizar as existentes
- Se criar página nova, atualizar `wiki/README.md` e `wiki/_Sidebar.md`
- **Não alterar** a estrutura de diretórios do wiki — manter as subpastas existentes
- **Não incluir** informações do CLAUDE.md, .env, ou configurações locais
- **Não documentar** migrations individualmente — documentar o resultado (tabelas/colunas)
- Ser **conciso** — tabelas e listas, não parágrafos longos
- Manter **consistência** com o estilo das páginas existentes

## Commit

Após atualizar a wiki, criar um commit separado:
```
docs(wiki): atualizar documentação após PR #<número>

Documenta: <lista curta do que foi documentado>
```

Criar branch `docs/DDMMAAAA-wiki-update` e abrir PR em `desenvolvimento`.

### PR

- **Título**: `docs(wiki): atualizar documentação após PR #<número>` (em português)
- **Descrição**: em português, com seções:
  - `## Resumo` — o que foi documentado (bullet points)
  - `## PRs documentadas` — lista das PRs que foram cobertas
- Seguir o padrão do projeto: `tipo(escopo): descrição curta em português`
```

---

> **Uso**: cole este prompt no Claude Code ou configure como skill/hook para execução após merges.
