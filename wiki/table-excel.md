# Table Excel Pattern

## Objetivo

Padronizar tabelas no projeto com o padrão **Table Excel**, inspirado na experiência da `Obras List`, sem sobrescrever o comportamento global do Filament.

Esse padrão deve ser **opt-in**: apenas `Resources`, `Pages` ou `Widgets` que ativarem explicitamente esse modo receberão a UX avançada.

---

## O que esse padrão precisa suportar

- filtros com boa usabilidade, incluindo modal;
- modal de configurar colunas;
- visual denso estilo Excel; 
- actions na primeira coluna;
- edição inline com `SelectColumn` e colunas customizadas;
- grupos/sections de colunas, como `INFORMAÇÕES DO PROJETO`.

---

## Princípios

1. **Não sobrescrever o padrão global do Filament**.
2. **Toda tabela continua sendo uma `Table` normal do Filament**.
3. **O Table Excel é um preset ativável**, não um renderer paralelo do sistema.
4. **Comportamento visual/estrutural** fica no núcleo reutilizável.
5. **Regra de negócio e colunas do domínio** ficam perto do `Resource`/`Page`.

---

## Direção arquitetural

### Padrão base

Criar um núcleo reutilizável para o padrão Table Excel:

```text
app/Filament/Tables/TableExcel/
├── TableExcelPreset.php
├── TableExcelOptions.php
├── Concerns/
│   └── HasTableExcel.php
├── Columns/
├── Actions/
├── Filters/
└── Support/
    ├── ColumnGroupPreset.php
    └── StickyColumns.php
```

Para componentes de coluna reutilizáveis que não dependem só do preset, a pasta preferencial é:

```text
app/Filament/Tables/Columns/
```

Exemplo para inline edit:

```text
app/Filament/Tables/Columns/
└── InlineEditColumn.php
```

Se surgirem variações reais do mesmo padrão, evoluir para algo como:

```text
app/Filament/Tables/Columns/Inline/
├── InlineEditColumn.php
├── InlineSelectColumn.php
├── InlineTextInputColumn.php
└── Support/
```

### Mapa da estrutura sugerida

#### `app/Filament/Tables/TableExcel/`
Núcleo do padrão Table Excel. Aqui ficam apenas peças ligadas ao preset e à sua UX, e não componentes genéricos do projeto inteiro.

- `TableExcelPreset.php`
  - ponto central de composição do preset;
  - recebe a `Table` nativa e aplica convenções compartilhadas;
  - deve orquestrar a configuração, sem concentrar regra de negócio.

- `TableExcelOptions.php`
  - objeto pequeno de configuração;
  - reúne flags e presets de comportamento;
  - serve para deixar a ativação explícita e legível.

- `Concerns/`
  - traits pequenas e opcionais para reduzir repetição;
  - usar apenas quando houver repetição real entre `Resources`, `Pages` e `Widgets`.

- `Concerns/HasTableExcel.php`
  - trait opcional para helpers de ativação do preset;
  - não deve virar depósito de lógica transversal demais.

- `Columns/`
  - colunas específicas do padrão Table Excel;
  - usar para peças que dependem do preset, da UX avançada ou de integração com recursos do Table Excel;
  - não é o lugar de colunas genéricas do sistema inteiro.

- `Actions/`
  - actions compartilhadas do padrão Table Excel;
  - exemplos: abrir modal de filtros, abrir configuração de colunas, resetar preferências visuais.

- `Filters/`
  - filtros ou builders de filtros reutilizáveis dentro do contexto do preset;
  - bom lugar para schema de filtros compartilhados quando fizerem parte da experiência do Table Excel.

- `Support/`
  - classes técnicas de apoio;
  - usar para helpers, value objects, mapeamentos, resolvers e utilitários pequenos;
  - evitar colocar UI principal ou regra de negócio aqui.

- `Support/ColumnGroupPreset.php`
  - apoio técnico para estilização, organização ou compatibilidade dos `ColumnGroup` com o preset;
  - não substitui `ColumnGroup` nativo do Filament.

- `Support/StickyColumns.php`
  - concentra lógica técnica de sticky/frozen columns;
  - deve ficar isolado porque é uma área mais frágil e mais sensível a mudanças internas do Filament.

