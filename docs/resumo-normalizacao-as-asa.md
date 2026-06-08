# Resumo - Normalizacao AS/ASA

## Objetivo

Normalizar a nomenclatura de banco do fluxo fiscal AS/ASA, mantendo a regra de negocio em que:

- AS significa `AutorizacaoServico`.
- ASA significa `AutorizacaoServicoAdicional`.
- AS nasce depois da linha principal do Controle de Nota Fiscal.
- ASA nasce antes da linha auxiliar, como solicitacao do fornecedor.

## Banco de Dados

Foram normalizados os nomes fisicos ligados a ASA:

- Tabela `asas` passou para `autorizacao_servico_adicionais`.
- Tabela `asa_items` passou para `autorizacao_servico_adicional_items`.
- Coluna `asa_id` passou para `autorizacao_servico_adicional_id` em:
  - `controle_nota_fiscals`
  - `controle_nota_fiscal_notas`
  - `autorizacao_servico_adicional_items`

Tambem foi removido o vinculo legado:

- `controle_nota_fiscal_items.autorizacao_servico_id`

O vinculo fiscal principal agora fica centralizado assim:

- Nota fiscal de AS usa `autorizacao_servico_id`.
- Nota fiscal de ASA usa `autorizacao_servico_adicional_id`.
- Linha principal chega na AS por `autorizacao_servicos.controle_nota_fiscal_item_id`.
- Linha auxiliar chega na ASA por `autorizacao_servico_adicionais.controle_nota_fiscal_auxiliar_id`.

## Codigo

Os models e relacionamentos foram ajustados para apontar para os nomes normalizados:

- `Asa` usa a tabela `autorizacao_servico_adicionais`.
- `AsaItem` usa a tabela `autorizacao_servico_adicional_items`.
- Relacionamentos fiscais usam `autorizacao_servico_adicional_id`.

Foi criado o alias de model:

- `AutorizacaoServicoAdicional extends Asa`

O nome `Asa` foi mantido em alguns pontos de codigo, recursos Filament, metodos e views para preservar compatibilidade de dominio e evitar renomeacoes estruturais desnecessarias.

## Fluxo Fiscal

O fluxo fiscal foi ajustado para preservar destinos distintos:

- AS e ASA aparecem no Controle de Nota Fiscal.
- Fornecedor importa NF para AS ou ASA pelo destino fiscal correto.
- NF de AS e NF de ASA entram em analise.
- Orcamentista aprova NF pela tela de aprovacao.
- Aprovacao recalcula saldo da linha principal ou auxiliar correspondente.

## Simulacao OI

O Controle AS passou a importar valores da Simulacao OI aprovada automaticamente pelo projeto da obra:

- Nao ha selecao manual de simulacao no botao.
- O botao abre apenas confirmacao.
- Valores personalizados digitados podem ser sobrescritos apos confirmacao.
- Linhas divergentes do valor importado da OI ficam destacadas.
- Escopos personalizados/manuais aprovados na OI criam linhas no Controle AS para futura criacao de AS.

## Notificacoes

Foram adicionadas notificacoes para trocas de agente no fluxo fiscal:

- Importacao de NF notifica aprovadores por e-mail e aplicativo.
- Aprovacao/reprovacao de NF notifica o fornecedor/importador por e-mail e aplicativo.
- Envio/liberacao de AS e ASA notifica o fornecedor no aplicativo.

Regra esperada:

- E-mail e enviado apenas para e-mails digitados/selecionados.
- Notificacao no aplicativo deve ocorrer para o usuario da proxima acao quando houver usuario vinculado.

## Testes

Foram adicionados/ajustados testes para cobrir:

- Schema normalizado de ASA.
- Remocao do vinculo legado no item principal.
- Importacao de NF para AS e ASA pelos campos normalizados.
- Fluxo AS/ASA ponta a ponta ate aprovacao da nota fiscal.
- Simulacao OI automatica por projeto.
- Criacao de linhas para escopos personalizados/manuais da OI.
- Destaque visual de valores divergentes da OI.

