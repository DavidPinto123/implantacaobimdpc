# Auditoria de Segurança — 2026-04-19

## PRs analisadas

- [x] **#203** — `refactor: adjust filesystem r2 local` — refatoração para tornar o disco de mídia configurável (`MEDIA_DISK`), migração de uploads antes hardcoded em `disk('r2')`/`disk('public')` para `config('filesystems.media_disk', 'r2')`, reativação do bloco `temporary_file_upload` do Livewire e novos comandos artisan de migração de mídia local → R2.
- [x] **#236** — `refactor(storage): update migration scripts to track warnings instead of errors` — separação entre `warnings` (origem ausente, não fatal) e `errors` nos comandos de migração R2; refatoração de `MigrateVisitaTecnicaMediaToR2` com `copyPublicFileToR2`.

### Escopo não auditado nesta rodada

As PRs doc-only mergeadas após o último ciclo (`#217`, `#218`, `#219`)
não alteram código executável e já constam como cobertas na auditoria
`2026-04-18` (PR #234, ainda aberta). PRs de auditorias anteriores
(`#216` para 2026-04-17 e `#234` para 2026-04-18) estão fechada-não-
mergeada e aberta respectivamente, mas suas issues já existem (`#220–#233`).

## Resumo executivo

- **Total de arquivos analisados**: 65 (código + config + comandos
  artisan + views)
- **Commits cobertos**: `3e92a6e..9d4fb0a` (8 commits)
- **Vulnerabilidades novas encontradas**: 1 (MÉDIO)
- **Issues existentes afetadas pela PR #203**: 2 (#229 parcialmente
  endereçada; #233 tocada mas não corrigida)
- **Criticidade geral**: **MÉDIO** — a PR é majoritariamente uma
  refatoração segura que centraliza o disco de mídia em uma única
  configuração, mas reativa um bloco do Livewire com `max:716800` (716
  MB) sem cota por usuário e não resolve o `visibility('public')` dos
  uploads sensíveis que já estavam cobertos por issues abertas.

## Vulnerabilidades encontradas

### [MÉDIO] Livewire `temporary_file_upload` reativado com limite de 716 MB sem cota por usuário

- **Arquivo**: `config/livewire.php:66-84`
- **PR**: #203
- **Descrição**: o bloco `temporary_file_upload` estava envolvido por
  `/* ... */` e, portanto, desativado (Livewire usava o padrão de 12 MB).
  A PR removeu os comentários e deixou `max:716800` ativo. A única
  limitação é `throttle:60,1` (por IP) e o middleware
  `LivewireLargeUpload` (apenas ajusta limites do PHP/servidor).
- **Impacto**: um usuário autenticado pode enviar arquivos de até
  ~716 MB e fazer várias requisições dentro do throttle, causando DoS
  econômico no R2 (banda/armazenamento) ou no disco local quando
  `MEDIA_DISK=public`.
- **Correção sugerida**:

  ```php
  'temporary_file_upload' => [
      'disk' => env('MEDIA_DISK', 'r2'),
      'rules' => ['required', 'file', 'max:51200'], // 50 MB por upload
      'middleware' => [
          'throttle:60,1',
          LivewireLargeUpload::class,
          // Adicionar middleware de cota por usuário/sessão
      ],
      'max_upload_time' => 10,
      'cleanup' => true,
  ],
  ```

  Para vídeos realmente grandes, usar pre-signed URLs direto para o R2
  em um campo específico em vez de elevar o limite global.
- **Issue**: [#237](https://github.com/dpcconsultoria/gestaosmart/issues/237)

## Impacto da PR #203 sobre issues abertas

### #229 — ObraDocumento/ObraRecebimento em disco `public` (MÉDIO)

**Parcialmente endereçada.** `ObraDocumentoResource::getUploadDisk()` e
`ObraRecebimentoResource::getUploadDisk()` agora retornam
`config('filesystems.media_disk', 'r2')`. Contudo:

- Os schemas (`ObraDocumentoForm`, `ObraRecebimentoForm`) mantêm
  `visibility('public')`. Com `MEDIA_DISK=r2`, as URLs continuam públicas.
- `.env.example` define `MEDIA_DISK=public` — deploys novos seguindo o
  example gravam no disco local com link simbólico `/storage`.
- `config/filesystems.php:47` adiciona `'serve' => true` ao disco
  `public`, mantendo o acesso via `/storage/<path>` sem autenticação.

Comentário registrado na issue com recomendações remanescentes.

### #233 — NF e boleto com `visibility('public')` no R2 (ALTO)

**Não resolvida.** A PR #203 tocou as linhas 393-405 e 430-444 de
`ImportacaoNotaFiscalForm.php` apenas para trocar `disk('r2')` por
`disk(config('filesystems.media_disk', 'r2'))`. `visibility('public')`
permanece. A vulnerabilidade descrita em #233 segue integralmente
aplicável.

Comentário registrado na issue.

## Boas práticas confirmadas

- **Path traversal protegido por `Storage` facade**:
  `InteractsWithR2Migration::resolvePublicPath` concatena candidatos de
  caminho para localizar o arquivo no disco `public`, mas passa tudo
  pelo Storage (que normaliza paths dentro da raiz). Não há concatenação
  com entrada de usuário — o input vem de colunas do banco.
- **Nenhum `DB::raw`/`whereRaw` novo com concatenação**: comandos de
  migração (`MigrateAsaMediaToR2`, `MigrateElaboracaoAditivoMediaToR2`,
  `MigrateRelatorioFotograficoMediaToR2`, etc.) leem e gravam via
  Eloquent/Query Builder com bindings.
- **Nenhum `{!! !!}` novo em views Blade**. As views alteradas
  (`view-obra.blade.php`, `view-controle-nota-fiscal.blade.php`,
  `view-elaboracao-aditivo-custom.blade.php`,
  `pdf/relatorio-fotografico.blade.php`) mantêm `{{ }}`.
- **`ImageUploadHelper::save` preservou a sanitização**: nome do arquivo
  é gerado por `Str::random` + extensão validada + MIME preservado via
  `ContentType`. Nenhum path vem do cliente.
- **Comandos de migração R2 são CLI-only** (`artisan`), sem exposição
  HTTP. Idempotência garantida pela checagem `$disk->exists($r2Path)`
  antes de gravar.
- **PR #236 reduz ruído**: separa `warnings` (arquivo ausente na origem,
  não fatal) de `errors` reais (exit code 1), evitando que operadores
  ignorem falhas legítimas.
- **`VisitaTecnicaDownloadController` e `VisitaTecnicaPdfService`
  permaneceram funcionalmente idênticos** — apenas substituíram o disco
  hardcoded pelo configurável. Não introduziram nem removeram controles.

## Recomendações gerais

1. **Definir `MEDIA_DISK=r2` como default seguro em `.env.example`**. O
   atual `MEDIA_DISK=public` pode levar deploys novos a armazenar
   mídia no disco local e expô-la via `/storage`. Um comentário
   explicando a diferença dev/prod também ajuda.
2. **Padronizar `visibility('private')` para documentos financeiros e
   contratuais** (NF, boletos, contratos, PDFs de relatórios). Criar um
   disco `r2-private` paralelo ao `r2` (hoje com `visibility=public`)
   para separar claramente os dois casos de uso.
3. **Quotas por usuário no Livewire**: estender
   `LivewireLargeUpload::class` para contar bytes acumulados por
   usuário/hora e rejeitar uploads que excedam o limite.
4. **Auditar as issues #220–#233 no próximo ciclo** — a maioria das
   rotas públicas (`/aps/*`, `/dados`, `/projetos-por-estado/*`,
   `/relatorios-fotograficos/*`, `{record}/pdf/download`, webhook
   WhatsApp) não foi tocada por #203 e permanece em aberto.
5. **Executar `composer audit` e `npm audit`** regularmente após
   mudanças em `composer.json`/`package.json` (nenhuma dependência foi
   adicionada nesta PR).

## PRs analisadas (detalhado)

### #203 — refactor(storage): adjust filesystem r2 local

Principais mudanças auditadas:

| Arquivo | Natureza | Observação de segurança |
|---|---|---|
| `config/livewire.php` | reativa bloco `temporary_file_upload` | **MÉDIO** — issue #237 aberta |
| `.env.example` | `MEDIA_DISK=public`, `R2_TEMPORARY_URL=` | Recomendação #1 |
| `config/filesystems.php` | `'media_disk'` + `'serve' => true` em `public` | Não exploitável por si; reforça #229 |
| `app/Filament/Resources/ObraDocumentos/ObraDocumentoResource.php` | `getUploadDisk()` usa config | Endereça parcialmente #229 |
| `app/Filament/Resources/ObraRecebimentos/ObraRecebimentoResource.php` | idem | idem |
| `app/Filament/Resources/ImportacaoNotaFiscals/Schemas/ImportacaoNotaFiscalForm.php` | disk configurável; `visibility('public')` mantido | **#233 não corrigida** |
| `app/Filament/Resources/Asas/Schemas/AsaForm.php` | disk configurável em `evidencias`, `foto_antes/depois`, `projeto_orcado/revisado`, `escopo_contratado/real` | Uploads técnicos de ASA — avaliação caso-a-caso; recomenda-se `private` para escopo financeiro |
| `app/Filament/Resources/RelatorioFotograficos/Schemas/RelatorioFotograficoForm.php` | disk antes era `public`, agora `media_disk`; visibility ainda `public` | Sem regressão; fotos de relatório são de fato públicas por natureza |
| `app/Support/ImageUploadHelper.php` | disk padrão agora vem de config | Sanitização preservada |
| `app/Support/PdfMedia.php` | disk configurável; fallback de `href()` alterado | Sem regressão |
| `app/Services/VisitaTecnicaPdfService.php` | disk configurável | Sem regressão; issue pré-existente de `isRemoteEnabled(true)` não foi introduzida por #203 |
| `app/Console/Commands/Migrate*MediaToR2.php` | novos comandos artisan CLI-only | Sem superfície HTTP; usam Eloquent com bindings |

### #236 — refactor(storage): warnings vs errors

- Refino de telemetria dos comandos de migração. Não introduz
  vulnerabilidades novas; melhora a auditabilidade das operações.

## Issues abertas nesta auditoria

- [#237](https://github.com/dpcconsultoria/gestaosmart/issues/237) —
  **MÉDIO** — Livewire `temporary_file_upload` reativado com limite de
  716 MB sem cota por usuário.

## Comentários adicionados

- [#229](https://github.com/dpcconsultoria/gestaosmart/issues/229) —
  Endereçamento parcial pela PR #203; visibility ainda pendente.
- [#233](https://github.com/dpcconsultoria/gestaosmart/issues/233) —
  PR #203 tocou o arquivo mas não corrigiu `visibility('public')`.