#### `app/Filament/Tables/Columns/`
Biblioteca de colunas reutilizáveis do projeto, independentes de uma tabela específica e não necessariamente presas ao preset Table Excel.

- usar quando a coluna puder ser reaproveitada em mais de um `Resource`, `Page` ou `Widget`;
- preferir esta pasta para componentes genéricos como `InlineEditColumn`, `ProgressColumn` e similares;
- se a coluna depender fortemente do Table Excel, avaliar se ela pertence melhor em `TableExcel/Columns/`.

#### `app/Filament/Tables/Columns/Inline/`
Subpasta opcional para organizar famílias de colunas inline quando surgirem variações reais.

- `InlineEditColumn.php`
  - abstração base ou ponto de entrada principal da experiência inline;
  - ideal para padronizar API e comportamento visual.

- `InlineSelectColumn.php`
  - variante focada em `Select` inline.

- `InlineTextInputColumn.php`
  - variante focada em edição textual inline.

- `Support/`
  - helpers técnicos internos da família de inline edit;
  - exemplos: normalização de estado, payloads, integração com feedback visual.

### Responsabilidades

#### `TableExcelOptions`
Objeto simples com flags de comportamento:

- `dense`
- `excelStyle`
- `filtersModal`
- `columnManager`
- `stickyHeader`
- `stickyActionsColumn`
- `groupedColumns`

Exemplo:

```php
TableExcelOptions::make()
    ->dense()
    ->excelStyle()
    ->filtersModal()
    ->columnManager()
    ->stickyHeader()
    ->stickyActionsColumn()
    ->groupedColumns();
```

#### `TableExcelPreset`
Aplica comportamento comum em cima do `Table` nativo:

- classes CSS escopadas;
- posição de actions antes das células;
- defaults de UX para tabelas densas;
- integração com modal de filtros;
- integração com configuração de colunas;
- suporte a sticky/frozen;
- helpers para visual de grupos de coluna.

#### `HasTableExcel`
Trait opcional para reduzir repetição em `Resources`, `Pages` e `Widgets` que ativarem o preset.

---

## Como ativar por tabela

### Resource

```php
public static function table(Table $table): Table
{
    return TableExcelPreset::apply(
        table: $table,
        options: TableExcelOptions::make()
            ->dense()
            ->excelStyle()
            ->filtersModal()
            ->columnManager(),
    )
        ->columns(static::columns())
        ->filters(static::filters())
        ->recordActions(static::actions());
}
```

### Page

```php
public function table(Table $table): Table
{
    return TableExcelPreset::apply(
        table: $table,
        options: TableExcelOptions::make()
            ->dense()
            ->excelStyle()
            ->filtersModal()
            ->columnManager(),
    )
        ->query($this->getTableQuery())
        ->columns($this->getTableColumns())
        ->filters($this->getTableFilters())
        ->recordActions($this->getTableActions());
}
```

### Widget

Mesmo princípio: só ativa quando precisar.

---

## O que vai para o núcleo reutilizável

Tudo que for comum entre várias tabelas:

- visual denso estilo Excel;
- sticky header;
- actions na primeira coluna;
- modal de filtros;
- modal/configuração de colunas;
- freeze/sticky de colunas;
- persistência de preferências visuais em sessão (ver seção abaixo);
- helpers genéricos para grupos de colunas;
- colunas inline genéricas reutilizáveis.

Exemplos de candidatos a consolidação:

- `app/Tables/Columns/ProgressColumn.php`
- `app/Tables/Columns/ProgressPercentageColumn.php`
- `app/Filament/Components/Tables/InlineEditColumn.php`

Esses itens devem migrar para algo como:

```text
app/Filament/Tables/Columns/
```

### Inline edit como componente isolado

Inline edit deve ser tratado como **componente/coluna custom reutilizável**, usado diretamente na definição da `Table`, e não como regra embutida dentro do preset ou dentro de uma tabela específica.

Objetivo desse componente:

- encapsular a UX de edição inline;
- padronizar markup, comportamento visual e interação;
- expor uma API consistente para uso em `Resources`, `Pages` e `Widgets`;
- permitir reutilização sem carregar regra de negócio junto.

Exemplo de uso esperado:

```php
InlineEditColumn::make('status')
    ->options(fn ($record) => ...)
    ->disabled(fn ($record) => ...);
```

O que deve ficar **no componente compartilhado**:

