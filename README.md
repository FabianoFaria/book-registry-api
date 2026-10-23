# Book Registry API

A JSON REST API in Laravel where users log in with a password, receive a token, and manage **only their own** books (create, list, view, update, delete).

- **Stack:** PHP 8.3+, Laravel 13, Laravel Sanctum, SQLite (default) or MySQL
- **Auth:** Bearer tokens (Sanctum personal access tokens)
- **Tests:** 43 PHPUnit tests (feature + unit), all against an in-memory SQLite database

---

## 1. Setup

```bash
git clone <repo-url> book-registry-api
cd book-registry-api
composer install
cp .env.example .env
php artisan key:generate
```
### Database

**SQLite (default, zero config):**

```bash
touch database/database.sqlite          # Windows PowerShell: ni database/database.sqlite
php artisan migrate --seed
```

**MySQL:** edit `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=book_registry
DB_USERNAME=root
DB_PASSWORD=secret
```

then `php artisan migrate --seed`.

### Run

```bash
php artisan serve        # http://127.0.0.1:8000
```

### Demo users (created by the seeder)

| Email               | Password   | Books |
|---------------------|------------|-------|
| `test@example.com`  | `password` | 3     |
| `demo@example.com`  | `password` | 5     |

Use the second user to check that one user can't see the other's books.