# Prompt Template — Laravel Stack

Use este arquivo como skeleton fixo. Substitua cada placeholder antes de retornar o prompt final.

## Placeholders

- `[SYSTEM_CONTEXT]` — frase de abertura em português descrevendo o sistema
- `[SYSTEM_NAME]` — nome do sistema
- `[DOMAIN]` — domínio de produção (ex: meuapp.com.br)
- `[TENANCY_CLAUSE]` — bloco de multi-tenant OU single-tenant (ver seções abaixo)
- `[FRONTEND_CLAUSE]` — bloco de frontend (ver seções abaixo; omitir se `none`)
- `{{FUNCTIONAL_REQUIREMENTS}}` — gerado a partir das respostas do usuário
- `{{NON_FUNCTIONAL_REQUIREMENTS}}` — gerado combinando garantias da plataforma com necessidades do domínio
- `{{TASK_SECTION}}` — gerado solicitando PRD + guia Forge + sprints

---

## Skeleton

[SYSTEM_CONTEXT]



# TECH SPECS DO SISTEMA
- Use PHP 8.5 e Laravel 13 como stack principal.
- Siga rigorosamente a arquitetura e convenções do boilerplate Laravel existente no projeto.
- Adicione `declare(strict_types=1)` no topo de todos os arquivos em `app/`.
- Use type hints explícitos em todos os parâmetros e retornos de método.
- O código deve ser em inglês. Mensagens de validação e interface do usuário em português brasileiro.
- Timezone: `America/Sao_Paulo`. Locale: `pt_BR`. Faker locale: `pt_BR`.
- Use banco de dados MySQL 8.4.
- Use Redis para cache, filas e sessões.
- Credenciais em arquivo `.env` na raiz (gitignored). Valores importados via `config/`.

### Arquitetura DDD

O projeto segue arquitetura Domain-Driven com a estrutura:

```
app/Domain/{Dominio}/
  Services/   — classes single-action, método handle(), uma por operação
  Data/       — DTOs estendendo Spatie\LaravelData\Data
app/Http/Controllers/Api/V1/{Recurso}/
app/Http/Requests/Api/V1/{Recurso}/
app/Http/Resources/Api/V1/{Recurso}/
app/Models/
app/Traits/
app/Enums/
app/Notifications/
app/Support/Api/
```

Fluxo obrigatório de requisição:
`FormRequest → DTO::from($request->validated()) → Service::handle() → Resource → ApiResponse`

### Padrão de Services

- Classes single-action com método `handle()`.
- Toda escrita em banco: `DB::transaction(fn() => ..., 3)` (retry 3x em deadlock).
- Retornar `$model->fresh()` após escrita em transaction.
- Notificações e e-mails disparados **fora** do closure da transaction.
- Nunca usar `try/catch` que apenas faz `report()` + rethrow.

### Padrão de DTOs (Spatie Laravel Data)

- DTOs estendem `Spatie\LaravelData\Data`.
- Instanciar sempre via `SomeData::from($request->validated())`.
- Campos sensíveis com atributo `#[Hidden]`.
- Propriedades `readonly` com constructor property promotion.

### Respostas da API (ApiResponse trait)

Todos os controllers estendem `ApiController` que usa `ApiResponse` trait.
Nunca usar `response()->json()` diretamente.

Métodos disponíveis: `success()`, `created()`, `paginated()`, `noContent()`,
`error()`, `notFound()`, `forbidden()`, `unauthorized()`, `validationError()`.

Envelope: `{ success, message, data, meta? }`.
Paginação inclui meta: `{ total, per_page, current_page, last_page, from, to }`.

### Autenticação

- Laravel Sanctum v4 com Bearer tokens.
- Login por e-mail (não username).
- Verificação de e-mail obrigatória antes do primeiro login.
- Route binding via `uuid` (nunca `id`).
- IDs internos, `tenant_id` e `deleted_at` sempre `hidden` nos models.

### Traits do boilerplate

- `HasUuid` — auto-gera UUID v4 na criação; route key = `uuid`.
- `ApiResponse` — métodos de resposta para controllers.

### Enums

- Usar backed enums com `string` type.
- Implementar método estático `values(): array`.
- Registrar nos `casts()` do model (nunca na propriedade `$casts`).

[TENANCY_CLAUSE]

[FRONTEND_CLAUSE]

### Pacotes obrigatórios (já no boilerplate)

