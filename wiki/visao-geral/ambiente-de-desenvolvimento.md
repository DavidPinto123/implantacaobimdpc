# Ambiente de Desenvolvimento

## DDEV (ambiente local)

O projeto usa **DDEV** como ambiente Docker local.

### Iniciar

```bash
ddev start
```

### Parar

```bash
ddev stop
```

### Acessar o projeto

- **URL**: http://gestaosmart.ddev.site
- **Banco**: acessível via `ddev describe`

### Executar comandos dentro do container

```bash
ddev exec php artisan migrate
ddev composer install
ddev npm install
```

### Comandos úteis DDEV

```bash
ddev describe          # Exibe URLs, banco, credenciais
ddev ssh               # Acessa o container web
ddev logs              # Ver logs
ddev artisan migrate   # Atalho para php artisan
ddev composer ...      # Atalho para composer
ddev npm ...           # Atalho para npm (respeita nodejs_version)
```

## Variáveis de ambiente

Copiar `.env.example` para `.env` e configurar:

```env
APP_NAME=GestaoSmart
APP_URL=http://gestaosmart.ddev.site

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=gestaosmart
DB_USERNAME=db
DB_PASSWORD=db

# Storage (R2 / S3)
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=auto
AWS_BUCKET=
AWS_ENDPOINT=

# E-mail (Resend)
RESEND_API_KEY=

# WhatsApp
WHATSAPP_TOKEN=
WHATSAPP_VERIFY_TOKEN=
WHATSAPP_PHONE_NUMBER_ID=

# Autodesk APS
APS_CLIENT_ID=
APS_CLIENT_SECRET=
```

## Comando de desenvolvimento

```bash
composer dev
```

Equivale a rodar em paralelo:
- `php artisan serve`
- `php artisan queue:work`
- `php artisan pail` (logs em tempo real)
- `npm run dev` (Vite HMR)

## Banco de dados

```bash
php artisan migrate           # Rodar migrations
php artisan migrate:fresh     # Recriar banco do zero
php artisan db:seed           # Popular com dados iniciais
php artisan db:seed --class=PosObraSeeder
```

### Seeders disponíveis

| Seeder | Descrição |
|--------|-----------|
| `DatabaseSeeder` | Entry point geral |
| `AcompanhamentoSeeder` | Dados de acompanhamento |
| `AmbientesSeeder` | Ambientes/salas |
| `AsEscopoSeeder` | Escopos ASA |
| `CapexEstruturaSeeder` | Estrutura CAPEX |
| `DadosSeeder` | Dados gerais |
| `DepartamentosSeeder` | Departamentos |
| `PosObraSeeder` | Módulo Pós Obra |
| `PosObraDemoSeeder` | Dados demo Pós Obra |
| `PosObraPermissionsSeeder` | Permissões Pós Obra |

## Qualidade de código

```bash
./vendor/bin/pint             # Formatar código (PSR-12)
php artisan test              # Rodar testes
```

## Branches e deploy

```
feature/DDMMAAAA-descricao
     ↓
desenvolvimento
     ↓
homologacao
     ↓
main (produção)
```

## Arquivos locais (não commitados)

- `.ddev/` — configurações DDEV (em `.git/info/exclude`)
- `CLAUDE.md` — contexto para IA (em `.git/info/exclude`)
- `Gestao Smartfit/` — este cofre Obsidian (em `.git/info/exclude`)
- `.env` — variáveis de ambiente (em `.gitignore`)
