# Spndly Backend API

Backend API do Spndly, construido com Laravel 12, PHP 8.4 e autenticacao via Laravel Passport (OAuth2).

## Stack

- **PHP** 8.4
- **Laravel** 12
- **Laravel Passport** 13 (OAuth2)
- **PostgreSQL**
- **PHPUnit** 11

## Setup

```bash
# Instalar dependencias
composer install

# Copiar .env
cp .env.example .env

# Gerar chave
php artisan key:generate

# Rodar migrations
php artisan migrate

# Criar client OAuth (Password Grant)
php artisan passport:client --password

# Rodar testes
php artisan test --compact
```

O projeto usa **Laravel Herd** para servir localmente. A URL padrao e `https://spndly-backend.test`.