| Pacote | Propósito |
|--------|-----------|
| `spatie/laravel-data` v4 | DTOs com `#[Hidden]` para campos sensíveis |
| `spatie/laravel-permission` v8 | RBAC — guard: `sanctum` |
| `spatie/laravel-medialibrary` v11 | Upload de arquivos e conversões |
| `spatie/laravel-health` v1 | Health checks em `/health` |
| `spatie/laravel-backup` v10 | Backup automático para S3 |
| `spatie/laravel-query-builder` v7 | Filtros e ordenação em listagens |
| `spatie/laravel-schedule-monitor` v4 | Monitor de agendamentos |
| `spatie/simple-excel` v3 | Exportação CSV/XLSX |
| `owen-it/laravel-auditing` v14 | Auditoria de mudanças em models |
| `resend/resend-laravel` v1 | E-mail transacional em produção |
| `dedoc/scramble` v0.13 | Documentação OpenAPI automática em `/docs/api` |
| `laravel/horizon` v5 | Dashboard de filas em `/horizon` |
| `laravel/reverb` v1 | WebSocket server para notificações em tempo real |
| `laravel/pennant` v1 | Feature flags por usuário, tenant ou plano |
| `laravel/pulse` v1 | Monitoramento de performance em `/pulse` |
| `spatie/laravel-model-states` v2 | State machines para models com workflow |
| `spatie/laravel-sluggable` v4 | Auto-geração de slugs |
| `barryvdh/laravel-dompdf` v3 | Geração de PDF |
| `laravellegends/pt-br-validator` v13 | Validação de CPF, CNPJ, CEP, telefone |
| `sentry/sentry-laravel` v4 | Error tracking em produção |
| `larastan/larastan` v3 (dev) | Análise estática PHPStan nível 6 |
| `laravel/telescope` v5 (local) | Debug em desenvolvimento |

### RBAC

- Guard: `sanctum` em `config/permission.php` e `config/auth.php`.
- Middleware aliases: `role`, `permission`, `role_or_permission` em `bootstrap/app.php`.
- Roles criadas por `RoleSeeder` com `guard_name = 'sanctum'`.
- Gates de `/pulse`, `/horizon` e `/telescope` restritos a `role:admin`.

### Validação

- Sempre usar Form Requests com `authorize()` retornando `true`.
- Mensagens em português em todos os Form Requests.
- Usar `laravellegends/pt-br-validator` para CPF, CNPJ, CEP quando necessário.

### Testes (Pest v4)

- Criar testes com `vendor/bin/sail artisan make:test --pest NomeTest`.
- Helper global `tenantActingAs(User $user)` definido em `tests/Pest.php`.
- Arch tests em `tests/ArchTest.php` garantem convenções arquiteturais.
- Rodar testes: `vendor/bin/sail artisan test --compact`.

### Qualidade de código

- Formatar PHP após toda mudança: `vendor/bin/sail bin pint --dirty --format agent`.
- Análise estática: `composer analyse` (PHPStan nível 6 via Larastan).
- Casts declarados no método `casts()`, nunca na propriedade `$casts`.

### Agendamento

```php
$schedule->command('schedule-monitor:sync')->hourly();
$schedule->command('health:schedule-check-heartbeat')->everyMinute();
$schedule->command('horizon:snapshot')->everyFiveMinutes();
$schedule->command('backup:run')->twiceDaily(6, 23);
$schedule->command('backup:clean')->dailyAt('00:30');
```

### Rate limiting

- `auth`: 5 req/min por IP.
- `api` autenticado: 60 req/min por `user.id`.
- `api` anônimo: 30 req/min por IP.

### E-mail

- Desenvolvimento: Mailpit (`http://localhost:8025`).
- Produção: Resend (`MAIL_MAILER=resend`, `RESEND_API_KEY=`).
- Notificações customizadas em `app/Notifications/` com textos em pt_BR.

### Backup

- `spatie/laravel-backup`: dump MySQL → zip → S3/R2.
- Retenção: 7 diários + 4 semanais + 3 mensais + 1 anual.
- Notificação de falha para `BACKUP_NOTIFICATION_EMAIL`.

### Desenvolvimento local

- Laravel Sail com Docker Compose.
- Serviços: `laravel.test` (PHP 8.5), `mysql` (8.4), `redis`, `mailpit`, `minio`.
- Todos os comandos prefixados com `vendor/bin/sail`.
- MinIO como S3-compatible local para backups e mídia.

### Deploy em produção (Laravel Forge)

