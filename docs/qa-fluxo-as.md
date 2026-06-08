# QA - Fluxo AS

Este roteiro valida o caminho da Autorizacao de Servico (AS), desde a criacao do ponto ate a aprovacao da nota fiscal. A Simulacao OI deve ser criada apenas como dado de entrada do teste; este roteiro nao altera nem valida o fluxo interno da Simulacao OI.

## Pre-requisitos

- Usuario Comercial com permissao para cadastrar ponto.
- Usuario responsavel por CNPJ/projeto com permissao para editar projeto e atribuir gestor.
- Gestor com permissao para criar obra e visualizar Controle de Nota Fiscal.
- Orcamentista com permissao para acessar Controle AS, criar/enviar AS e aprovar notas fiscais.
- Fornecedor com usuario ativo vinculado a Construtora.
- Construtora/fornecedor cadastrado com nome, CNPJ e usuario vinculado.
- Escopos AS ativos e nao personalizados cadastrados.

## Criacao da Simulacao OI

1. Acessar `Expansao > Orcamentos > Simulacao OI`.
2. Criar uma Simulacao OI vinculada ao mesmo projeto que sera usado no fluxo.
3. Incluir itens para escopos normais.
4. Incluir ao menos um item de escopo personalizado/manual com escopo incluido.
5. Aprovar a Simulacao OI.

Verificacoes:

- A Simulacao OI deve estar vinculada ao projeto.
- A Simulacao OI deve estar aprovada.
- O item personalizado/manual usado no teste deve estar com escopo incluido.
- Nao usar simulacao vinculada apenas por nome, unidade ou sigla.
- O roteiro de AS nao deve modificar o fluxo interno de criacao/aprovacao da Simulacao OI.

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

5. Orcamentista abre o Controle AS em `Expansao > Orcamentos > Controle de AS`.
   - Localizar a obra criada.
   - Verificar as linhas de escopo.

6. Importar valores da Simulacao OI em `Expansao > Orcamentos > Controle de AS`.
   - Pressionar o botao de Simulacao OI na obra.
   - Confirmar o modal que avisa que valores personalizados digitados podem ser sobrescritos.
   - Verificar que a simulacao aprovada vinculada ao projeto foi usada automaticamente.
   - Verificar que os valores estimados foram atualizados sem recarregar a pagina.
   - Verificar que escopos personalizados/manuais aprovados na Simulacao OI criaram linhas no Controle AS.
   - Verificar que essas linhas possuem escopo vinculado e permitem criar AS posteriormente.

7. Validar alteracao manual de valores no Controle AS em `Expansao > Orcamentos > Controle de AS`.
   - Alterar manualmente o valor estimado de uma linha importada da Simulacao OI.
   - Salvar a linha.
   - Verificar que a linha fica destacada por divergencia em relacao ao valor da Simulacao OI.
   - Pressionar novamente Simulacao OI e confirmar.
   - Verificar que o valor volta ao valor aprovado da Simulacao OI.

8. Criar AS em `Expansao > Orcamentos > Controle de AS`.
   - Preencher fornecedor, valor estimado, valor fechado e percentuais quando necessario.
   - Clicar em criar AS.
   - No modal, preencher datas, desconto, parcelamento, descricao do PDF e anexos.
   - Confirmar criacao.
   - Verificar que a AS foi criada, valores foram calculados e PDF foi gerado.

9. Validar campos obrigatorios da AS em `Expansao > Orcamentos > Controle de AS`.
   - Tentar criar AS sem fornecedor.
   - Tentar criar AS sem valor estimado.
   - Tentar criar AS sem valor fechado quando a regra exigir.
   - Tentar confirmar modal sem datas/parcelamento obrigatorios.
   - Verificar que o sistema bloqueia a acao e exibe erro claro.

10. Editar AS antes do envio em `Expansao > Orcamentos > Controle de AS`.
   - Alterar valor estimado, desconto, datas, descricao ou parcelamento.
   - Confirmar.
   - Verificar que os valores foram recalculados e o PDF foi regenerado.

