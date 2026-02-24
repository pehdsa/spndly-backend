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

## Autenticacao

A autenticacao usa **Laravel Passport** com Password Grant. Todas as rotas autenticadas esperam o header:

```
Authorization: Bearer {access_token}
```

### Enums

| Enum | Valores |
|---|---|
| `UserRole` | `ADMIN`, `CLIENT` |
| `UserStatus` | `ACTIVE`, `BLOCKED` |
| `InvitationStatus` | `VALID`, `USED`, `EXPIRED` |

---

## Rotas

**Base URL:** `https://spndly-backend.test`

### Auth

#### Login

```
POST /oauth/token
```

```json
{
  "grant_type": "password",
  "client_id": "your-client-id",
  "client_secret": "your-client-secret",
  "username": "joao@email.com",
  "password": "senha123",
  "scope": "*"
}
```

**Response 200:**

```json
{
  "token_type": "Bearer",
  "expires_in": 31536000,
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOi...",
  "refresh_token": "def502003a1b2c3d4e5f..."
}
```

#### Refresh Token

```
POST /oauth/token/refresh
```

```json
{
  "grant_type": "refresh_token",
  "refresh_token": "def502003a1b2c3d4e5f...",
  "client_id": "your-client-id",
  "client_secret": "your-client-secret",
  "scope": "*"
}
```

**Response 200:** mesmo formato do login.

#### Registro (via convite)

```
POST /api/v1/auth/register
```

```json
{
  "token": "abc123...64chars",
  "name": "Joao Silva",
  "password": "SenhaForte123!",
  "password_confirmation": "SenhaForte123!",
  "client_id": "your-client-id",
  "client_secret": "your-client-secret"
}
```

Validacao:
- `token`: obrigatorio, string, exatamente 64 caracteres
- `name`: obrigatorio, string, max 255
- `password`: obrigatorio, confirmado (`password_confirmation`)
- `client_id` / `client_secret`: obrigatorios

**Response 201:**

```json
{
  "user": {
    "id": 5,
    "name": "Joao Silva",
    "email": "joao@email.com",
    "phone_number": null,
    "role": "CLIENT",
    "status": "ACTIVE",
    "viewed_at": null,
    "email_verified_at": null,
    "created_at": "2026-02-24T12:00:00.000000Z",
    "updated_at": "2026-02-24T12:00:00.000000Z"
  },
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOi...",
  "refresh_token": "def502003a1b2c3d4e5f...",
  "expires_in": 31536000
}
```

> **Nota:** response nao vem envelopado em `data`.

#### Logout

Requer: `auth:api`

```
POST /api/v1/auth/logout
Authorization: Bearer {access_token}
```

**Response 200:**

```json
{
  "message": "Logged out successfully."
}
```

> **Nota:** response nao vem envelopado em `data`.

#### Usuario autenticado

Requer: `auth:api`, `active`, `track.activity`

```
GET /api/v1/auth/me
Authorization: Bearer {access_token}
```

**Response 200:**

```json
{
  "data": {
    "id": 1,
    "name": "Joao Silva",
    "email": "joao@email.com",
    "phone_number": "+5511999999999",
    "role": "ADMIN",
    "status": "ACTIVE",
    "viewed_at": "2026-02-24T10:00:00.000000Z",
    "email_verified_at": "2026-01-01T00:00:00.000000Z",
    "created_at": "2026-01-01T00:00:00.000000Z",
    "updated_at": "2026-02-24T10:00:00.000000Z"
  }
}
```

---

### Admin - Gerenciamento de Usuarios

Todas as rotas abaixo requerem: `auth:api`, `active`, `track.activity`, `can:admin`

#### Listar usuarios

```
GET /api/v1/users?search=joao
Authorization: Bearer {access_token}
```

**Response 200:**

```json
{
  "data": [
    {
      "id": 1,
      "name": "Joao Silva",
      "email": "joao@email.com",
      "phone_number": "+5511999999999",
      "role": "ADMIN",
      "status": "ACTIVE",
      "viewed_at": "2026-02-24T10:00:00.000000Z",
      "email_verified_at": "2026-01-01T00:00:00.000000Z",
      "created_at": "2026-01-01T00:00:00.000000Z",
      "updated_at": "2026-02-24T10:00:00.000000Z"
    }
  ]
}
```

#### Ver usuario

```
GET /api/v1/users/{id}
Authorization: Bearer {access_token}
```

**Response 200:**

```json
{
  "data": {
    "id": 1,
    "name": "Joao Silva",
    "email": "joao@email.com",
    "phone_number": "+5511999999999",
    "role": "ADMIN",
    "status": "ACTIVE",
    "viewed_at": null,
    "email_verified_at": "2026-01-01T00:00:00.000000Z",
    "created_at": "2026-01-01T00:00:00.000000Z",
    "updated_at": "2026-01-01T00:00:00.000000Z"
  }
}
```

