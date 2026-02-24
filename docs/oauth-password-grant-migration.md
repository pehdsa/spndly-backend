# Migrar Auth de Personal Access Tokens para OAuth Password Grant

## Context

O fluxo de auth atual usa `$user->createToken('api')->accessToken` (Personal Access Tokens), que não fornece refresh tokens reais. Vamos migrar para o OAuth Password Grant do Passport, que retorna `access_token` + `refresh_token` via `/oauth/token`.

O frontend é uma **aplicação separada** (SPA) que chama o endpoint `/oauth/token` do Passport diretamente para login e refresh. O `client_id` e `client_secret` ficam no frontend (padrão para first-party apps com password grant).

O backend mantém endpoints custom apenas para:
- **Register** — lógica de invitation + emissão de token após criar user
- **Logout** — revogar access token + refresh token
- **Me** — retornar dados do user autenticado

---

## O que muda no backend

### 1. Remover `app/Http/Controllers/Api/V1/Auth/LoginController.php`

O login passa a ser feito diretamente via `POST /oauth/token` pelo frontend. O controller e o `LoginRequest` não são mais necessários.

### 2. Remover `app/Http/Controllers/Api/V1/Auth/RefreshController.php`

O refresh passa a ser feito diretamente via `POST /oauth/token` com `grant_type=refresh_token` pelo frontend.

### 3. `app/Http/Controllers/Api/V1/Auth/RegisterController.php`

**Antes:** Cria user + `$user->createToken('api')->accessToken`

**Depois:**
- Manter toda lógica de invitation (validação de token, criação de user)
- Após criar o user, usar `$user->createToken('api')` para emitir um Personal Access Token temporário para login imediato
- Retornar `user` + `token` (o frontend pode então usar o `/oauth/token` para obter tokens OAuth no próximo login)

**OU (alternativa recomendada):**
- Após criar o user, despachar internamente para `/oauth/token` com `grant_type=password` para já retornar tokens OAuth
- Usa `Request::create()` + `app()->handle()` para evitar overhead HTTP
- Retornar `user` + `access_token` + `refresh_token` + `expires_in`

### 4. `app/Http/Controllers/Api/V1/Auth/LogoutController.php`

**Antes:** `$request->user()->token()->revoke()`

**Depois:** Revogar access token E refresh token associado:
```php
$token = $request->user()->token();
$token->revoke();
$token->refreshToken?->revoke();
```

### 5. `routes/api/v1.php`

- Remover rota `auth/login`
- Remover rota `auth/refresh`
- Manter rotas: `auth/register`, `auth/logout`, `auth/me`

### 6. `routes/console.php` — Agendar purge de tokens

Adicionar schedule para limpar tokens revogados/expirados do banco automaticamente:
```php
Schedule::command('passport:purge')->hourly();
```

### 7. Remover arquivos desnecessários

- `app/Http/Controllers/Api/V1/Auth/LoginController.php`
- `app/Http/Controllers/Api/V1/Auth/RefreshController.php`
- `app/Http/Requests/LoginRequest.php`

---

## Testes

- Remover `tests/Feature/Auth/LoginTest.php` (login agora é via `/oauth/token`)
- Remover `tests/Feature/Auth/RefreshTest.php` (refresh agora é via `/oauth/token`)
- Atualizar `tests/Feature/Auth/RegisterTest.php` — verificar novo formato de resposta com tokens OAuth
- Atualizar `tests/Feature/Auth/LogoutTest.php` — verificar que refresh token também é revogado

---

## Formato de resposta

### Register — `POST /api/v1/auth/register`

```json
{
  "user": {
    "id": 1,
    "name": "Novo Usuário",
    "email": "novo@exemplo.com",
    "role": "CLIENT",
    "status": "ACTIVE"
  },
  "access_token": "eyJ...",
  "refresh_token": "def502...",
  "expires_in": 1296000
}
```

### Me — `GET /api/v1/auth/me`

```json
{
  "id": 1,
  "name": "João Silva",
  "email": "usuario@exemplo.com",
  "role": "ADMIN",
  "status": "ACTIVE"
}
```

### Logout — `POST /api/v1/auth/logout`

```json
{
  "message": "Logged out successfully."
}
```

---

## Requests do frontend (exemplos)

### Login — `POST /oauth/token` (Passport nativo)

```json
{
  "grant_type": "password",
  "client_id": "1",
  "client_secret": "abc...",
  "username": "usuario@exemplo.com",
  "password": "senha123",
  "scope": "*"
}
```

**Response:**
```json
{
  "token_type": "Bearer",
  "expires_in": 1296000,
  "access_token": "eyJ...",
  "refresh_token": "def502..."
}
```

### Refresh — `POST /oauth/token` (Passport nativo)

```json
{
  "grant_type": "refresh_token",
  "client_id": "1",
  "client_secret": "abc...",
  "refresh_token": "def502..."
}
```

**Response:**
```json
{
  "token_type": "Bearer",
  "expires_in": 1296000,
  "access_token": "eyJ...",
  "refresh_token": "def502..."
}
```

### Register — `POST /api/v1/auth/register`

```json
{
  "token": "abc123...64chars",
  "name": "Novo Usuário",
  "password": "SenhaForte@123",
  "password_confirmation": "SenhaForte@123"
}
```

### Me — `GET /api/v1/auth/me`

Header: `Authorization: Bearer {access_token}`

### Logout — `POST /api/v1/auth/logout`

Header: `Authorization: Bearer {access_token}`

---

## Passos para ativar

1. Rodar `php artisan db:seed --class=PassportClientSeeder` para garantir que o password grant client existe
2. Compartilhar o `client_id` e `client_secret` com o frontend
3. Rodar `php artisan test --compact tests/Feature/Auth/` para validar todos os testes
4. Rodar `vendor/bin/pint --dirty --format agent`
