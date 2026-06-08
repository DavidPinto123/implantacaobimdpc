# Jobs & Filas

## Commands (app/Console/Commands/)

Comandos Artisan para operações batch e manutenção.

### Migração de Mídias para R2

Comandos para migrar arquivos do disco `public` (local) para o disco `r2` (Cloudflare R2). Compartilham a trait `app/Console/Commands/Concerns/InteractsWithR2Migration.php`. Suportam `--dry-run` para simular sem gravar.

| Comando | Arquivo | Descrição |
|---------|---------|-----------|
| `media:migrate-to-r2` | `MigrateAllMediaToR2.php` | Orquestrador: executa todos os comandos abaixo em sequência; suporta `--stop-on-error` |
| `asa:migrate-media-to-r2` | `MigrateAsaMediaToR2.php` | Migra anexos e evidências de ASAs |
| `autorizacao-servico:migrate-media-to-r2` | `MigrateAutorizacaoServicoMediaToR2.php` | Migra anexos de autorizações de serviço |
| `elaboracao-aditivo:migrate-media-to-r2` | `MigrateElaboracaoAditivoMediaToR2.php` | Migra anexos e comparativos de aditivos |
| `matterport:migrate-media-to-r2` | `MigrateMatterportMediaToR2.php` | Migra imagens e PDFs de Matterport |
| `midia:migrate-to-r2` | `MigrateMidiaToR2.php` | Migra registros da tabela `midias` e corrige o campo `disk` |
| `projeto-ponto:migrate-media-to-r2` | `MigrateProjetoPontoMediaToR2.php` | Migra anexos legados de Cadastro de Ponto para `arquivos-pt/{id}/midia` |
| `relatorio-fotografico:migrate-media-to-r2` | `MigrateRelatorioFotograficoMediaToR2.php` | Migra fotos e arquivos de entregas contratuais |
| `vt:migrate-media-to-r2` | `MigrateVisitaTecnicaMediaToR2.php` | Migra imagens e vídeos de Visitas Técnicas |
| `app:migrar-fotos-perfil-r2` | `MigrarFotosPerfilParaR2.php` | Migra fotos de perfil de usuários |

**Opções comuns**: `--dry-run` (simula sem gravar), `--id=<id>` (registro específico), `--stop-on-error` (somente `MigrateAllMediaToR2`).

**Métricas de execução** (`InteractsWithR2Migration`):

| Métrica | Significado |
|---------|------------|
| `records` | Registros lidos |
| `files_copied` | Arquivos copiados com sucesso |
| `fields_updated` | Campos de modelo atualizados |
| `warnings` | Arquivos ausentes em qualquer origem (não é erro fatal) — status `missing_everywhere` |
| `skipped` | Arquivos já existentes no R2 (idempotência) — status `already_exists` |
| `errors` | Falhas reais (`stream_error`, `validation_error`) — causam exit code 1 |

**Métodos da trait** `InteractsWithR2Migration` (`app/Console/Commands/Concerns/`):

| Método | Descrição |
|--------|-----------|
| `copyFileToR2PreferringPublic` | Método unificado: tenta disco `public` primeiro; se não encontrar, tenta R2; retorna `missing_everywhere` se ausente em ambos |
| `resolvePublicPath` | Resolve caminho do arquivo no disco `public` (com candidatos normalizados) |
| `resolveR2Path` | Resolve caminho do arquivo no disco `r2` (com candidatos `storage/` normalizados) |
| `copyPublicFileToR2` | Copia arquivo do disco `public` para R2 |
| `copyR2FileToR2` | Copia arquivo entre caminhos no disco R2 |
| `normalizeFiles` | Normaliza valor JSON de campo de arquivo para array |
| `extractPath` | Extrai caminho de um item de arquivo |
| `sanitizeFileName` | Sanitiza nome do arquivo para o caminho destino |

Todos os comandos de migração usam `copyFileToR2PreferringPublic` como ponto de entrada unificado. O array de retorno inclui os campos `status`, `source`, `target`, `copied` e `source_disk` (`'public'`, `'r2'` ou `null`).

### Outros Commands

| Comando | Arquivo | Descrição |
|---------|---------|-----------|
| `obras:recalcular-campos` | `RecalcularCamposObras.php` | Recalcula campos derivados de obras (dias para inauguração, prazos, desvio) |
| `autorizacao-servico:reconstruir` | `ReconstruirAutorizacaoServicoParaEscopos.php` | Reconstrói relações `AutorizacaoServico` → `AsEscopos` |
| `pos-obra:verificar-slas` | `VerificarSlasPendencias.php` | Verifica pendências com SLA vencido e dispara alertas escalonados |

## Jobs (app/Jobs/)

### GenerateVisitaTecnicaPdfJob
- **Arquivo**: `app/Jobs/GenerateVisitaTecnicaPdfJob.php`
- Geração assíncrona do PDF de Visita Técnica
- Delegado para fila após criar/finalizar um relatório
- Usa `VisitaTecnicaPdfService`

### ProcessObraImportJob
- **Arquivo**: `app/Jobs/ProcessObraImportJob.php`
- Processamento assíncrono de importação de obras via planilha
- Disparado pela Page `ImportObras`
- Usa `SpreadsheetParserService`

## Exports (app/Exports/)

| Export | Formato | Uso |
|--------|---------|-----|
| `ProjetoExport` | Excel | Exporta lista de projetos |
| `ListProjetoExport` | Excel | Exporta listagem detalhada de projetos |
| `ElaboracaoAditivoPlanilhaExport` | Excel | Exporta aditivos contratuais |

## Imports (app/Imports/)

| Import | Formato | Uso |
|--------|---------|-----|
| `ProjetosImport` | Excel | Importa projetos de planilha |
| `ControlePedidosBaseImport` | Excel | Importa base de pedidos |

## Configuração de Filas

- Driver configurado em `config/queue.php`
- Em desenvolvimento: driver `sync` (executa na mesma requisição)
- Em produção: driver `database` ou `redis`

## Rodar worker manualmente

```bash
php artisan queue:work
# ou via DDEV:
ddev artisan queue:work
```

## Monitorar filas (via Pail)

```bash
php artisan pail
# ou via DDEV:
ddev artisan pail
```
