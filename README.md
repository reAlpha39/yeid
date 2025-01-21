# YEID

## Project Setup

1. Open Task Scheduler
   Create a new Basic Task:

* Name: "Laravel Queue Worker"
* Trigger: "At startup"
* Action: Start a program
* Program: C:\path\to\php\php.exe
* Arguments: artisan queue:work --timeout=7200 --tries=3--max-jobs=500
* Start in: C:\path\to\your\laravel

2. Run below:
```sh
pnpm install
```

### Compile and Hot-Reload for Development

```sh
pnpm run dev
```

```sh
php artisan serve
```

### Type-Check, Compile and Minify for Production

```sh
pnpm run build
```
