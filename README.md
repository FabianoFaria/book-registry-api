# Book Registry API

A JSON REST API in Laravel where users log in with a password, receive a token, and manage **only their own** books (create, list, view, update, delete).

- **Stack:** PHP 8.3+, Laravel 13, Laravel Sanctum, SQLite (default) or MySQL
- **Auth:** Bearer tokens (Sanctum personal access tokens)
- **Tests:** 43 PHPUnit tests (feature + unit), all against an in-memory SQLite database

## Assumptions

- No registration: users are seeded (register is a small extension).
- Only name is required; isbn and value are optional.
- ISBN: 10 or 13 digits, no hyphens or X; stored as string; unique per user.
- Value: >= 0, max 2 decimals, DECIMAL(10,2), returned as string.
- Another user's book returns 404 to avoid revealing which IDs exist.

## Key decisions
- Sanctum personal access tokens: hashed at rest, per-device logout.
- Form Requests (validation), Policy (ownership), API Resources (output contract).
- Books always created via $user->books(): user_id never comes from input.
- DB unique index (user_id, isbn) backs the validation rule.
- Versioned routes (/api/v1), paginated lists, login throttled (5/min).

## Growing this into a larger application
- Business logic moves to Action classes once create/update do more than save.
- Add token expiry + scheduled pruning; roles via policies or spatie/laravel-permission.
- Search/filtering with indexes; full-text or Scout when data grows.
- Cache per-user lists, queue slow work, Redis for cache/rate limits.
- Stateless tokens allow horizontal scaling behind a load balancer.
- OpenAPI docs and CI (tests + Pint) on every push.

---

## 1. Setup

```bash
git clone https://github.com/FabianoFaria/book-registry-api
cd book-registry-api
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve

Demo login: demo@example.com / password
Run tests: php artisan test
MySQL: set DB_CONNECTION=mysql and DB_* in .env, then migrate.
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

## 2. API reference

Base URL: `/api/v1`. Every response is JSON. Send `Content-Type: application/json` for request bodies. Protected routes need `Authorization: Bearer <token>`.

| Method      | Path            | Auth | Description                    | Success |
|-------------|-----------------|------|--------------------------------|---------|
| POST        | `/login`        | –    | Exchange email+password for a token | 200 |
| POST        | `/logout`       | ✔    | Revoke the token used for this request | 204 |
| GET         | `/me`           | ✔    | Current user                   | 200 |
| GET         | `/books`        | ✔    | List my books (paginated)      | 200 |
| POST        | `/books`        | ✔    | Create a book                  | 201 |
| GET         | `/books/{id}`   | ✔    | Show one of my books           | 200 |
| PUT / PATCH | `/books/{id}`   | ✔    | Update one of my books (partial) | 200 |
| DELETE      | `/books/{id}`   | ✔    | Delete one of my books         | 204 |

### Book fields

| Field   | Type                | Required | Rules |
|---------|---------------------|----------|-------|
| `name`  | string              | **yes**  | max 255 chars |
| `isbn`  | string or `null`    | no       | digits only, exactly 10 or 13 digits; unique **per user** |
| `value` | number/string or `null` | no   | ≥ 0, max 2 decimal places, ≤ 99,999,999.99 |

### Examples

**Log in**

```bash
curl -X POST http://127.0.0.1:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password","device_name":"curl"}'
```

```json
{
  "token": "1|Xyz...",
  "token_type": "Bearer",
  "expires_at": "2026-10-30T18:00:00+00:00",
  "user": { "id": 1, "name": "Test User", "email": "test@example.com" }
}
```

**Create a book**

```bash
curl -X POST http://127.0.0.1:8000/api/v1/books \
  -H "Authorization: Bearer 1|Xyz..." \
  -H "Content-Type: application/json" \
  -d '{"name":"Clean Code","isbn":"9780132350884","value":39.9}'
```

```json
{
  "data": {
    "id": 6,
    "name": "Clean Code",
    "isbn": "9780132350884",
    "value": "39.90",
    "created_at": "2026-10-23T18:33:13+00:00",
    "updated_at": "2026-10-23T18:33:13+00:00"
  }
}
```

**List books:** `GET /api/v1/books?per_page=20&page=2` returns `{ "data": [...], "links": {...}, "meta": {...} }`.

### Error responses

| Status | When | Body |
|--------|------|------|
| 401 | Missing, invalid, expired or revoked token | `{"message":"Unauthenticated."}` |
| 404 | Book doesn't exist **or belongs to someone else** | `{"message":"Not found."}` |
| 422 | Validation failed, or wrong login credentials | `{"message":"...","errors":{"field":["..."]}}` |
| 429 | More than 5 login attempts per minute | `{"message":"Too Many Attempts."}` |

---