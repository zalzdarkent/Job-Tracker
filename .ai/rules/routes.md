---
paths:
  - 'resources/js/routes/**'
---

# Routes

## wayfinder:generate needs --with-form
The Wayfinder Vite plugin config uses formVariants: true, so generated route functions include `.form()`. When running the CLI manually, you MUST pass `--with-form`: `php artisan wayfinder:generate --with-form`. Without it, generated types lose `.form()` and tsc errors like "Property 'form' does not exist" appear in pages using `<Form {...store.form()} />`.
