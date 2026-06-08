# Stack Tecnológica

## Backend

| Tecnologia | Versão | Uso |
|-----------|--------|-----|
| PHP | ^8.2 | Linguagem principal |
| Laravel | ^11.31 | Framework web |
| Filament | ^4.0 | Painel administrativo |
| Livewire | (via Filament) | Componentes reativos |
| Spatie Permission | ^6.21 | Roles e permissões |
| Filament Shield | ^4.0 | RBAC no painel |
| Maatwebsite Excel | ^3.1 | Import/export de planilhas |
| Laravel DomPDF | ^3.1 | Geração de PDF |
| Laravel Snappy | ^1.0 | PDF via wkhtmltopdf |
| Intervention Image | ^4.0 | Manipulação de imagens |
| Simple QRCode | ^4.2 | Geração de QR codes |
| Guzzle | ^7.9 | HTTP client |
| Laravel Invoices | ^4.1 | Geração de faturas |
| Flysystem AWS S3 | ^3.32 | Storage S3/R2 |
| Filament Apex Charts | 4.0-beta1 | Gráficos |
| Doctrine DBAL | ^4.3 | Abstração de banco |
| Resend Laravel | ^1.3 | Envio de e-mails |

## Frontend

| Tecnologia | Versão | Uso |
|-----------|--------|-----|
| Vite | ^6.0.11 | Build tool |
| Tailwind CSS | ^4.1.13 | Framework CSS |
| Axios | ^1.7.4 | HTTP client JS |
| PostCSS | ^8.4.47 | Transformação CSS |
| Autoprefixer | ^10.4.21 | Prefixos CSS |

## Infraestrutura

| Serviço | Uso |
|---------|-----|
| DDEV | Ambiente local (Docker) |
| MariaDB 11.8 | Banco de dados local |
| Cloudflare R2 | Storage de arquivos (prod) |
| Resend | E-mail transacional |
| Laravel Sail | Alternativa Docker |

## Ferramentas de Dev

| Ferramenta | Uso |
|-----------|-----|
| Laravel Pint | Formatação de código (PSR-12) |
| Laravel Pail | Visualização de logs |
| PHPUnit | Testes |
| Faker | Dados falsos para seeds |
| Mockery | Mocking em testes |

## Paleta de cores (branding)

- **Primária**: `#fbba00` (amarelo Smart Fit)

## Comando de desenvolvimento

```bash
composer dev
# Equivale a: serve + queue + pail + npm dev (concurrently)
```
