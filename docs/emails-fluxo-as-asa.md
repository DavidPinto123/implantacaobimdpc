# Emails - Fluxos AS e ASA

Este documento consolida os emails disparados nos fluxos de Autorizacao de Servico (AS) e Autorizacao de Servico Adicional (ASA), considerando os roteiros de QA e os pontos de envio automatico identificados no codigo.

## Resumo

- O `status` de AS e ASA usa o enum `App\Enums\AsStatus` (12 estados unificados).
- O envio manual de AS e ASA acontece pelo Controle AS e usa os destinatarios do modal.
- O cancelamento manual de AS e ASA acontece pelo Controle AS e tambem usa os destinatarios do modal.
- No envio manual de AS e ASA, a notificacao no aplicativo deve ir para usuarios ativos cujo email esteja selecionado em `Para`.
- No cancelamento manual de ASA, a notificacao no aplicativo "Item cancelado pelo orcamentista" vai para usuarios ativos cujo email esteja selecionado em `Para`.
- A importacao de NF dispara email automatico para usuarios ativos vinculados ao fornecedor da AS ou ASA.
- A aprovacao ou reprovacao de NF dispara email automatico para o usuario que importou a NF.
- As notificacoes no aplicativo dos itens 2, 7, 8, 11 e 12 seguem os mesmos destinatarios descritos para cada evento.

## Matriz de disparos

| ID | Fluxo | Momento | Email | No app | Quem aciona | Destinatarios |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | AS | Criar AS no Controle AS | Nao | Nao | Orcamentista | Nenhum. |
| 2 | AS | Enviar AS no Controle AS e liberar item para fornecedor | Sim | Sim | Orcamentista | Email:<br>- Envia para todos os emails selecionados no modal.<br>- Auto seleciona em `Para`: usuarios ativos vinculados a construtora/fornecedor da AS.<br>- Auto seleciona em `CC`: responsavel de engenharia do projeto.<br>- Auto seleciona em `CCO`: usuario logado, quando aplicavel.<br>- Os emails auto selecionados podem ser removidos antes do envio.<br>No app:<br>- usuarios ativos cujo email esteja selecionado em `Para`. |
| 3 | AS | Cancelar AS no Controle AS | Sim | Nao | Orcamentista | Email:<br>- Envia para todos os emails selecionados no modal (Para/CC/CCO).<br>- Falha o cancelamento se `Para` estiver vazio (com mensagem orientando informar e-mail). |
| 4 | AS | Fornecedor importa NF da AS | Sim automatico | Sim | Fornecedor | Usuarios ativos vinculados ao fornecedor da AS. |
| 5 | AS | Aprovar NF da AS | Sim automatico | Sim | Orcamentista/aprovador | Usuario que importou a NF. |
| 6 | AS | Reprovar NF da AS | Sim automatico | Sim | Orcamentista/aprovador | Usuario que importou a NF. |
| 7 | ASA | Criar aditivo | Nao | Nao | Fornecedor | Nenhum. |
| 8 | ASA | Criar ASA a partir do aditivo e enviar para gestor | Sim | Sim | Fornecedor | Email e app:<br>- usuario de engenharia localizado pela obra. |
| 9 | ASA | Gestor aprova ASA | Sim | Sim | Gestor | Email e app:<br>- orcamentista do projeto. |
| 10 | ASA | Orcamentista cria AS a partir da ASA aprovada no Controle AS | Nao | Nao | Orcamentista | Nenhum; o status da ASA passa a `criada`. |
| 11 | ASA | Enviar AS no Controle AS e liberar adicional para fornecedor | Sim | Sim | Orcamentista | Email:<br>- Envia para todos os emails selecionados no modal.<br>- Auto seleciona em `Para`: usuarios ativos vinculados a construtora/fornecedor da linha auxiliar.<br>- Auto seleciona em `CC`: responsavel de engenharia do projeto.<br>- Auto seleciona em `CCO`: usuario logado, quando aplicavel.<br>- Os emails auto selecionados podem ser removidos antes do envio.<br>No app:<br>- usuarios ativos cujo email esteja selecionado em `Para`. |
| 12 | ASA | Cancelar AS da ASA no Controle AS | Sim | Sim | Orcamentista | Email:<br>- Envia para todos os emails selecionados no modal (Para/CC/CCO).<br>- Falha o cancelamento se `Para` estiver vazio.<br>No app:<br>- usuarios ativos cujo email esteja selecionado em `Para` recebem notificacao "Item cancelado pelo orcamentista". |
| 13 | ASA | Visualizar AS da ASA no Controle AS | Nao | Nao | Orcamentista | Acao somente leitura, abre `Asas > Edit` em nova aba. |
| 14 | ASA | Fornecedor importa NF da ASA | Sim automatico | Sim | Fornecedor | Usuarios ativos vinculados ao fornecedor da ASA. |
| 15 | ASA | Aprovar NF da ASA | Sim automatico | Sim | Orcamentista/aprovador | Usuario que importou a NF. |
| 16 | ASA | Reprovar NF da ASA | Sim automatico | Sim | Orcamentista/aprovador | Usuario que importou a NF. |

