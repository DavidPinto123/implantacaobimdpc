# Guia de Desenvolvimento

## Convenções de código

- **Formatação**: Laravel Pint (PSR-12) — `./vendor/bin/pint`
- **Lógica de negócio**: em Resources (Filament) e Services, não em Controllers
- **Controllers**: slim — apenas para rotas especiais (PDF, webhooks, APIs externas)
- **Comentários**: apenas onde a lógica não for autoexplicativa
- **Resources desativados**: prefixo `.` no nome do arquivo

## Convenções de nomes

| Tipo | Padrão | Exemplo |
|------|--------|---------|
| Model | Singular | `Projeto`, `Obra`, `Pendencia` |
| Tabela | Plural | `projetos`, `obras`, `po_pendencias` |
| Filament Resource | Singular + Resource | `ProjetoResource` |
| Controller | Singular + Controller | `DadosController` |
| Service | Singular + Service | `WhatsAppService` |
| Policy | Model + Policy | `PendenciaPolicy` |
| Job | Ação + Job | `GenerateVisitaTecnicaPdfJob` |
| Event | Passado | `PendenciaRegistrada` |
| Listener | Ação | `NotificarConstrutoraNovasPendencias` |
| Observer | Model + Observer | `ObrasObserver` |
| Migration | Timestamp + ação | `2025_05_05_003710_create_projetos_table` |
| Branch | `feature/DDMMAAAA-descricao` | `feature/09042026-pos-obra` |

## Convenções de commit

**Nunca commitar automaticamente** — apenas quando explicitamente solicitado.

### Formato da mensagem

```
tipo(escopo): descrição curta em português

Corpo detalhado explicando o que e por quê.
```

### Tipos

| Tipo | Uso |
|------|-----|
| `feat` | Nova funcionalidade |
| `fix` | Correção de bug |
| `refactor` | Refatoração sem mudança de comportamento |
| `chore` | Tarefas de manutenção |
| `docs` | Documentação |
| `test` | Testes |

### Escopos comuns

`pos-obra`, `policies`, `aditivos`, `vite`, `projetos`, `obras`, `capex`, `asa`, `whatsapp`

### Ordem dos commits em uma feature

1. Infraestrutura base (enums, migrations)
2. Models
3. Policies
4. Services
5. Events / Listeners / Observers
6. Commands
7. Controllers
8. Resources Filament
9. Seeders
10. Adaptações em models existentes
11. Registro na aplicação (providers, rotas)
12. Refactors
13. Configurações de ambiente

### Regras importantes

- **Nunca push** sem solicitação explícita
- **Commits separados por contexto lógico** — responsabilidade única por commit
- **Nunca misturar** refactor/padronização em commit de feature
- **Desfazer commits não publicados**: `git reset HEAD~N` (não `git revert`)
- **Arquivos locais** (`.env`, `vite.config.js` com config DDEV/HMR) não commitados

## Estrutura de um Filament Resource

```
Resources/
  ExemploResource.php          ← Classe principal
  ExemploResource/
    Pages/
      ListExemplos.php
      CreateExemplo.php
      EditExemplo.php
      ViewExemplo.php          ← (opcional)
    RelationManagers/
      RelacaoRelationManager.php
    Schemas/                   ← (opcional) formulários complexos
    Tables/                    ← (opcional) tabelas customizadas
    Widgets/                   ← (opcional)
```

## Adicionando um novo módulo

1. Criar migration(s)
2. Criar Model com relacionamentos
3. Criar Policy
4. Criar Filament Resource
5. Registrar no painel (`AdminPanelProvider`)
6. Criar Seeder se necessário
7. Registrar permissões no Shield
8. **Documentar em** `Gestao Smartfit/`

## Autorização

```php
// Verificar permissão
$this->authorize('view', $projeto);

// No Filament Resource
public static function canView(Model $record): bool
{
    return auth()->user()->can('view_projeto');
}
```

## Storage de arquivos

```php
// Upload para R2 (produção)
Storage::disk('r2')->put($path, $file);

// URL pública
Storage::disk('r2')->url($path);
```

## Geração de PDF

```php
// DomPDF
$pdf = Pdf::loadView('pdfs.relatorio', $data);
return $pdf->download('relatorio.pdf');

// Snappy (wkhtmltopdf)
$pdf = SnappyPdf::loadView('pdfs.relatorio', $data);
```