#### Deletar usuario

```
DELETE /api/v1/users/{id}
Authorization: Bearer {access_token}
```

**Response 200:**

```json
{
  "data": {
    "message": "User deleted successfully."
  }
}
```

#### Bloquear usuario

```
POST /api/v1/users/{id}/block
Authorization: Bearer {access_token}
```

**Response 200:**

```json
{
  "data": {
    "message": "User blocked successfully."
  }
}
```

#### Desbloquear usuario

```
POST /api/v1/users/{id}/unblock
Authorization: Bearer {access_token}
```

**Response 200:**

```json
{
  "data": {
    "message": "User unblocked successfully."
  }
}
```

#### Alterar role do usuario

```
PATCH /api/v1/users/{id}/role
Authorization: Bearer {access_token}
Content-Type: application/json
```

```json
{
  "role": "CLIENT"
}
```

Valores: `ADMIN` | `CLIENT`

**Response 200:**

```json
{
  "data": {
    "id": 1,
    "name": "Joao Silva",
    "email": "joao@email.com",
    "phone_number": "+5511999999999",
    "role": "CLIENT",
    "status": "ACTIVE",
    "viewed_at": null,
    "email_verified_at": "2026-01-01T00:00:00.000000Z",
    "created_at": "2026-01-01T00:00:00.000000Z",
    "updated_at": "2026-02-24T12:00:00.000000Z"
  }
}
```

---

### Admin - Gerenciamento de Convites

Todas as rotas abaixo requerem: `auth:api`, `active`, `track.activity`, `can:admin`

#### Listar convites

```
GET /api/v1/invitations?search=email@test.com
Authorization: Bearer {access_token}
```

**Response 200:**

```json
{
  "data": [
    {
      "id": 1,
      "email": "convidado@email.com",
      "token": "a1b2c3d4...64chars",
      "role": "CLIENT",
      "status": "VALID",
      "expires_at": "2026-03-01T00:00:00.000000Z",
      "used_at": null,
      "invited_by": 1,
      "created_at": "2026-02-24T00:00:00.000000Z"
    }
  ]
}
```

Valores de `status`: `VALID` | `EXPIRED` | `USED`

#### Criar convites (batch)

```
POST /api/v1/invitations
Authorization: Bearer {access_token}
Content-Type: application/json
```

```json
{
  "emails": ["user1@email.com", "user2@email.com"],
  "role": "CLIENT"
}
```

Validacao:
- `emails`: obrigatorio, array, max 50 itens
- `emails.*`: string, email valido, max 255
- `role`: obrigatorio, `ADMIN` | `CLIENT`

**Response 201:**

```json
{
  "data": {
    "sent": [
      {
        "id": 2,
        "email": "user1@email.com",
        "token": "x1y2z3...64chars",
        "role": "CLIENT",
        "status": "VALID",
        "expires_at": "2026-02-26T00:00:00.000000Z",
        "used_at": null,
        "invited_by": 1,
        "created_at": "2026-02-24T00:00:00.000000Z"
      }
    ],
    "failed": [
      {
        "email": "user2@email.com",
        "reason": "A user with this email address already exists."
      }
    ]
  }
}
```

O convite expira em **48 horas**. Um e-mail e enviado automaticamente com o link `{FRONTEND_URL}/register?token={token}`.

#### Deletar convite

```
DELETE /api/v1/invitations/{id}
Authorization: Bearer {access_token}
```

**Response 200:**

```json
{
  "data": {
    "message": "Invitation deleted successfully."
  }
}
```

---

### Validar token de convite (rota publica)

Throttle: 10 requests/minuto.

```
GET /api/v1/invitations/validate?token=abc123...64chars
```

**Response 200:**

```json
{
  "data": {
    "id": 1,
    "email": "convidado@email.com",
    "token": "abc123...64chars",
    "role": "CLIENT",
    "status": "VALID",
    "expires_at": "2026-03-01T00:00:00.000000Z",
    "used_at": null,
    "invited_by": 1,
    "created_at": "2026-02-24T00:00:00.000000Z"
  }
}
```

---

## Respostas de Erro

As respostas de erro seguem o padrao do Laravel:

**422 - Validacao:**

```json
{
  "message": "The role field is required.",
  "errors": {
    "role": ["A role is required."]
  }
}
```

**401 - Nao autenticado:**

```json
{
  "message": "Unauthenticated."
}
```

**403 - Nao autorizado:**

```json
{
  "message": "This action is unauthorized."
}
```

**404 - Nao encontrado:**

```json
{
  "message": "No query results for model [App\\Models\\User] 999"
}
```