## Detalhes por tipo de email

### Envio de AS (principal)

- Mailable: `App\Mail\AutorizacaoServicoMail`.
- Assunto: `Autorizacao de Servico {numero_as}`.
- Anexos: PDF da AS e anexos cadastrados na AS.
- Origem dos destinatarios padrao:
  - Fornecedor: emails dos usuarios da construtora vinculada.
  - Gestor em copia: `obra.projeto.responsavelEng.email`.
  - Copia oculta: email do usuario logado, quando aplicavel.

### Envio de AS (ASA / linha auxiliar)

- Mailable: `App\Mail\EnviarPdfMail`.
- Assunto: `AS liberada {numero_asa}`.
- Anexos: pode incluir Excel do aditivo, conforme opcao escolhida no modal.
- Origem dos destinatarios padrao:
  - Fornecedor: emails dos usuarios da construtora vinculada ao auxiliar/ASA.
  - Gestor em copia: `obra.projeto.responsavelEng.email`.
  - Copia oculta: email do usuario logado, quando aplicavel.

### ASA enviada para gestor

- Mailable: `App\Mail\EnviarPdfMail`.
- Assunto: `Nova ASA criada {numero_asa}`.
- Destinatario: usuario de engenharia localizado pelo nome em `obra.engenharia`.
- Tambem cria notificacao no aplicativo para o mesmo usuario.

### ASA aprovada pelo gestor

- Mailable: `App\Mail\EnviarPdfMail`.
- Assunto: `ASA aguardando aprovacao do orcamento {numero_asa}`.
- Destinatario: orcamentista do projeto.
- Tambem cria notificacao no aplicativo para o mesmo usuario.

### AS cancelada (principal)

- Mailable: `App\Mail\EnviarPdfMail`.
- Assunto: `AS cancelada {numero_as}`.
- Corpo: informa unidade, fornecedor, escopo e motivo do cancelamento.
- Anexos: nenhum.
- Destinatarios: emails informados em Para/CC/CCO do modal.

### AS cancelada (ASA / linha auxiliar)

- Mailable: `App\Mail\EnviarPdfMail`.
- Assunto: `AS cancelada {numero_asa}`.
- Corpo: informa unidade, fornecedor, escopo e motivo do cancelamento.
- Anexos: nenhum.
- Destinatarios: emails informados em Para/CC/CCO do modal.
- Tambem cria notificacao no aplicativo "Item cancelado pelo orcamentista" para usuarios ativos cujo email esteja em `Para`.

### NF importada

- Mailable: `App\Mail\EnviarPdfMail`.
- Assunto: `Nova nota fiscal para aprovacao {numero_nf}`.
- Corpo: informa que a NF foi importada e aguarda aprovacao, com link para a proxima acao.
- Destinatarios:
  - na AS: usuarios ativos vinculados ao fornecedor da AS;
  - na ASA: usuarios ativos vinculados ao fornecedor da ASA.
- Tambem cria notificacao no aplicativo para os mesmos destinatarios.

### NF aprovada ou reprovada

- Mailable: `App\Mail\EnviarPdfMail`.
- Assunto:
  - `Nota fiscal aprovada {numero_nf}`;
  - `Nota fiscal reprovada {numero_nf}`.
