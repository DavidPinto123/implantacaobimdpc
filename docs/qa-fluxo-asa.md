# QA - Fluxo ASA

Este roteiro valida o caminho da Autorizacao de Servico Adicional (ASA), desde a criacao do ponto ate a aprovacao da nota fiscal. A ASA nasce como solicitacao do fornecedor antes da linha auxiliar fiscal estar disponivel para importacao.

## Pre-requisitos

- Usuario Comercial com permissao para cadastrar ponto.
- Usuario responsavel por CNPJ/projeto com permissao para editar projeto e atribuir gestor.
- Gestor com permissao para criar obra, aprovar ASA e visualizar Controle de Nota Fiscal.
- Orcamentista com permissao para acessar Controle AS, criar AS a partir da ASA aprovada, enviar AS, visualizar AS, cancelar AS e aprovar notas fiscais.
- Fornecedor com usuario ativo vinculado a Construtora.
- Construtora/fornecedor cadastrado com nome, CNPJ e usuario vinculado.
- Escopos AS ativos e nao personalizados cadastrados.

## Criacao da Simulacao OI

1. Acessar `Expansao > Orcamentos > Simulacao OI`.
2. Criar uma Simulacao OI vinculada ao projeto usado no fluxo.
3. Incluir itens de escopos normais e, se aplicavel, escopos personalizados/manuais com escopo incluido.
4. Aprovar a Simulacao OI.

Verificacoes:

- A Simulacao OI deve estar vinculada ao projeto.
- A Simulacao OI deve estar aprovada.
- O item personalizado/manual usado no teste deve estar com escopo incluido.
- O fluxo ASA nao deve alterar o fluxo interno da Simulacao OI.

## Fluxo Principal

1. Comercial cadastra o ponto em `Expansao > Comercial > Cadastrar ponto`.
   - Preencher codigo, nome, marca, data de posse, endereco completo, pais, estado, cidade, area da academia e status iniciais.
   - Verificar que o projeto foi criado.

2. Responsavel por CNPJ/projeto cadastra o CNPJ em `Outros > Cadastros > Cadastrar CNPJ`.
   - Sugestao: use um usuario com permissoes de super admin.
   - Preencher CNPJ definitivo ou provisorio.
   - Preencher o status do CNPJ conforme o campo usado.
   - Salvar e verificar que o CNPJ ficou vinculado ao projeto.

3. Responsavel por CNPJ/projeto informa o gestor do projeto em `Outros > Projetos`.
   - Sugestao: use um usuario com permissoes de super admin.
   - Editar o projeto criado no passo 1.
   - Na secao `Squad`, preencher `Responsavel Engenharia` com o gestor que criara a obra.
   - Sugestao: selecione um usuario com permissao de gestor de obra quando estiver disponivel para selecao.
   - Salvar o projeto.

4. Gestor converte o projeto em obra em `Expansao > Engenharia > Obras`.
   - Sugestao: use um usuario com permissao de gestor de obra.
   - Clicar em criar obra e selecionar o projeto criado no passo 1.
   - Preencher projeto, status, unidade, codigo da obra e engenharia/gestor.
   - Verificar que o Controle de Nota Fiscal de Expansao foi criado automaticamente.
   - Verificar que as linhas foram preenchidas com escopos ativos e nao personalizados.

5. Orcamentista abre o Controle AS e importa Simulacao OI, quando aplicavel, em `Expansao > Orcamentos > Controle de AS`.
   - Pressionar o botao de Simulacao OI na obra.
   - Confirmar o modal que avisa que valores personalizados digitados podem ser sobrescritos.
   - Verificar que a simulacao aprovada vinculada ao projeto foi usada automaticamente.
   - Verificar que escopos personalizados/manuais aprovados na Simulacao OI criaram linhas no Controle AS.

6. Fornecedor cria elaboracao de aditivo em `Outros > Construtora > Elaboracao de Aditivos`.
   - Acessar o fluxo de elaboracao de aditivo.
   - Preencher obra, fornecedor, descricao/justificativa, valores e anexos exigidos.
   - Enviar solicitacao.
   - Verificar que a ASA nasce como solicitacao do fornecedor com status `solicitado` (enum `App\Enums\AsStatus`).

7. Validar campos obrigatorios da solicitacao/aditivo em `Outros > Construtora > Elaboracao de Aditivos`.
   - Tentar criar solicitacao sem obra.
   - Tentar criar solicitacao sem fornecedor.
   - Tentar criar solicitacao sem descricao/justificativa obrigatoria.
   - Tentar criar solicitacao sem valor/anexo quando obrigatorio.
   - Verificar que o sistema bloqueia e informa os campos pendentes.

8. Gestor aprova ASA em `Expansao > Engenharia > ASA`.
   - Abrir a ASA solicitada.
   - Aprovar pelo gestor.
   - Verificar que a ASA segue para aprovacao do orcamento (`status` passa a `em_aprovacao_orcamento`).
   - Verificar que a linha auxiliar fiscal foi criada/vinculada ao Controle AS/Controle de NF.

9. Orcamentista cria AS a partir da ASA aprovada em `Expansao > Orcamentos > Controle de AS`.
   - Localizar a linha auxiliar da ASA aprovada pelo gestor.
   - Clicar em `Criar AS`.
   - No modal, preencher datas, desconto, parcelamento, descricao do PDF e anexos.
   - Confirmar criacao.
   - Verificar que o PDF foi gerado, valores foram calculados e o status da ASA passou a `criada` (enum `AsStatus::CRIADA`).
   - Verificar que o `status_fluxo` do `elaboracao_aditivo` passou a `aprovado`.

