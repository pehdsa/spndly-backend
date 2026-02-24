# Arquitetura do Projeto

## Stack & Versoes

### Backend

- PHP 8.4
- Laravel v12
- Inertia (server) v2
- Laravel Wayfinder v0
- Laravel Pint v1
- PHPUnit v11
- Laravel Passport v13 (OAuth2) *(planejado)*
- Laravel Socialite ^5.24 *(planejado)*

### Frontend

- Vue 3
- @inertiajs/vue3 v2
- Tailwind CSS v4
- Vite v7
- TypeScript 5
- @vueuse/core v12
- ESLint v9
- Prettier v3

### Utilitarios UI

- clsx, tailwind-merge, class-variance-authority

## Estrutura Laravel 12

- `bootstrap/app.php` registra middleware, exceptions e routing
- `bootstrap/providers.php` contém service providers
- Sem `app/Console/Kernel.php` — usar `bootstrap/app.php` ou `routes/console.php`
- Commands em `app/Console/Commands/` auto-registram
- `HandleInertiaRequests` middleware registrado em `bootstrap/app.php`

## Frontend (Inertia + Vue SPA)

- Entry point: `resources/js/app.ts`
- SSR configurado: `resources/js/ssr.ts`
- Pages em `resources/js/pages/`
- Types em `resources/js/types/`
- Wayfinder helpers em `resources/js/wayfinder/`
- Utility `cn()` em `resources/js/lib/utils.ts` (clsx + tailwind-merge)
- CSS entry: `resources/css/app.css` (Tailwind v4 com `@import 'tailwindcss'`)

## API-Only (Versionada) *(planejado)*

- Aplicação API-only com rotas versionadas em `/api/v1`
- Rotas definidas em `routes/api.php` → carrega `routes/api/v1.php`
- Futuras versões: `routes/api/v2.php`, etc.
- Respostas sempre em JSON com HTTP status codes apropriados
- Eloquent API Resources para formatação de resposta
- `MessageResource` para respostas simples (ao invés de `response()->json()`)

## Actions Pattern *(planejado)*

- Lógica de negócio encapsulada em Actions (`app/Actions/`)
- Organizadas por domínio/feature (ex: `app/Actions/Auth/`, `app/Actions/CampaignDispatch/`)
- Cada Action segue SRP — uma responsabilidade
- Método público `handle()` executa a lógica
- Nomeação: verbo + substantivo (ex: `CreateUser`, `SendWelcomeEmail`)
- Controllers finos delegam para Actions

```php
namespace App\Actions\Users;

class CreateUser
{
    public function handle(array $data): User
    {
        // Business logic here
    }
}
```

## Controllers & Validação

- Form Request classes para validação (nunca inline no controller)
- Incluir regras de validação e mensagens de erro customizadas
- Route model binding quando apropriado

## Database

- Driver padrão: PostgreSQL
- Driver de teste: SQLite in-memory
- Eloquent models e relationships ao invés de queries raw
- Evitar `DB::`; preferir `Model::query()`
- Eager loading para prevenir N+1
- Relationship methods com return type hints
- Relationships `BelongsTo` que apontem para models com `SoftDeletes` devem usar `->withTrashed()` para evitar referências retornando null
- Casts via método `casts()` no model (não propriedade `$casts`)
- Ao modificar coluna em migration, incluir todos os atributos previamente definidos

## Paginação — `paginateFromRequest` *(planejado)*

Macro customizado no Eloquent Builder via `PaginatorServiceProvider`. **Sempre usar** para endpoints paginados.

```php
$users = User::query()->paginateFromRequest(orderBy: 'name', initialDirection: 'asc');
return UserResource::collection($users);
```

- Parâmetros: `?page=1`, `?per_page=15`, `?sort=column:asc`
- Validação de segurança contra SQL injection nos nomes de coluna
- Chamar no Builder, **nunca** em Collection

## Autenticação *(planejado)*

### Passport (OAuth2)
- Middleware `auth:api` para rotas autenticadas
- Password Grant flow via `/oauth/token`
- Token expiration: 15 dias (access), 30 dias (refresh), 6 meses (personal)
- Configurado em `app/Providers/AppServiceProvider.php`
- Client criado via `PassportClientSeeder`

### Socialite (Google)
- OAuth com providers sociais
- Dados do provider no model User (`google_id`, etc.)
- Configuração em `config/services.php`

## Models

- Factories e seeders ao criar novos models
- Enums com keys em TitleCase (ex: `FavoritePerson`, `Monthly`)

## PHP

- Curly braces obrigatórias em control structures
- Constructor property promotion (PHP 8)
- Return types e type hints explícitos
- PHPDoc blocks ao invés de comentários inline
- Array shape type definitions quando apropriado

## Testes (PHPUnit)

- PHPUnit classes (converter Pest se encontrar)
- Feature tests como padrão; `--unit` para unit tests
- Factories com states para criar models em testes
- `Passport::actingAs()` para autenticar (nunca testar OAuth2 flow diretamente)

## Queues

- Jobs com `ShouldQueue` para operações demoradas

## Configuração

- `env()` apenas em arquivos de config
- Usar `config('app.name')`, nunca `env('APP_NAME')` no código

## Tooling & Code Quality

- **Pint**: Laravel preset (`pint.json`) — `vendor/bin/pint --dirty` antes de finalizar
- **ESLint v9**: Vue + TypeScript (`eslint.config.js`)
- **Prettier v3**: com `prettier-plugin-tailwindcss`

## Commits

- Formato: `<type>(<scope>): <mensagem> [JIRA-ID]`
- Tipos: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`, `perf`
- `[MXM-XXX]` obrigatório para `feat` e `fix`

## Pull Requests

- Título: `[JIRA-ID] Descrição breve`
- Template com: Jira Ticket, Descrição, Mudanças Técnicas, Checklist, Como Testar
- Criar via `gh pr create`
