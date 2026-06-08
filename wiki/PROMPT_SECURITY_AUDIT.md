# Prompt — Análise de Segurança

> Este prompt deve ser usado como rotina no Claude Code para análise de segurança do código após PRs ou periodicamente.

---

```
Você é responsável por realizar uma análise de segurança no projeto GestãoSmart (Laravel 11 + Filament 4).

## Contexto

O projeto é uma aplicação web interna com painel Filament. A análise deve ser feita a partir da branch `desenvolvimento`.

Antes de iniciar:
```bash
git checkout desenvolvimento && git pull origin desenvolvimento
```

## Escopo da análise

Analise as PRs mergeadas recentemente em `desenvolvimento` que ainda não foram auditadas.
Use `gh pr list --state merged --base desenvolvimento --limit 10` para listar.

Para cada PR, leia os arquivos alterados e avalie os pontos abaixo.

## Checklist de segurança

### 1. Injeção SQL
- [ ] Verificar uso de `DB::raw()`, `DB::select()`, `DB::statement()`, `whereRaw()`, `selectRaw()`
- [ ] Confirmar que variáveis de usuário são passadas via bindings (`?` ou `:param`), nunca concatenadas
- [ ] Exemplo inseguro: `DB::raw("WHERE id = $id")` → seguro: `DB::raw("WHERE id = ?", [$id])`

### 2. XSS (Cross-Site Scripting)
- [ ] Verificar se views Blade usam `{{ }}` (escaped) e não `{!! !!}` (raw)
- [ ] Se `{!! !!}` é usado, confirmar que o conteúdo é sanitizado antes
- [ ] Verificar se inputs de usuário são exibidos sem escape em JavaScript ou atributos HTML
- [ ] Atenção especial a campos rich text, descrições, comentários

### 3. Autorização e controle de acesso
- [ ] Novos resources Filament têm Policy correspondente?
- [ ] Policies verificam não só permissão por role, mas escopo por setor quando aplicável?
- [ ] Novas rotas web têm middleware `auth` e verificação de permissão?
- [ ] Queries filtram dados por setor/permissão do usuário logado?
- [ ] Atenção a IDOR: endpoints que recebem IDs verificam se o usuário tem acesso ao registro?

### 4. Upload de arquivos
- [ ] Uploads validam tipo MIME e extensão (`acceptedFileTypes`, `image()`, etc.)
- [ ] Tamanho máximo está definido (`maxSize()`)
- [ ] Arquivos são armazenados em disco não-público ou com acesso controlado (R2/S3)
- [ ] Nomes de arquivo são sanitizados (sem path traversal `../../`)

### 5. Autenticação
- [ ] Endpoints sensíveis exigem re-autenticação?
- [ ] Rate limiting em formulários de login, reset de senha, verificação de email?
- [ ] Tokens/sessões têm expiração adequada?

### 6. CSRF
- [ ] Rotas POST/PUT/DELETE têm proteção CSRF (middleware `VerifyCsrfToken`)?
- [ ] Se CSRF está desabilitado para alguma rota (ex: webhooks), há validação alternativa (assinatura, token, IP whitelist)?

### 7. Exposição de dados sensíveis
- [ ] Respostas de API/views não expõem dados além do necessário?
- [ ] Logs não registram senhas, tokens, dados pessoais sensíveis?
- [ ] Campos sensíveis usam `$hidden` no model?
- [ ] Variáveis de ambiente não estão hardcoded no código?

### 8. Dependências
- [ ] Novas dependências (composer/npm) são de fontes confiáveis?
- [ ] Verificar `composer audit` e `npm audit` para vulnerabilidades conhecidas
- [ ] Versões estão fixadas adequadamente?

### 9. Mass Assignment
- [ ] Models novos definem `$fillable` ou `$guarded`?
- [ ] Campos sensíveis (role, is_admin, password) não estão em `$fillable`?

### 10. Comunicação externa
- [ ] Requisições HTTP externas usam HTTPS?
- [ ] Credenciais de APIs externas vêm do `.env`, não hardcoded?
- [ ] Webhooks recebidos validam assinatura/autenticidade?
- [ ] Timeouts definidos para requisições externas (evitar SSRF/DoS)?

## Áreas de atenção específicas do projeto

### Webhooks WhatsApp (`routes/web.php`)
- CSRF desabilitado por necessidade (Meta exige)
- Validar: token de verificação, assinatura X-Hub-Signature-256
- Considerar whitelist de IPs da Meta

### Relatórios PDF
- Imagens S3/R2 convertidas para base64 — verificar que URLs são validadas
- Conteúdo do PDF não inclui HTML não-sanitizado do usuário

### Autodesk APS (`routes/web.php`)
- Rotas `/aps/*` — verificar autenticação e que tokens não são expostos ao frontend

### Queries raw em dashboards
- Widgets e páginas de dashboard usam `DB::raw` — verificar bindings
- Arquivos: `app/Filament/Pages/`, `app/Filament/Widgets/`

## Formato do relatório

Gere um relatório estruturado com:

### Resumo
- Total de arquivos analisados
- PRs auditadas
- Criticidade geral: CRÍTICO / ALTO / MÉDIO / BAIXO / SEGURO

### Vulnerabilidades encontradas

Para cada vulnerabilidade:

```markdown
#### [CRITICIDADE] Título da vulnerabilidade

- **Arquivo**: `caminho/do/arquivo.php:linha`
- **PR**: #número
- **Descrição**: O que está errado e por quê
- **Impacto**: O que um atacante poderia fazer
- **Correção sugerida**:
```php
// código corrigido
```
```

### Boas práticas confirmadas
Lista do que está correto e seguro.

### Recomendações gerais
Melhorias de segurança que não são vulnerabilidades imediatas mas fortalecem a postura de segurança.

## Issues no GitHub

Para cada vulnerabilidade encontrada (CRÍTICO, ALTO ou MÉDIO), abrir uma issue no GitHub:

```bash
gh issue create \
  --title "🔒 [CRITICIDADE] Título da vulnerabilidade" \
  --label "security,CRITICIDADE" \
  --body "$(cat <<'ISSUE'
## Vulnerabilidade de segurança

**Criticidade**: CRÍTICO / ALTO / MÉDIO
**Arquivo**: `caminho/do/arquivo.php:linha`
**PR de origem**: #número
**Detectado em**: AAAA-MM-DD

## Descrição

O que está errado e por quê.

## Impacto

O que um atacante poderia fazer.

## Correção sugerida

```php
// código corrigido
```

## Referências

- OWASP: [link relevante se aplicável]
ISSUE
)"
```

### Labels de segurança

Antes da primeira execução, criar as labels (executar uma vez):
```bash
gh label create "security" --color "d73a4a" --description "Vulnerabilidade de segurança" --force
gh label create "security:critical" --color "b60205" --description "Segurança — criticidade crítica" --force
gh label create "security:high" --color "d93f0b" --description "Segurança — criticidade alta" --force
gh label create "security:medium" --color "fbca04" --description "Segurança — criticidade média" --force
gh label create "security:low" --color "0e8a16" --description "Segurança — criticidade baixa" --force
```

### Regras para issues

- **CRÍTICO e ALTO**: sempre abrir issue
- **MÉDIO**: abrir issue se for explorável no contexto atual
- **BAIXO**: listar no relatório, não abrir issue
- Atribuir a label `security` + a label de criticidade específica (ex: `security,security:critical`)

### Verificação de duplicatas (obrigatório)

Antes de criar qualquer issue, verificar se já existe uma issue aberta para o mesmo problema:

```bash
gh issue list --label security --state open --limit 50
```

Para cada vulnerabilidade encontrada:
1. Listar as issues de segurança abertas com o comando acima
2. Comparar o arquivo e a descrição da vulnerabilidade com as issues existentes
3. Se já existir issue aberta para o mesmo arquivo e mesmo tipo de vulnerabilidade, **não criar duplicata** — apenas adicionar um comentário na issue existente se houver informação nova:
   ```bash
   gh issue comment <número> --body "Detectado novamente em auditoria de AAAA-MM-DD. PR de origem: #número"
   ```
4. Se não existir issue similar, criar uma nova

## Progresso incremental (proteção contra limite de contexto)

A análise pode ser longa. Para evitar perda de progresso se o contexto estourar:

1. **Processar uma PR por vez** — não analisar todas de uma vez
2. **Abrir issues imediatamente** ao encontrar cada vulnerabilidade, antes de continuar a análise
3. **Salvar relatório parcial** após cada PR analisada em `wiki/seguranca/auditoria-AAAA-MM-DD.md`:
   ```markdown
   # Auditoria de Segurança — AAAA-MM-DD

   ## PRs analisadas
   - [x] #123 — descrição
   - [x] #124 — descrição
   - [ ] #125 — pendente

   ## Vulnerabilidades encontradas
   (listar as já encontradas com link para a issue)

   ## Boas práticas confirmadas
   (listar)
   ```
4. **Commitar o relatório parcial** após cada PR analisada:
   ```bash
   git add wiki/seguranca/ && git commit -m "docs(wiki): auditoria de segurança parcial — PR #123"
   ```
5. Se a análise for interrompida, a próxima execução deve:
   - Ler o relatório parcial mais recente em `wiki/seguranca/`
   - Continuar a partir das PRs marcadas como pendentes (`- [ ]`)
   - Não re-analisar PRs já marcadas como concluídas (`- [x]`)

## Commit e PR

Após a auditoria, commitar o relatório e abrir PR:

```
docs(wiki): auditoria de segurança — AAAA-MM-DD

Analisa PRs: #123, #124, #125
Vulnerabilidades: X encontradas, Y issues abertas
```

Criar branch `docs/DDMMAAAA-security-audit` e abrir PR em `desenvolvimento`.

### PR

- **Título**: `docs(wiki): auditoria de segurança — AAAA-MM-DD` (em português)
- **Descrição**: em português, com seções:
  - `## Resumo` — total de PRs analisadas, vulnerabilidades encontradas por criticidade
  - `## Issues abertas` — lista com links das issues criadas
  - `## Boas práticas confirmadas` — destaques positivos
- Seguir o padrão do projeto: `tipo(escopo): descrição curta em português`

## Regras gerais

- **Não corrigir código automaticamente** — apenas reportar e abrir issues
- Focar em vulnerabilidades reais, não em falsos positivos teóricos
- Priorizar por impacto: dados de usuário > disponibilidade > estética
- Considerar o contexto: aplicação interna com autenticação obrigatória
- Ser objetivo e conciso — código, não prosa
- Relatórios ficam em `wiki/seguranca/`
- **Toda comunicação (commits, PRs, issues, relatórios) deve ser em português**
```

---

> **Uso**: execute este prompt periodicamente ou após merges significativos para manter a postura de segurança do projeto.
