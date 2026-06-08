# Guia de Uso do Git - Fluxo de Alterações de Código

Este guia descreve o processo padrão para realizar alterações no código do projeto, garantindo organização, rastreabilidade e qualidade nas entregas.

## 1. Atualizar o código local
Antes de iniciar qualquer trabalho:
```bash
git pull origin desenvolvimento
git pull origin homologacao
git pull origin main
```
Isso garante que você está trabalhando com a versão mais recente da branch **desenvolvimento** no repositório remoto.

## 2. Criar uma branch de trabalho
Sempre crie sua branch a partir da branch **desenvolvimento**.  
Utilize um nome descritivo para a branch, seguindo o padrão:

```
feature/DDMMAAAA-descricao-resumida
bugfix/DDMMAAAA-descricao-resumida
hotfix/DDMMAAAA-descricao-resumida
```

Onde:
- **DDMMAAAA**: dia, mês e ano da criação da branch (por exemplo: `12082025` para 12/08/2025).
- **descricao-resumida**: breve descrição da funcionalidade ou correção, separada por hifens.

**Exemplos:**
```
feature/12082025-cadastro-clientes
bugfix/12082025-corrigir-calculo-total
```

**Comandos para criar a branch:**
```bash
git checkout desenvolvimento
git pull origin desevolvimento
git checkout -b feature/12082025-cadastro-clientes //criar e alternar para a nova branch em um único comando
```

## 3. Desenvolvimento e testes locais
- Implemente as alterações necessárias.
- Teste localmente para garantir que tudo esteja funcionando antes do merge.

### DDEV
- Em instalações novas, execute os testes dentro do DDEV e migre o banco de testes antes da suíte:
```bash
ddev artisan migrate --env=testing --no-interaction
ddev artisan test --compact
```

### Usuários demo locais
Ao executar o `LocalDemoSeeder`, os usuários abaixo são criados com senha `password`:

| Perfil | E-mail | Roles |
|---|---|---|
| Super admin | `super.admin@example.test` | `super_admin` |
| PMO expansão | `pmo.expansao@example.test` | `PMO` |
| Comercial expansão | `comercial.expansao@example.test` | `Comercial` |
| Gestor de obra | `gestor.obra@example.test` | `Gestor`, `Engenharia`, `Arquitetura` |
| Coordenador de obra | `coordenador.obra@example.test` | `Coordenador` |
| Orçamentista | `coordenador.orcamentos@example.test` | `coordenador_orcamento` |
| Construtora terceiros | `construtora.terceiros@example.test` | `Construtora` |
| Construtora obra | `construtora.obra@example.test` | `Construtora` |

- Para testes de navegador/Playwright, após `npm install` pode ser necessário instalar os binários:
```bash
ddev exec npx playwright install chromium
```
- Se o Chromium reclamar de bibliotecas Linux ausentes (ex.: `libnspr4.so`), instale dependências de sistema no container/imagem DDEV, preferencialmente com:
```bash
ddev exec npx playwright install --with-deps chromium
```
  ou adicionando os pacotes necessários na configuração de imagem do DDEV para persistir entre recriações.

### Laravel Herd
- Use PHP **8.4** no Herd.
- Confirme extensões necessárias:
```bash
php -m | grep -E 'PDO|pdo_mysql|mysqli'
```
- Configure o `.env.testing` apontando para o banco de testes MySQL/MariaDB local (usado pelo Herd/serviço local).
- Para testes de navegador, execute:
```bash
npm install
npx playwright install chromium
```
- Rode migrações e suíte de testes:
```bash
php artisan migrate --env=testing --no-interaction
php artisan test --compact
```

- Reset destrutivo (apenas no banco de **testes**) em qualquer ambiente:
```bash
ddev artisan migrate:fresh --env=testing --no-interaction
# ou
php artisan migrate:fresh --env=testing --no-interaction
```

## 4. Merge para a branch desenvolvimento
Quando a alteração estiver concluída e validada:
```bash
git checkout desenvolvimento
git pull origin desenvolvimento
git merge feature/12082025-cadastro-clientes
git push origin desenvolvimento
```
Isso atualizará o servidor de **desenvolvimento**.

## 5. Atualização e merge para homologação
Após o servidor de desenvolvimento ser atualizado e validado:
```bash
git checkout homologacao
git pull origin homologacao
git merge desenvolvimento
git push origin homologacao
```
Isso atualizará o servidor de **homologação**.

## 6. Validação em homologação
- A equipe de QA (Quality Assurance) realizará os testes na homologação.
- Somente após aprovação, prossiga para produção.

## 7. Merge para a branch main (produção)
Quando os testes em homologação estiverem aprovados:
```bash
git checkout main
git pull origin main
git merge homologacao
git push origin main
```
Isso atualizará o servidor de **produção**.

## Resumo do fluxo
1. Pull da branch desenvolvimento.
2. Criar branch de trabalho a partir de desenvolvimento.
3. Desenvolver e testar localmente.
4. Merge na **desenvolvimento** -> atualização no servidor de desenvolvimento.
5. Merge da **desenvolvimento** para **homologacao** -> atualização no servidor de homologação.
6. Testes e aprovação do QA.
7. Merge da **homologacao** para **main** -> atualização no servidor de produção.