- Servidor Ubuntu 22.04 LTS com PHP 8.5, MySQL 8.4, Redis e Nginx gerenciados pelo Forge.
- Repositório conectado via GitHub com deploy automático ao push na branch principal.
- SSL via Let's Encrypt gerenciado pelo Forge (renovação automática).
- Horizon rodando como daemon worker gerenciado pelo Forge.
- Laravel Scheduler configurado via cron no Forge (`* * * * * php artisan schedule:run`).
- Deploy script no Forge:

```bash
cd /home/forge/[DOMAIN]
git pull origin main
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
php artisan migrate --force --no-interaction
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan horizon:terminate
```

- Zero-downtime via `horizon:terminate` (Horizon reinicia automaticamente pelo Forge).
- Variáveis de ambiente configuradas no painel Forge em `Environment`.
- Sentry DSN em `SENTRY_LARAVEL_DSN` para rastreamento de erros.
- Backup S3/R2 configurado com `AWS_*` vars no `.env` de produção.



# REQUISITOS FUNCIONAIS DO SISTEMA
{{FUNCTIONAL_REQUIREMENTS}}



# REQUISITOS NÃO FUNCIONAIS DO SISTEMA
{{NON_FUNCTIONAL_REQUIREMENTS}}



# TAREFA
{{TASK_SECTION}}

---

## Blocos de TENANCY_CLAUSE

### Multi-tenant (usar quando is_multitenant = true)

```
### Multi-tenancy (spatie/laravel-multitenancy v4)

- Arquitetura single database: todos os tenants no mesmo banco com `tenant_id` FK.
- Identificação: header `X-Tenant-ID` (UUID) em toda requisição API exceto registro inicial.
- Middleware `ensure_tenant`: resolve tenant via `HeaderTenantFinder`, chama
  `$tenant->makeCurrent()`, retorna 422 JSON se não encontrado ou inativo.
- `BelongsToTenant` trait: global scope `WHERE tenant_id = current` + auto-preenche
  `tenant_id` no create. Aplicar em todos os models tenant-scoped.
- Migrations do model `Tenant` em `database/migrations/landlord/`, carregadas via
  `AppServiceProvider::boot()`.
- Rota de registro cria tenant + usuário admin atomicamente em `DB::transaction(..., 3)`.
- Validação de unicidade de e-mail por tenant:
  `Rule::unique('users')->where(fn($q) => $q->where('tenant_id', Tenant::current()->getKey()))`.
- Testes: `$this->tenant = Tenant::factory()->create(); $this->tenant->makeCurrent();` em `beforeEach`.
- Toda request tenant-scoped precisa do header: `$this->withHeader('X-Tenant-ID', $this->tenant->uuid)`.
```

### Single-tenant (usar quando is_multitenant = false)

```
### Single-tenant

- O sistema serve uma única empresa/organização.
- Não usar `spatie/laravel-multitenancy` nem `BelongsToTenant` trait.
- Autenticação padrão via Laravel Sanctum com Bearer tokens.
- Login por e-mail via custom auth backend.
- Verificação de e-mail obrigatória antes do primeiro login.
- Permissões gerenciadas por `spatie/laravel-permission` com guard `sanctum`.
```

---

## Blocos de FRONTEND_CLAUSE

### Next.js (usar quando frontend_type = nextjs)

```
### Frontend — Next.js (consumidor da API)

- A API Laravel é a única fonte de dados.
- Autenticação: Bearer token Sanctum em cookie httpOnly via middleware Next.js.
- CORS configurado em `config/cors.php` com `allowed_origins` para o domínio do frontend.
- SEO: páginas públicas com SSR/SSG no Next.js.
- Deploy: Vercel ou subdomínio gerenciado pelo Forge (`app.[DOMAIN]`).
```

### React Native (usar quando frontend_type = react-native)

```
### Frontend — React Native / Expo (consumidor da API)

- A API Laravel é a única fonte de dados.
- Autenticação: Bearer token Sanctum em SecureStore (Expo).
- Endpoints versionados em `/api/v1/` — nunca quebrar compatibilidade sem novo versão.
- Push notifications via `laravel/reverb` ou FCM.
- Deploy: Expo EAS Build com canais dev/staging/production.
```

### Inertia React (usar quando frontend_type = inertia)

```
### Frontend — Inertia React (full-stack no mesmo projeto Laravel)

- Inertia.js conecta Laravel com componentes React sem API REST separada.
- Autenticação via sessão Laravel com CSRF (sem tokens Sanctum).
- Componentes em `resources/js/Pages/` e `resources/js/Components/`.
- Build com Vite: `npm run dev` (dev) e `npm run build` (produção).
- Assets compilados servidos pelo Nginx do Forge em produção.
```
