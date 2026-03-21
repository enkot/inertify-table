# inertify/table

Headless table tooling for Laravel + Inertia + Vue with:

- Pagination
- Sorting
- Filtering
- Multi-table support on one page (query keys are table-scoped)
- No UI opinions (you render your own markup)

## Install

```bash
composer require inertify/table
```

If you publish config:

```bash
php artisan vendor:publish --tag=inertify/table-config
```

Optional Vue package build output:

```bash
npm install @inertify/table-vue
```

## Publish to Packagist

1. Make sure your package name in `composer.json` is final (already set to `inertify/table`).
2. Commit and push `main` to a **public** Git repository.
3. Create a semantic version tag and push it:

```bash
git tag v1.0.0
git push origin v1.0.0
```

4. Sign in to [Packagist](https://packagist.org), click **Submit**, and paste your repository URL.
5. In repository settings (GitHub/GitLab), add Packagist webhook so updates happen automatically.
6. After indexing, install with Composer:

```bash
composer require inertify/table
```

### Recommended release flow

- Merge changes to `main`
- Run tests (`composer test`)
- Tag release (`vX.Y.Z`)
- Push tag (`git push origin vX.Y.Z`)
- Verify package page on Packagist

## Publish Vue package to npm (automated)

This repository includes GitHub Actions workflow at `.github/workflows/publish-npm.yml`.

It publishes `@inertify/table-vue` when you push a tag `v*`.

### One-time setup

1. In npm org `inertify`, create a **granular access token** with publish permissions for `@inertify/table-vue` and 2FA bypass for automation.
2. In GitHub repo settings, add secret `NPM_TOKEN` with that token value.

### Release commands

```bash
npm version patch
git push origin main --follow-tags
```

The workflow validates that tag version matches `package.json` version, builds package, and publishes to npm.

## Laravel API

```php
use Inertia\Inertia;
use App\Models\User;
use Inertify\Table\Column;
use Inertify\Table\Filter;
use Inertify\\Table\\Table;

public function index()
{
    $table = Table::make('users')
        ->columns([
        Column::make('name')->sortable()->filterable(),
        Column::make('email')->sortable()->filterable(),
            Column::make('role')->filterable(),
        Column::make('created_at')->type('date')->sortable()->filterable(),
        ])
        ->allowedSorts(['name', 'email', 'created_at'])
      ->allowedFilters(['name', 'email', 'created_at'])
        ->defaultSort('-created_at')
        ->defaultPerPage(15)
        ->perPageOptions([15, 30, 50]);

    return Inertia::render('Users/Index', [
        ...$table->payload(
            query: User::query(),
            rowsKey: 'users',
            metaKey: 'usersTable'
        ),
    ]);
}
```

### Filter inference from column type

When `allowedFilters([...])` receives a string key, the package infers the filter type from `Column::type(...)`:

- `number` / `int` / `float` / `decimal` => `Filter::numberRange(...)`
- `date` / `datetime` / `timestamp` => `Filter::dateRange(...)`
- `boolean` / `bool` => `Filter::exact(...)`
- everything else => `Filter::partial(...)`

Use explicit `Filter::...` entries in `allowedFilters([...])` when you need custom behavior (for example `Filter::select(...)` with options or callback filters).

### Upgrade note: explicit to inferred filters

Before (explicit filters everywhere):

```php
$table = Table::make('users')
  ->columns([
    Column::make('id', 'ID'),
    Column::make('name', 'Name'),
    Column::make('created_at', 'Created'),
  ])
  ->allowedFilters([
    Filter::numberRange('id', 'id', 'ID'),
    Filter::partial('name', 'name', 'Name'),
    Filter::dateRange('created_at', 'created_at', 'Created'),
  ]);
```

After (inferred defaults):

```php
$table = Table::make('users')
  ->columns([
    Column::make('id', 'ID')->type('number'),
    Column::make('name', 'Name'),
    Column::make('created_at', 'Created')->type('date'),
  ])
  ->allowedFilters(['id', 'name', 'created_at']);
```

Keep using explicit `Filter::...` when you need custom filter behavior (for example `Filter::select(...)`, custom callback logic, or non-default matching rules).

### Inertia macro shortcut

The package registers a `Inertia::tablePayload(...)` macro:

```php
return Inertia::render('Users/Index', [
    ...Inertia::tablePayload(
        name: 'users',
        query: User::query(),
        configure: fn ($table) => $table
            ->allowedSorts(['name', 'email'])
            ->allowedFilters([Filter::partial('name')]),
        rowsKey: 'users',
        metaKey: 'usersTable',
    ),
]);
```

## Vue Headless API

### Composables-first (recommended)

```ts
import {
  useInertifyTable,
  useInertifyTableFilters,
  useInertifyTableSorting,
  useInertifyTablePagination,
  useInertifyTableSelection,
} from "@inertify/table-vue";

const table = useInertifyTable(props.usersTable, {
  only: ["users", "usersTable"],
});

const filters = useInertifyTableFilters(table);
const sorting = useInertifyTableSorting(table);
const pagination = useInertifyTablePagination(table);
const selection = useInertifyTableSelection(table);
```

Each composable also supports inject fallback when used inside `HeadlessTableProvider`:

```ts
const filters = useInertifyTableFilters();
const sorting = useInertifyTableSorting();
const pagination = useInertifyTablePagination();
const selection = useInertifyTableSelection();
```

### Provider/inject (optional)

```vue
<script setup lang="ts">
import {
  HeadlessTableProvider,
  HeadlessTableFilters,
  HeadlessTableSorting,
  HeadlessTablePagination,
} from "@inertify/table-vue";

defineProps<{ usersTable: any }>();
</script>

<template>
  <HeadlessTableProvider :meta="usersTable" :only="['users', 'usersTable']">
    <HeadlessTableFilters
      v-slot="{ filters, getFilterValue, setFilterValue, applyFilters }"
    >
      <!-- Render your inputs/selects from filters metadata -->
    </HeadlessTableFilters>

    <HeadlessTableSorting v-slot="{ toggleSort, isSortedBy, activeDirection }">
      <!-- Render sortable headers -->
    </HeadlessTableSorting>

    <HeadlessTablePagination
      v-slot="{ page, lastPage, previous, next, setPerPage, perPageOptions }"
    >
      <!-- Render pager controls -->
    </HeadlessTablePagination>
  </HeadlessTableProvider>
</template>
```

### Direct table API

```ts
import { useInertifyTable } from "@inertify/table-vue";

const table = useInertifyTable(props.usersTable, {
  only: ["users", "usersTable"],
});

table.toggleSort("name");
table.setFilter("role", "admin");
table.visit();

table.toggleRowSelected(1);
table.areAllRowsSelected([1, 2, 3]);
table.clearSelection();
```

### Row selection

Use `HeadlessTableSelection` for renderless row-selection state and helpers:

```vue
<HeadlessTableSelection
  v-slot="{
    isRowSelected,
    toggleRowSelected,
    toggleAllRowsSelected,
    selectionCount,
  }"
>
  <!-- Use these helpers to build checkbox/select-all UI -->
</HeadlessTableSelection>
```

Selection state is client-side and automatically clears when table meta is refreshed.

### Column-based head/cell rendering

`HeadlessTableHeads` and `HeadlessTableCells` support slot overrides by:

- Column name: `column-{key}` (example: `column-created_at`)
- Column type: `type-{type}` (example: `type-date`, `type-number`)

Precedence is: column-name slot → type slot → default slot.

Column type is resolved from `column.meta.type` first, then inferred from filter input (`date-range` => `date`, `number-range` => `number`).

### Renderless components

`HeadlessTable` and `HeadlessPagination` expose slot props only, so you can build any UI design system.

```vue
<HeadlessTable
  :meta="usersTable"
  v-slot="{ state, toggleSort, setFilter, visit }"
>
  <button @click="toggleSort('name')">Sort by name</button>
  <input :value="state.filters.name ?? ''" @input="setFilter('name', $event.target.value, { submit: false })" />
  <button @click="visit()">Apply</button>
</HeadlessTable>
```

## Example app (shadcn-vue)

A complete usage example with Laravel + Inertia + shadcn-vue components is available in:

- `examples/laravel-vue-shadcn`

## Query format

For table name `users`, the default query keys are:

- `users_page`
- `users_per_page`
- `users_sort` (`name` or `-name`)
- `users_filters[name]=...`

Range filters use nested `from` / `to` values:

- `users_filters[age][from]=18`
- `users_filters[age][to]=65`
- `users_filters[created_at][from]=2026-01-01`
- `users_filters[created_at][to]=2026-01-31`

`Filter::numberRange(...)` and `Filter::dateRange(...)` apply inclusive bounds (`>= from`, `<= to`).

Customize this in `config/inertify-table.php`.
