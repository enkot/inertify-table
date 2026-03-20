# Laravel + Inertia + shadcn-vue Example

This folder contains copy-paste example files showing how to use this package in a Laravel app with a custom UI built from `shadcn-vue` components.

## What is included

- `app/Http/Controllers/UserIndexController.php`
- `routes/web.php`
- `resources/js/Pages/Users/Index.vue`
- `database/seeders/UserSeeder.php`

## Quick setup (inside a Laravel app)

1. Install backend dependencies:

```bash
composer require inertiajs/inertia-laravel taras/inertia-headless-table
```

2. Install frontend dependencies:

```bash
npm install @inertiajs/vue3 @taras/inertia-headless-table-vue
```

3. Install shadcn-vue and add the components used by the example:

```bash
npx shadcn-vue@latest init
npx shadcn-vue@latest add button card input select table
```

4. Copy the files from this `examples/laravel-vue-shadcn` folder into your Laravel app.

5. Seed demo users:

```bash
php artisan db:seed --class=UserSeeder
```

6. Open `/users`.

## Notes

- The table logic (`pagination`, `sorting`, `filtering`) is fully headless and comes from `useHeadlessTable`.
- Filters in `Index.vue` are rendered from `usersTable.filters` metadata (text/select/boolean), so you can add server-side filters without rewriting the page.
- The example uses provider/inject composition components: `HeadlessTableProvider`, `HeadlessTableFilters`, `HeadlessTableSorting`, and `HeadlessTablePagination`.
- All visuals in `Index.vue` are built with shadcn-vue components, so you can redesign freely.
- The example expects a `users` table with at least: `name`, `email`, `role`, `password`, and timestamps.
