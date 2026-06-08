# Services

Serviços de negócio em `app/Services/`.

## Services principais

### VisitaTecnicaPdfService
- **Arquivo**: `app/Services/VisitaTecnicaPdfService.php`
- Gera PDF do Relatório de Visita Técnica
- Usado pelo `GenerateVisitaTecnicaPdfJob` (assíncrono)

### RelatorioFotograficoPdfService
- **Arquivo**: `app/Services/RelatorioFotograficoPdfService.php`
- Gera PDF do Relatório Fotográfico
- Converte imagens do R2 para base64 antes de embutir no PDF

### RelatorioFotograficoTaskService
- **Arquivo**: `app/Services/RelatorioFotograficoTaskService.php`
- Gerencia tarefas do fluxo de relatório fotográfico (autor → gestor)

### RelatorioVisitaTecnicaTaskService
- **Arquivo**: `app/Services/RelatorioVisitaTecnicaTaskService.php`
- Gerencia tarefas do fluxo de visita técnica

### CapexSimulacaoPdfService
- **Arquivo**: `app/Services/CapexSimulacaoPdfService.php`
- Gera PDF da Simulação CAPEX usando DomPDF (`barryvdh/laravel-dompdf`)
- View: `resources/views/invoices/pdfCapexSimulacao.blade.php`
- Usado pela página `EditCapexSimulacao` (botão de exportar PDF)
- Carrega relações `itens`, `projeto` e `faixaArea` antes de renderizar

### AsaService
- **Arquivo**: `app/Services/AsaService.php`
- Lógica de negócio dos assessments ASA
- Cálculos de área, escopo e valores
- `normalizeMediaPaths(Asa $asa)` — normaliza caminhos de arquivos do ASA para o diretório correto no R2 após criação/sincronização

### ConstructinService
- **Arquivo**: `app/Services/ConstructinService.php`
- Integração com plataforma externa ConstructIn
- Sincronização de dados de obras

### ProjetoIAService
- **Arquivo**: `app/Services/ProjetoIAService.php`
- Análise de projetos com Inteligência Artificial
- Provavelmente integra com API de IA (ex: OpenAI)

### SpreadsheetParserService
- **Arquivo**: `app/Services/SpreadsheetParserService.php`
- Parsing de planilhas Excel para import de dados

## Pós Obra Services (app/Services/PosObra/)

### WhatsAppService
- **Arquivo**: `app/Services/PosObra/WhatsAppService.php`
- Singleton registrado no `PosObraServiceProvider`
- Envio de mensagens via WhatsApp Cloud API
- Registro de conversas e mensagens no banco

### WhatsAppBotService
- **Arquivo**: `app/Services/PosObra/WhatsAppBotService.php`
- Singleton registrado no `PosObraServiceProvider`
- Processamento de mensagens recebidas
- Fluxo automatizado de atualização de status de pendências

### PendenciaService
- **Arquivo**: `app/Services/PosObra/PendenciaService.php`
- Singleton registrado no `PosObraServiceProvider`
- Regras de negócio para pendências
- Controle de transições de status
- Verificação de SLA

## Support (app/Support/)

Classes utilitárias de suporte:

| Classe | Arquivo | Descrição |
|--------|---------|-----------|
| `DateCalc` | `Support/DateCalc.php` | Cálculo de dias úteis/corridos |
| `ImageUploadHelper` | `Support/ImageUploadHelper.php` | Upload e processamento de imagens |
| `PdfFormatter` | `Support/PdfFormatter.php` | Utilitários de formatação para PDF |
| `PdfMedia` | `Support/PdfMedia.php` | Manipulação de mídia em PDFs |
| `TextIa` | `Support/TextIa.php` | Processamento de texto com IA |

### DateCalc
Utilitário central para cálculo de prazos. Suporta:
- Dias úteis (exclui fins de semana e feriados)
- Dias corridos
- Usado pelo módulo de Tarefas e SLAs
