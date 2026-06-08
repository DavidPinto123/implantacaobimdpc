# Rotas & Controllers

## Rotas Web (routes/web.php)

| Método | Rota | Controller | Descrição |
|--------|------|-----------|-----------|
| `GET` | `/` | — | Redireciona para painel Filament |
| `GET` | `/dados` | `DadosController` | Listagem de dados |
| `GET` | `/projetos/{projeto}/matterport-qrcode` | — | QR Code do Matterport |
| `GET` | `/{record}/pdf/download` | `VisitaTecnicaDownloadController` | Download do PDF de visita técnica |
| `GET` | `/relatorios-fotograficos/{record}/pdf` | `RelatorioFotograficoPdfController` | PDF do relatório fotográfico |
| `GET` | `/projetos-por-estado/{sigla}` | — | Projetos filtrados por UF |
| `GET` | `/aps/token` | `ApsAuthController` | Token de autenticação APS |
| `GET` | `/aps/hubs` | `ApsDocsController` | Lista hubs APS |
| `GET` | `/aps/projetos/{hubId}` | `ApsDocsController` | Projetos do hub |
| `GET` | `/aps/pastas/{hubId}/{projetoId}` | `ApsDocsController` | Pastas do projeto APS |
| `GET` | `/aps/arquivos/{projetoId}/{pastaId}` | `ApsDocsController` | Arquivos da pasta |
| `GET` | `/aps/docs/rvt/{projectId}/{folderId}/{prefix}` | `ApsDocsController` | Arquivos `.rvt` |
| `GET` | `/admin/projetos/{projeto}/viewer-3d` | `Viewer3DProjetoController` | Viewer 3D |
| `GET` | `/webhook/whatsapp` | `WhatsAppWebhookController` | Verificação webhook |
| `POST` | `/webhook/whatsapp` | `WhatsAppWebhookController` | Recebe mensagens |

> **Nota**: A maioria das operações CRUD acontece via Filament (sem rotas web manuais). Os controllers são usados apenas para operações especiais.

## Controllers (app/Http/Controllers/)

### Controller.php
- Controller base (padrão Laravel)

### DadosController.php
- Endpoint de dados gerais
- `GET /dados`

### ApsAuthController.php
- Autenticação com Autodesk Platform Services
- Troca de credenciais por token

### ApsDocsController.php (5.8KB)
- Navegação de documentos no APS
- Hubs → Projetos → Pastas → Arquivos
- Filtro específico para arquivos `.rvt` (Revit)

### Viewer3DProjetoController.php (9.5KB)
- Renderiza o viewer 3D embutido
- Integra o SDK do Autodesk Viewer
- Requer autenticação via APS

### RelatorioFotograficoPdfController.php
- Gera o PDF do relatório fotográfico
- Usa `RelatorioFotograficoPdfService`
- Imagens S3 convertidas para base64

### VisitaTecnicaDownloadController.php
- Download do PDF de visita técnica
- Pode usar job assíncrono (`GenerateVisitaTecnicaPdfJob`)

### PosObra/WhatsAppWebhookController.php
- `GET`: handshake de verificação com a API Meta
- `POST`: recebe e processa mensagens recebidas
- Delega ao `WhatsAppBotService`

## Rota especial: projetos-por-estado

```
GET /projetos-por-estado/{sigla}
```

Query complexa que retorna projetos filtrados por UF com múltiplos status. Usada nos mapas geográficos.

## Console routes (routes/console.php)

Configuração de comandos Artisan agendados e customizados.
