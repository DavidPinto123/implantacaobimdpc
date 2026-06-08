# Integrações Externas

## Autodesk Platform Services (APS)

### Propósito
Visualização de modelos BIM (arquivos `.rvt` do Revit) em 3D no navegador.

### Controllers
- **ApsAuthController** (`app/Http/Controllers/ApsAuthController.php`)
  - `GET /aps/token` — obtém token de autenticação APS

- **ApsDocsController** (`app/Http/Controllers/ApsDocsController.php` — 5.8KB)
  - `GET /aps/hubs` — lista hubs
  - `GET /aps/projetos/{hubId}` — lista projetos do hub
  - `GET /aps/pastas/{hubId}/{projetoId}` — lista pastas
  - `GET /aps/arquivos/{projetoId}/{pastaId}` — lista arquivos
  - `GET /aps/docs/rvt/{projectId}/{folderId}/{prefix}` — busca arquivos `.rvt`

### Page/Controller 3D
- **Viewer3DProjetoController** (`app/Http/Controllers/Viewer3DProjetoController.php` — 9.5KB)
  - Rota: `GET /admin/projetos/{projeto}/viewer-3d`
  - Renderiza o viewer APS embutido

### Configuração
```env
APS_CLIENT_ID=
APS_CLIENT_SECRET=
```

---

## Matterport

### Propósito
Tours virtuais 3D dos imóveis/obras.

### Model
- **Matterport** (`app/Models/Matterport.php`)
- Armazena URLs e dados dos scans Matterport

### Resource
- **MatterportResource** — CRUD no painel Filament

### QR Code
- Rota: `GET /projetos/{projeto}/matterport-qrcode`
- Gera QR Code via `Simple QRCode` para acesso ao tour

---

## WhatsApp Cloud API (Meta)

### Propósito
Comunicação automatizada com construtoras no módulo Pós Obra.

### Webhook
- `GET /webhook/whatsapp` — verificação (handshake com Meta)
- `POST /webhook/whatsapp` — recebe mensagens

### Controller
- **WhatsAppWebhookController** (`app/Http/Controllers/PosObra/WhatsAppWebhookController.php`)

### Services
- **WhatsAppService** — envio de mensagens e templates
- **WhatsAppBotService** — processamento do fluxo automatizado

### Configuração
```env
WHATSAPP_TOKEN=
WHATSAPP_VERIFY_TOKEN=
WHATSAPP_PHONE_NUMBER_ID=
```

---

## Cloudflare R2 (Storage)

### Propósito
Armazenamento de arquivos em produção (S3-compatible).

### Uso
- Fotos de perfil: `fotos-perfil/`
- Relatórios fotográficos
- Documentos de obras
- PDFs gerados

### Configuração
```env
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=auto
AWS_BUCKET=
AWS_ENDPOINT=https://<account>.r2.cloudflarestorage.com
```

### Integração com PDF
Imagens armazenadas no R2 são convertidas para **base64** antes de serem embutidas em PDFs, pois os geradores de PDF (DomPDF/Snappy) não acessam URLs externas autenticadas.

### Migração de arquivos legados
Comandos artisan para migrar arquivos do disco `public` para o R2 (ver [Jobs & Filas](backend/jobs-e-filas#migração-de-mídias-para-r2)):

```bash
# Simular migração completa
php artisan media:migrate-to-r2 --dry-run

# Executar migração completa
php artisan media:migrate-to-r2
```

---

## Resend (E-mail)

### Propósito
Envio de e-mails transacionais (PDFs de relatórios, notificações).

### Mailable
- **EnviarPdfMail** (`app/Mail/EnviarPdfMail.php`) — mailable genérico com PDF anexado

### Configuração
```env
RESEND_API_KEY=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME=
```

### Regra de negócio
- PDF do Relatório Fotográfico é enviado **apenas** quando status = "concluído"

---

## ConstructIn

### Propósito
Plataforma externa de gestão de construções.

### Service
- **ConstructinService** (`app/Services/ConstructinService.php`)
- Sincronização de dados de obras

---

## IA / Inteligência Artificial

### ProjetoIAService
- **Arquivo**: `app/Services/ProjetoIAService.php`
- Análise de projetos com IA
- Integração provavelmente via API externa (OpenAI ou similar)

### TextIa
- **Arquivo**: `app/Support/TextIa.php`
- Utilitário de processamento de texto com IA
