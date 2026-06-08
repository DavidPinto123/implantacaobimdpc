# CHANGELOG — Implantação BIM

> Este arquivo documenta todas as mudanças relevantes por dia. Replica em outras cópias do sistema aplicando cada item na sequência.

---

## 2026-05-26

### Banco de Dados

- **Migration** `add_duracao_dias_to_cronograma_fase_itens_table` — coluna `duracao_dias` (int nullable) na tabela `cronograma_fase_itens`.
- **Migration** `create_cronograma_fase_item_responsaveis_table` — tabela pivot `cronograma_fase_item_responsaveis` (item_id FK → cronograma_fase_itens, user_id FK → users).
- **Seeder** `ValoresPlanejamentoPermissionSeeder` — permissão `ver_valores_planejamento` criada e concedida aos roles `super_admin`, `admin`, `Gestor`, `PMO`, `Planejamento Estratégico`.

### Modelos

- **`App\Models\CronogramaFaseItem`** — relação `responsaveis()` (BelongsToMany → User, pivot `cronograma_fase_item_responsaveis`); campo `duracao_dias` adicionado ao `$fillable`.

### Controller / Livewire Page

- **`App\Filament\Pages\Cronograma`** — novos métodos públicos:
  - `adicionarResponsavelSubitem(int $itemId, int $userId)` — sincroniza responsável via `syncWithoutDetaching`.
  - `removerResponsavelSubitem(int $itemId, int $userId)` — remove responsável via `detach`.
  - `salvarDuracaoSubitem(int $itemId, ?int $valor)` — salva `duracao_dias` no item.
  - Eager load ampliado para incluir `responsaveis` em itens e filhos.
  - Variável `$usuarios` (lista de usuários) passada à view para dropdown de responsáveis.

### Views

#### `resources/views/filament/pages/cronograma.blade.php`

**Design Refresh:**
- Badge circular colorido (`cr-fase-num-badge`) para o número da fase na tabela.
- Status badge maior e mais arredondado (`cr-status-trigger`).
- Fonte geral da tabela aumentada (`cr-table`, `cr-table td`, `cr-table th`).
- Fundo de linha de fase mais proeminente por status de farol (`cr-fase-linha-verde/amarelo/vermelho`).
- Fonte maior nos subitens e no Gantt.

**7 Ajustes na tabela de planejamento:**
1. **Coluna Status** — largura padrão aumentada para 155 px; usa CSS custom property `--cr-col-status-w`.
2. **Resize manual de colunas** — `x-data` expandido com larguras para todas as 10 colunas; `startResize()` generalizado com `propMap`; persiste em `localStorage` (`cr:col:<nome>`).
3. **Toggle de colunas** — botão "Colunas" abre painel com checkboxes; visibilidade persistida em `localStorage` (`cr:cols:vis`); `x-show="cols.xxx"` em `<col>`, `<th>`, `<td>` de fase e `<td>` de subitens.
4. **Busca por fase** — campo de texto com ícone, `x-model="buscaTabela"`; cada fase renderiza num `<tbody>` próprio com `data-label` e `x-show` que filtra por `label.includes(busca)`.
5. **Filtro por status** — `<select>` preenchido com `StatusCronograma::cases()`; `x-model="filtroStatusTabela"`; `<tbody>` filtrado por `data-status`.
6. **Sobreposição Dependência/Comentários** — coluna Dependência migrou de `x-show="mostrarDeps"` (booleano global) para `x-show="cols.deps"` (painel de colunas); por padrão `deps: false` no estado inicial.
7. **CHANGELOG** — este arquivo criado; ver item 7 da seção de views abaixo.

**Estrutura de tbody:**
- Substituído único `<tbody>` por um `<tbody>` por fase com atributos `data-label` e `data-status` e `x-show` para busca/filtro.
- `colspan` das linhas de adição de subitem e subitem filho corrigido para `20` (comporta todas as colunas independentemente de visibilidade).

#### `resources/views/filament/pages/cronograma-subitem-table.blade.php`

- **Numeração hierárquica** — prefixo `$numPrefix` (ex.: `1.2`, `1.2.1`) exibido antes do título.
- **Coluna Valor** — exibida sob `@can('ver_valores_planejamento')`; `x-show="cols.valor"`.
- **Coluna Responsáveis** — tags clicáveis de responsáveis com remoção; dropdown para adicionar usuário; chama `adicionarResponsavelSubitem` / `removerResponsavelSubitem` via `$wire`.
- **Coluna Duração** — subitens folha: `<input type="number">` com `wire:change="salvarDuracaoSubitem"`; subitens pai: exibe soma dos filhos (read-only).
- **x-show em todas as tds** — `cols.planejado`, `cols.durplan`, `cols.realizado`, `cols.pct`, `cols.valor`, `cols.responsaveis`, `cols.deps`, `cols.comentarios` aplicados em cada `<td>` correspondente.

---

## Como replicar em outra cópia do sistema

1. Executar as migrations na nova instância:
   ```
   php artisan migrate
   ```
2. Executar o seeder de permissão:
   ```
   php artisan db:seed --class=ValoresPlanejamentoPermissionSeeder
   ```
3. Substituir os arquivos de view:
   - `resources/views/filament/pages/cronograma.blade.php`
   - `resources/views/filament/pages/cronograma-subitem-table.blade.php`
4. Substituir `app/Filament/Pages/Cronograma.php`.
5. Substituir `app/Models/CronogramaFaseItem.php`.
6. Limpar cache:
   ```
   php artisan optimize:clear
   ```