10. Enviar AS em `Expansao > Orcamentos > Controle de AS`.
    - Clicar em `Enviar AS`.
    - Verificar que o modal segue o padrao do envio de AS principal.
    - Verificar que o modal sugere fornecedor em Para e gestor em CC quando houver e-mail cadastrado.
    - Remover os e-mails do modal e tentar enviar.
    - Verificar comportamento esperado:
      - Se houver e-mail digitado, apenas esses destinatarios recebem e-mail.
      - Se nenhum e-mail for digitado, nenhum e-mail deve ser enviado.
      - Mesmo sem e-mail digitado, o fornecedor vinculado deve ser notificado no aplicativo.
    - Verificar que o adicional fica liberado para o fornecedor e o status da ASA passa a `enviada`.

11. Visualizar AS da ASA em `Expansao > Orcamentos > Controle de AS`.
    - Clicar em `Visualizar AS` na linha auxiliar (disponivel quando a ASA esta em `criada`, `enviada` ou `cancelada`).
    - Verificar que abre em nova aba a tela `Asas > Edit` com dados da AS gerada (datas, parcelamento, valores, anexos, PDF).
    - Verificar que respeita a permissao `View:Asa`.

12. Cancelar AS da ASA em `Expansao > Orcamentos > Controle de AS`.
    - Clicar em `Cancelar AS` na linha auxiliar (disponivel quando a ASA esta em `criada` ou `enviada`).
    - Preencher destinatarios (Para/CC/CCO) e confirmar.
    - Verificar que a ASA fica com status `cancelada`, motivo, autor e timestamp gravados.
    - Verificar que o e-mail "AS cancelada {numero_asa}" foi enviado apenas para os destinatarios digitados.
    - Verificar que a notificacao no aplicativo "Item cancelado pelo orcamentista" foi criada para usuarios ativos cujo e-mail esteja em Para.
    - Verificar que o `status_fluxo` do `elaboracao_aditivo` passou a `cancelado`.
    - Tentar cancelar a mesma ASA novamente; verificar que e tratada como no-op (sem duplicar timestamps).
    - Quando ha NF aprovada vinculada: verificar que o cancelamento e bloqueado, exceto para usuario com permissao `CancelApproved:AutorizacaoServico`.

13. Gestor valida Controle de NF em `Expansao > Engenharia > Controle de Notas Fiscais`.
    - Abrir Controle de NF da obra.
    - Verificar que AS e ASA aparecem como destinos fiscais distintos.
    - Verificar que a ASA aparece como linha auxiliar/adicional.

14. Fornecedor importa nota fiscal da ASA em `Outros > Construtora > Meus controles de NF`.
    - Acessar Meus controles de NF.
    - Abrir o link de importacao da linha ASA.
    - Preencher tipo de medicao, empresa, CNPJ fornecedor, numero da NF, CNPJ faturamento, valor medido, emissao, instrucoes de pagamento, arquivo da NF e observacoes.
    - Confirmar importacao.
    - Verificar que a NF entra em analise vinculada a ASA.

15. Validar campos obrigatorios da importacao de NF em `Outros > Construtora > Meus controles de NF`.
    - Tentar importar sem numero da NF.
    - Tentar importar sem CNPJ fornecedor.
    - Tentar importar sem valor medido.
    - Tentar importar sem arquivo da NF, se obrigatorio.
    - Verificar que o sistema bloqueia e informa os campos pendentes.

16. Orcamentista aprova nota fiscal da ASA em `Expansao > Engenharia > Aprovacao de Notas Fiscais`.
    - Acessar Aprovacao de Notas Fiscais.
    - Selecionar o controle.
    - Visualizar a NF.
    - Aprovar.
    - Verificar que a NF fica aprovada.
    - Verificar que o saldo da linha auxiliar/adicional foi recalculado.
    - Verificar que o fornecedor foi notificado no aplicativo.
    - Verificar que e-mail de aprovacao foi enviado apenas para destinatarios com e-mail aplicavel.

## Criterios de Aceite

- A ASA nasce antes da linha fiscal como solicitacao do fornecedor.
- O `status` da ASA usa o enum `App\Enums\AsStatus` (mesmos 12 estados da AS principal).
- A linha auxiliar fiscal nasce/vincula depois da aprovacao do fluxo ASA.
- A ASA fica disponivel no Controle AS para criacao da AS (`Criar AS`) pelo orcamentista assim que aprovada.
- `Enviar AS` libera o fornecedor e notifica no aplicativo mesmo sem e-mail digitado.
- `Visualizar AS` abre a tela `Asas > Edit` em nova aba quando o status permite (`criada`, `enviada`, `cancelada`).
- `Cancelar AS` grava motivo/timestamp/autor, dispara o e-mail "AS cancelada" e bloqueia quando ha NF aprovada (exceto permissao `CancelApproved:AutorizacaoServico`).
- E-mail so e enviado para e-mails digitados/selecionados.
- NF da ASA fica vinculada por `autorizacao_servico_adicional_id`.
- NF da ASA nao usa `asa_id`.
- NF aprovada recalcula saldo da linha auxiliar.
- AS e ASA permanecem como destinos fiscais distintos no Controle de NF.