11. Enviar AS em `Expansao > Orcamentos > Controle de AS`.
    - Clicar em enviar AS.
    - Verificar que o modal sugere fornecedor em Para e gestor em CC quando houver e-mail cadastrado.
    - Remover os e-mails do modal e tentar enviar.
    - Verificar comportamento esperado:
      - Se houver e-mail digitado, apenas esses destinatarios recebem e-mail.
      - Se nenhum e-mail for digitado, nenhum e-mail deve ser enviado.
      - Mesmo sem e-mail digitado, o fornecedor vinculado deve ser notificado no aplicativo.
    - Verificar que a linha fica liberada para o fornecedor e o status da AS passa a `enviada` (enum `App\Enums\AsStatus`).

11.1. Cancelar AS em `Expansao > Orcamentos > Controle de AS`.
    - Clicar em `Cancelar AS` na linha principal (disponivel quando a AS esta em `criada` ou `enviada`).
    - Preencher destinatarios (Para/CC/CCO) e confirmar.
    - Verificar que a AS fica com status `cancelada`, motivo, autor e timestamp gravados.
    - Verificar que o e-mail "AS cancelada {numero_as}" foi enviado para os destinatarios digitados.
    - Quando ha NF aprovada vinculada: verificar que o cancelamento e bloqueado, exceto para usuario com permissao `CancelApproved:AutorizacaoServico`.

12. Fornecedor importa nota fiscal da AS em `Outros > Construtora > Meus controles de NF`.
    - Acessar Meus controles de NF.
    - Abrir o link de importacao da linha AS.
    - Preencher tipo de medicao, empresa, CNPJ fornecedor, numero da NF, CNPJ faturamento, valor medido, emissao, instrucoes de pagamento, arquivo da NF e observacoes.
    - Confirmar importacao.
    - Verificar que a NF entra em analise vinculada a AS.

13. Validar campos obrigatorios da importacao de NF em `Outros > Construtora > Meus controles de NF`.
    - Tentar importar sem numero da NF.
    - Tentar importar sem CNPJ fornecedor.
    - Tentar importar sem valor medido.
    - Tentar importar sem arquivo da NF, se obrigatorio.
    - Verificar que o sistema bloqueia e informa os campos pendentes.

14. Orcamentista aprova nota fiscal em `Expansao > Engenharia > Aprovacao de Notas Fiscais`.
    - Acessar Aprovacao de Notas Fiscais.
    - Selecionar o controle.
    - Visualizar a NF.
    - Aprovar.
    - Verificar que a NF fica aprovada.
    - Verificar que o saldo da linha foi recalculado.
    - Verificar que o fornecedor foi notificado no aplicativo.
    - Verificar que e-mail de aprovacao foi enviado apenas para destinatarios com e-mail aplicavel.

## Criterios de Aceite

- A AS nasce a partir da linha principal do Controle AS.
- O `status` da AS usa o enum `App\Enums\AsStatus` (`rascunho`, `criada`, `enviada`, `em_orcamento`, `orcada`, `cancelada` e variantes do ciclo ASA).
- A Simulacao OI usada no Controle AS e localizada pelo projeto da obra.
- O botao de Simulacao OI abre apenas confirmacao, nao selecao.
- Valores personalizados podem ser sobrescritos apos confirmacao.
- Linhas divergentes da Simulacao OI ficam destacadas.
- Escopos personalizados/manuais aprovados na Simulacao OI criam linhas aptas a gerar AS.
- Envio de AS libera fornecedor e notifica no aplicativo mesmo sem e-mail digitado.
- Cancelamento de AS grava motivo/autor/timestamp, dispara o e-mail "AS cancelada" e bloqueia quando ha NF aprovada (exceto permissao `CancelApproved:AutorizacaoServico`).
- E-mail so e enviado para e-mails digitados/selecionados.
- NF da AS fica vinculada por autorizacao_servico_id.
- NF aprovada recalcula saldo.
