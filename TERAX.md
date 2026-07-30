# TERAX Overview

This project is a Laravel application, identified as the "Laravel Starter Kit for Livewire," focused on building dynamic Livewire-powered applications with a focus on clean actions and robust data handling.

## 🚀 Setup & Commands

**Installation/Setup:**
Use Composer to install dependencies and set up initial configuration:
`composer install`

**Development:**
To run the local development server, queue listener, and Vite assets concurrently:
`npm run dev` (or use `php artisan serve` for basic setup)

**Testing & Linting:**
*   **Linting:** `pint --parallel`
*   **Code Check/Test:** `test`
*   **CI Check:** `ci:check`

**Entry Points:**
The primary entry point is typically the web server running on `php artisan serve`. Application routes are defined in the `routes` directory. Controller logic resides in `app/Http/Controllers`.

## 🏛️ Architecture Overview

The application follows the standard Laravel MVC pattern, extended by Livewire components and action-based logic:
*   **Core Logic:** Handled within the `app/` directory (Models, Actions, Concerns).
*   **Routing:** Defined in `routes/`.
*   **Configuration:** Managed via files in `config/`.
*   **Assets & Frontend:** Compiled using Vite via `vite.config.js`.
*   **Business Logic:** Specific domain logic is often separated into action classes (e.g., `app/Actions`).

## 📜 Conventions & Patterns

1.  **PSR-4 Autoloading:** Standard PSR-4 mapping under `App\` and custom namespaces like `Function\`.
2.  **Action Pattern:** Business operations are encapsulated in dedicated Action classes (e.g., `CreateAction.php`), promoting separation of concerns.
3.  **Laravel Ecosystem:** Relies heavily on Laravel features, including Eloquent ORM and Livewire.
4.  **Tooling Integration:** Uses tools like PHP-CS-Fixer (`pint`) and Pest for testing, indicating a focus on code hygiene and test-driven development.

## 🗺️ Key Directories

*   `app/`: Application source code (Controllers, Models, Actions, Concerns).
*   `routes/`: API and web routing definitions.
*   `config/`: Application configuration files.
*   `database/`: Migrations and seeders.
*   `storage/`: Application-generated files (logs, cache).
*   `tests/`: Unit and feature tests.
*   `vendor/`: Composer dependencies.

Next steps involve reviewing the contents of `routes` and `app/` to map specific entry points.