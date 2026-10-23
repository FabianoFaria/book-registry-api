# Book Registry API

A JSON REST API in Laravel where users log in with a password, receive a token, and manage **only their own** books (create, list, view, update, delete).

- **Stack:** PHP 8.3+, Laravel 13, Laravel Sanctum, SQLite (default) or MySQL
- **Auth:** Bearer tokens (Sanctum personal access tokens)
- **Tests:** 43 PHPUnit tests (feature + unit), all against an in-memory SQLite database