- Destinatario: usuario que importou a NF (`importadoPor`).
- Tambem cria notificacao no aplicativo para esse usuario.

## Notificacoes no aplicativo

| Momento | Destinatario |
| --- | --- |
| Item AS liberado para fornecedor durante envio da AS | Usuarios ativos cujo email esteja selecionado em `Para` no modal de envio da AS. |
| Item AS cancelado durante cancelamento da ASA | Usuarios ativos cujo email esteja selecionado em `Para` no modal de cancelamento da ASA. |
| ASA criada a partir do aditivo | Usuario de engenharia localizado pelo nome em `obra.engenharia`. Tambem recebe email. |
| ASA aprovada pelo gestor e enviada ao orcamento | Orcamentista do projeto. Tambem recebe email. |
| ASA liberada para importacao de NF durante envio da ASA | Usuarios ativos cujo email esteja selecionado em `Para` no modal de envio da ASA. |

## Divergencias com os roteiros de QA

### Envio sem emails no modal

Os roteiros `docs/qa-fluxo-as.md` e `docs/qa-fluxo-asa.md` dizem que, se nenhum email for digitado no modal, nenhum email deve ser enviado e o fornecedor deve ser notificado no aplicativo.

Comportamento atual no codigo:

- AS: o usuario logado e adicionado automaticamente em `CCO` quando tem email e nao esta em `Para`, `CC` ou `CCO`. Assim, pode existir envio mesmo depois de remover os emails visiveis do modal.
- ASA: se `Para` ficar vazio no envio, o envio e bloqueado com a mensagem `Informe ao menos um e-mail valido para enviar a AS.`
- Cancelamento (AS e ASA): se `Para` ficar vazio, o cancelamento e marcado mas o e-mail nao e enviado e o usuario recebe um aviso. A operacao de banco (status `cancelada` + motivo/autor/timestamp) ja foi efetivada.

### Reprovacao de NF

Os roteiros mencionam email de aprovacao da NF, mas o codigo tambem envia email automatico quando a NF e reprovada.

### Importacao de NF

Os roteiros nao destacam email automatico na importacao de NF, mas o codigo envia email para usuarios ativos vinculados ao fornecedor da AS ou ASA assim que a NF e importada.

## Referencias de codigo

- `app/Enums/AsStatus.php`
  - enum unificado para `autorizacao_servicos.status` e `autorizacao_servico_adicionais.status`.
- `app/Services/AutorizacaoServicoFluxoService.php`
  - envio de AS;
  - destinatarios do fornecedor;
  - email do gestor;
  - notificacao de item liberado para fornecedor;
  - cancelamento de AS.
- `app/Services/AsaFluxoService.php`
  - geracao do PDF da ASA;
  - envio da AS adicional;
  - cancelamento da AS adicional (`cancelar`).
- `app/Filament/Resources/AutorizacaoServicos/Pages/ControleAutorizacoesServico.php`
  - modal de envio de AS;
  - modal de envio de ASA;
  - envio de ASA;
  - copia oculta automatica para o usuario logado;
  - acoes `cancelarAs`, `cancelarAsa`, `enviarAsa`;
  - notificacoes `notificarCancelamentoAs` e `notificarCancelamentoAsa`;
  - permissoes `podeVisualizarAsa` e `podeCancelarAsa`.
- `app/Services/ControleNotaFiscal/ControleNotaFiscalNotaService.php`
  - importacao de NF para AS;
  - importacao de NF para ASA.
- `app/Services/ControleNotaFiscal/ControleNotaFiscalAgenteNotificationService.php`
  - emails e notificacoes de NF importada;
  - emails e notificacoes de NF aprovada/reprovada.
- `app/Filament/Pages/AprovacaoNotasFiscaisPage.php`
  - aprovacao e reprovacao de NF.
- `app/Filament/Resources/ElaboracaoAditivos/Pages/ViewElaboracaoAditivoCustom.php`
  - notificacao no aplicativo ao criar ASA a partir do aditivo.
- `app/Filament/Resources/Asas/Pages/EditAsa.php`
  - notificacao no aplicativo ao aprovar ASA pelo gestor.