- renderização da célula;
- estado visual de edição;
- integração genérica com `Select`, `TextInput` ou outra entrada inline;
- hooks técnicos reutilizáveis;
- comportamento comum de loading, feedback e cancelamento;
- classes CSS e atributos necessários para a experiência visual.

O que deve ficar **no domínio da tabela**:

- quais campos podem ser editados;
- opções do select e dados do domínio;
- autorização contextual;
- validação contextual;
- regras de persistência;
- efeitos colaterais e regras de negócio após salvar.

Pasta recomendada para a primeira implementação compartilhada:

```text
app/Filament/Tables/Columns/InlineEditColumn.php
```

Se houver mais de um tipo real de edição inline, quebrar em subpasta:

```text
app/Filament/Tables/Columns/Inline/
```

Regra prática:

- se ainda existe só um caso local e acoplado ao contexto de `Obras`, pode nascer perto de `Obras`;
- se já for claramente parte do padrão Table Excel desde o início, pode nascer direto em `app/Filament/Tables/Columns/`.

---

## O que continua no domínio da tabela

Tudo que for específico do contexto:

- query;
- eager loading;
- filtros específicos do recurso;
- opções de select do domínio;
- actions do domínio;
- colunas dinâmicas do domínio;
- autorização;
- regras de atualização inline.

Exemplo em `Obras`:

- `ObrasColumnFilters` continua no domínio de Obras;
- geração dinâmica de `Pontos de Atenção` continua em `Obras`;
- regras de permissão por setor/cargo continuam em `Obras`.

---

## Persistência de preferências visuais

As preferências visuais da tabela (colunas visíveis, ordem de colunas, densidade, filtros aplicados, ordenação) devem ser persistidas **via sessão do Laravel**, e não em `localStorage` nem em banco.

Motivação:

- preferências são estado de UI, não dado de domínio;
- devem sobreviver entre requisições dentro da mesma sessão do usuário, mas não precisam durar para sempre;
- expiram naturalmente ao deslogar, evitando crescimento indefinido de dados de UI;
- evitam acoplar o preset a `localStorage` e aos problemas de sincronização entre abas/dispositivos.

### Contrato

Centralizar o acesso à sessão em um único ponto do preset:

```text
app/Filament/Tables/TableExcel/Support/TableExcelPreferences.php
```

API mínima esperada:

```php
TableExcelPreferences::get(string $tableKey, string $pref, mixed $default = null): mixed;
TableExcelPreferences::put(string $tableKey, string $pref, mixed $value): void;
TableExcelPreferences::forget(string $tableKey): void;
```

Internamente, a classe deve escrever em uma chave namespaced da sessão, por exemplo:

```text
table-excel.{userId}.{tableKey}.{pref}
```

### Regras

- **`tableKey` obrigatório e único por tabela**: cada `Resource`, `Page` ou `Widget` que ativar o preset deve fornecer um identificador estável (ex.: `obras.list`, `projetos.list`). Sem `tableKey`, o preset não persiste nada.
- **Whitelist de chaves**: apenas preferências conhecidas podem ir para a sessão (ex.: `visible_columns`, `column_order`, `density`, `filters`, `sort`). Qualquer outra chave deve ser rejeitada pelo `TableExcelPreferences`.
- **Somente estado de UI**: nunca persistir dados do domínio, IDs de registros editados, payloads de formulário ou qualquer coisa sensível.
- **Reset explícito**: o preset deve oferecer uma action "Resetar preferências" que chama `forget($tableKey)`.
- **Escopo por usuário**: as chaves devem ser prefixadas pelo ID do usuário autenticado para evitar vazamento entre sessões compartilhadas.

### Atenção ao driver de sessão

O driver `cookie` tem limite prático de ~4 KB por payload. Tabelas com muitas colunas e filtros podem estourar esse limite rapidamente.

Antes de ligar a persistência em produção:

- verificar `config/session.php` e confirmar que o driver é `database`, `redis` ou `file`;
- se o projeto usa `cookie`, migrar o driver antes de adotar a persistência do Table Excel;
- documentar esse requisito na ativação do preset.

---

## Groups / Sections de colunas

Não reinventar isso fora do Filament.

Usar `ColumnGroup` nativo:

```php
ColumnGroup::make('INFORMAÇÕES DO PROJETO', [
    TextColumn::make('codigo'),
    TextColumn::make('sigla'),
    TextColumn::make('nova_sigla'),
    TextColumn::make('nome'),
    TextColumn::make('marca'),
    TextColumn::make('pipe_land'),
    SelectColumn::make('status'),
]);
```

O preset só deve:

- estilizar os grupos;
- ajudar na exibição/ocultação quando aplicável;
- manter compatibilidade com o gerenciamento de colunas.

---

## O que evitar

### Anti-padrões

1. sobrescrever a tabela global do Filament;
2. criar um novo sistema paralelo de tabelas;
3. duplicar engine de filtros/colunas do Filament;
4. centralizar UI, query, cache, auth e domínio num único arquivo gigante;
5. usar CSS global agressivo em `.fi-ta-*` sem escopo;
6. promover abstrações genéricas sem responsabilidade clara.

---

## Estratégia de views e CSS

### Preferência

1. usar API nativa do Filament;
2. aplicar preset com classes escopadas;
3. usar partials pequenos para comportamentos complementares;
4. só criar Blade compartilhado maior se houver necessidade real em mais de uma tabela.

### Escopo visual

Sempre escopar por classe própria, por exemplo:

```php
->extraAttributes(['class' => 'gs-table-excel'])
```

No CSS:

```css
.gs-table-excel .fi-ta-table {
    /* estilos do Table Excel */
}
```

Isso evita contaminar tabelas comuns do projeto.

---

## Plano de implementação

### Fase 1 — extrair o que é genérico da `Obras`

Separar da implementação atual:

- visual denso;
- sticky/frozen;
- actions na primeira coluna;
- base do modal de filtros;
- base do modal/configuração de colunas.

### Fase 2 — criar o núcleo reutilizável do Table Excel

Criar:

- `TableExcelPreset`
- `TableExcelOptions`
- estrutura de `Columns/`, `Actions/`, `Filters/` compartilháveis.

### Fase 3 — adaptar `ObrasTable`

`ObrasTable` passa a usar o preset, mantendo no domínio apenas o que é específico de Obras.

### Fase 4 — validar em uma segunda tabela real

Aplicar em outro `Resource` ou `Page` antes de generalizar mais.

Regra: após validar em outra tabela real, ajustar o escopo do núcleo com base no que realmente permaneceu genérico.

---

## Regras de governança

1. O Table Excel é sempre **opt-in**.
2. Nenhum `Resource` é obrigado a usar esse preset.
3. Shared components do padrão podem nascer desde o início em `app/Filament/Tables/*`, desde que sejam claramente genéricos e não carreguem regra de negócio.
4. Regra de negócio não fica no preset.
5. Se uma `*Table.php` crescer demais, quebrar em `Columns/`, `Filters/` e `Actions/`.

### Regra de modularização

`Resources`, `Pages`, `Widgets` e classes `*Table.php` devem permanecer pequenos e atuar como **orquestradores** da configuração da tabela.

Eles não devem concentrar no mesmo arquivo:

- definição extensa de colunas;
- filtros complexos;
- actions demais;
- autorização contextual;
- persistência;
- regra de negócio.

Sempre que uma classe começar a acumular responsabilidades, extrair por responsabilidade:

- `Columns/` para colunas reutilizáveis;
- `Filters/` para filtros e schema de filtros;
- `Actions/` para ações reutilizáveis ou agrupamentos de actions;
- `Support/` para helpers técnicos e objetos de apoio;
- classes de domínio/serviço para regra de negócio, persistência e efeitos colaterais.

Regra prática:

- se a leitura da tabela deixar de ser imediata, quebrar em arquivos menores;
- se houver blocos independentes de colunas, filtros ou actions, extrair antes de continuar crescendo a classe;
- `Resource`, `Page`, `Widget` e `*Table.php` devem compor a solução, não virar o lugar onde toda a lógica mora.

---

## Resumo executivo

A solução recomendada para o projeto é:

> transformar a experiência da `Obras List` em um **preset Table Excel opt-in**, em cima do `Table` nativo do Filament, sem override global.

Assim, o projeto ganha:

- padronização;
- extensibilidade;
- menor acoplamento;
- menor risco em upgrades do Filament;
- reaproveitamento real entre `Resources`, `Pages` e `Widgets`.
