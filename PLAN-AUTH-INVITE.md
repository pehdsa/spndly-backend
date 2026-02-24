Plano: Users & Invitations — Implementação Completa
                                                                                                                                                
     Contexto
                                                                                                                                                
     Implementar o sistema completo de usuários e convites conforme spec, sobre a base já existente (auth, register, invite single). Inclui   
      CRUD de usuários (admin), listagem/deleção de convites, validação de token pública, batch invitations, middlewares de atividade, e
     ajustes de enum/schema.

     Ver ARCHITECTURE.md para decisões de arquitetura do projeto.

     Atenção: Testes rodam em SQLite in-memory (ver ARCHITECTURE.md). Usar whereRaw('LOWER(column) LIKE LOWER(?)', ["%{$search}%"]) para
     search — ILIKE é exclusivo do PostgreSQL e quebraria os testes.

     ---
     Ordem de Execução

     1. Registrar PaginatorServiceProvider

     Arquivo: bootstrap/providers.php
     Adicionar App\Providers\PaginatorServiceProvider::class — não está registrado, macro paginateFromRequest não funciona sem isso.

     ---
     2. Criar migration: viewed_at + phone_number + status BLOCKED

     Arquivo: nova migration
     $table->string('phone_number', 50)->nullable()->after('email');
     $table->timestamp('viewed_at')->nullable()->after('updated_at');
     UserStatus::BLOCKED não precisa de migration de dados — é ambiente dev sem INACTIVE em produção.

     ---
     3. Enums

     Modificar app/Enums/UserStatus.php:
     - Inactive = 'INACTIVE' → Blocked = 'BLOCKED'

     Criar app/Enums/InvitationStatus.php:
     - Valid = 'VALID', Used = 'USED', Expired = 'EXPIRED'

     ---
     4. Atualizar User Model

     Arquivo: app/Models/User.php
     - Adicionar phone_number, viewed_at em $fillable
     - Adicionar cast viewed_at → datetime

     ---
     5. Atualizar UserFactory

     Arquivo: database/factories/UserFactory.php
     - inactive() → blocked() (usa UserStatus::Blocked)

     ---
     6. Criar MessageResource

     Arquivo: app/Http/Resources/MessageResource.php
     Resposta padrão { "data": { "message": "..." } } — referenciado no CLAUDE.md como convenção.

     ---
     7. Atualizar UserResource

     Arquivo: app/Http/Resources/UserResource.php
     Adicionar: phone_number, viewed_at

     ---
     8. Criar InvitationResource

     Arquivo: app/Http/Resources/InvitationResource.php
     Campos: id, email, role, status (calculado via InvitationStatus), expires_at, used_at, invited_by, created_at
     Status calculado: USED se used_at != null, EXPIRED se expires_at passado, VALID caso contrário.

     ---
     9. DTO + BatchInvitationResource

     Criar app/DTOs/BatchInvitationResultData.php:
     public function __construct(
         public readonly array $sent,   // Invitation[]
         public readonly array $failed, // ['email' => string, 'reason' => string][]
     ) {}
     Criar app/Http/Resources/BatchInvitationResource.php:
     Retorna { sent: InvitationResource[], failed: [{email, reason}] }

     ---
     10. Atualizar CreateInvitationAction → batch

     Arquivo: app/Actions/Invitations/CreateInvitationAction.php
     Nova assinatura: handle(User $inviter, array $emails, string $role): BatchInvitationResultData
     - Itera sobre emails, coleta erros em $failed (sem lançar exceção)
     - Retorna BatchInvitationResultData

     ---
     11. Atualizar CreateInvitationRequest

     Arquivo: app/Http/Requests/CreateInvitationRequest.php
     - email → emails (array, max 50 items)
     - emails.* → email, max 255

     ---
     12. Criar Actions: Users

     - app/Actions/Users/ListUsersAction.php — search (name/email) + paginateFromRequest
     - app/Actions/Users/BlockUserAction.php — DB::transaction(): status=BLOCKED + $user->tokens()->update(['revoked' => true])
     - app/Actions/Users/UnblockUserAction.php — status=ACTIVE
     - app/Actions/Users/DeleteUserAction.php — DB::transaction(): $user->tokens()->update(['revoked' => true]) + softDelete
     - app/Actions/Users/UpdateUserRoleAction.php — atualiza role

     ---
     13. Criar Actions: Invitations

     - app/Actions/Invitations/ListInvitationsAction.php — search (email) + paginateFromRequest
     - app/Actions/Invitations/DeleteInvitationAction.php — deleta convite

     ---
     14. Criar Middleware

     - app/Http/Middleware/EnsureUserIsActive.php — se status !== ACTIVE retorna 403 com MessageResource
     - app/Http/Middleware/TrackUserActivity.php — se viewed_at null ou > 5min, faz $user->update(['viewed_at' => now()])

     Registrar aliases em bootstrap/app.php:
     $middleware->alias([
         'active' => EnsureUserIsActive::class,
         'track.activity' => TrackUserActivity::class,
     ]);

     ---
     15. Criar UserPolicy

     Arquivo: app/Policies/UserPolicy.php
     Regra: admin não pode bloquear, deletar ou alterar role de si mesmo.
     Usado via can: no controller ou authorize() nas actions.

     ---
     16. Criar Request: UpdateUserRoleRequest

     Arquivo: app/Http/Requests/UpdateUserRoleRequest.php
     Campo: role required + Enum(UserRole::class)

     ---
     17. Criar UserController (admin)

     Arquivo: app/Http/Controllers/Api/V1/UserController.php
     Métodos: index, show, destroy, block, unblock, updateRole
     Retornos: UserResource::collection, UserResource, MessageResource

     ---
     18. Atualizar InvitationController

     Arquivo: app/Http/Controllers/Api/V1/InvitationController.php
     - store → usa batch action, retorna BatchInvitationResource 201
     - Adicionar index → ListInvitationsAction → InvitationResource::collection
     - Adicionar destroy → DeleteInvitationAction → MessageResource
     - Adicionar validateToken (público) → ValidateInvitationTokenAction → InvitationResource

     ---
     19. Atualizar routes/api/v1.php

     // Público
     Route::middleware('throttle:10,1')->group(function () {
         Route::get('invitations/validate', [InvitationController::class, 'validateToken']);
     });

     // Auth only — logout acessível mesmo para usuários bloqueados
     Route::middleware('auth:api')->group(function () {
         Route::post('auth/logout', LogoutController::class)->name('api.v1.auth.logout');
     });

     // Auth + active + track
     Route::middleware(['auth:api', 'active', 'track.activity'])->group(function () {
         // me, refresh
         // Admin only
         Route::middleware('can:admin')->group(function () {
             Route::apiResource('users', UserController::class)->only(['index', 'show', 'destroy']);
             Route::post('users/{user}/block', [UserController::class, 'block']);
             Route::post('users/{user}/unblock', [UserController::class, 'unblock']);
             Route::patch('users/{user}/role', [UserController::class, 'updateRole']);

             Route::get('invitations', [InvitationController::class, 'index']);
             Route::post('invitations', [InvitationController::class, 'store']);
             Route::delete('invitations/{invitation}', [InvitationController::class, 'destroy']);
         });
     });

     ---
     20. Atualizar Tests existentes

     - CreateInvitationTest — atualizar para emails: [] e novo formato de resposta
     - Auth/*Test — verificar se UserStatus::Inactive quebrou algum test

     ---
     21. Criar Tests novos

     - tests/Feature/Users/ListUsersTest.php
     - tests/Feature/Users/ShowUserTest.php
     - tests/Feature/Users/DeleteUserTest.php
     - tests/Feature/Users/BlockUserTest.php
     - tests/Feature/Users/UpdateUserRoleTest.php
     - tests/Feature/Invitations/ListInvitationsTest.php
     - tests/Feature/Invitations/ValidateInvitationTest.php
     - tests/Feature/Invitations/DeleteInvitationTest.php

     ---
     Arquivos Críticos Modificados

     ┌──────────────────────────────────────────────────────┬─────────────────────────────────┐
     │                       Arquivo                        │              Ação               │
     ├──────────────────────────────────────────────────────┼─────────────────────────────────┤
     │ bootstrap/providers.php                              │ + PaginatorServiceProvider      │
     ├──────────────────────────────────────────────────────┼─────────────────────────────────┤
     │ bootstrap/app.php                                    │ + middleware aliases            │
     ├──────────────────────────────────────────────────────┼─────────────────────────────────┤
     │ app/Enums/UserStatus.php                             │ Inactive → Blocked              │
     ├──────────────────────────────────────────────────────┼─────────────────────────────────┤
     │ app/Models/User.php                                  │ + phone_number, viewed_at       │
     ├──────────────────────────────────────────────────────┼─────────────────────────────────┤
     │ app/Http/Resources/UserResource.php                  │ + phone_number, viewed_at       │
     ├──────────────────────────────────────────────────────┼─────────────────────────────────┤
     │ database/factories/UserFactory.php                   │ inactive() → blocked()          │
     ├──────────────────────────────────────────────────────┼─────────────────────────────────┤
     │ app/Actions/Invitations/CreateInvitationAction.php   │ batch                           │
     ├──────────────────────────────────────────────────────┼─────────────────────────────────┤
     │ app/Http/Requests/CreateInvitationRequest.php        │ emails[]                        │
     ├──────────────────────────────────────────────────────┼─────────────────────────────────┤
     │ app/Http/Controllers/Api/V1/InvitationController.php │ + index, destroy, validateToken │
     ├──────────────────────────────────────────────────────┼─────────────────────────────────┤
     │ routes/api/v1.php                                    │ + todas as rotas                │
     ├──────────────────────────────────────────────────────┼─────────────────────────────────┤
     │ tests/Feature/Invitations/CreateInvitationTest.php   │ atualizar                       │