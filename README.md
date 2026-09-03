<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Racster

Racster is a Laravel 12 application for managing sports training (timetable, coaches, clients, subscriptions and payments).

## Local development

Requirements: PHP 8.2+, Composer, Node.js, Docker.

```bash
composer install
npm install
docker compose up -d        # MySQL on localhost:3307 (racster/racster, db: racster)
cp .env.example .env        # then set DB_HOST=127.0.0.1, DB_PORT=3307
php artisan key:generate
php artisan migrate
php artisan db:seed         # creates admin@racster.com / secret + demo users
npm run build
php artisan serve           # http://localhost:8000
```

## Deployment / Release

The application is deployed by running the release script **on the server**.
It pulls the latest state of the `master` branch and performs all release
steps in the correct order:

```bash
cd /path/to/racster
bash scripts/release.sh
```

What it does, in order:

1. Preflight checks (`.env` and lock files present, `php`/`composer` available)
2. Enables maintenance mode (disabled again on exit, even on failure)
3. `git fetch` + fast-forward-only pull of `master` (fails instead of merging if the server copy has diverged)
4. `composer install --no-dev --optimize-autoloader` — installs exactly what `composer.lock` pins
5. `npm ci` + `npm run build` — installs exactly what `package-lock.json` pins; **lock files are never modified** by the script, avoiding future merge conflicts
6. `php artisan migrate --force`
7. `php artisan storage:link` (if missing)
8. Clears config/route/view/cache, rebuilds the compiled views
9. `php artisan queue:restart`

Options:

```bash
BRANCH=main bash scripts/release.sh   # release a different branch
SKIP_BUILD=1 bash scripts/release.sh  # skip npm ci / npm run build
SKIP_MAINTENANCE=1 bash scripts/release.sh
```

Notes:

- The server needs `php`, `composer` and `npm` in the PATH of the user running the script.
- Config and route caching are intentionally **not** used: the codebase calls `env()` at runtime (e.g. Stripe keys) and defines closure routes, both of which break with `config:cache` / `route:cache`.
- If the script is run as root while files are owned by another user, git may refuse to operate ("dubious ownership"). Run the script as the user who owns the project files.
- Server `.env` is never touched by the script. Make sure it has `APP_ENV=production`, `APP_DEBUG=false` and real credentials.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
