# Backup Automático — Design Spec

**Date:** 2026-06-18
**Status:** Approved
**Package:** `spatie/laravel-backup`

---

## Overview

Adiciona backup automático do banco de dados MySQL ao boilerplate Laravel 13, com armazenamento em S3 (MinIO localmente), retenção escalonada, agendamento 2x por dia, notificações de falha via Discord e monitoramento via `/health`.

---

## 1. MinIO no Sail

### compose.yaml — novo serviço

```yaml
minio:
    image: 'minio/minio:latest'
    ports:
        - '${FORWARD_MINIO_PORT:-9000}:9000'
        - '${FORWARD_MINIO_DASHBOARD_PORT:-9001}:9001'
    environment:
        MINIO_ROOT_USER: '${AWS_ACCESS_KEY_ID}'
        MINIO_ROOT_PASSWORD: '${AWS_SECRET_ACCESS_KEY}'
    volumes:
        - 'sail-minio:/data/minio'
    networks:
        - sail
    command: 'minio server /data/minio --console-address ":9001"'
    healthcheck:
        test: ['CMD', 'mc', 'ready', 'local']
        retries: 3
        timeout: 5s
```

Adicionar volume `sail-minio` na seção `volumes`.

### .env e .env.example

```dotenv
AWS_ACCESS_KEY_ID=sail
AWS_SECRET_ACCESS_KEY=password
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=backups
AWS_ENDPOINT=http://minio:9000
AWS_USE_PATH_STYLE_ENDPOINT=true
```

### Criar bucket no MinIO via Artisan

Após subir o Sail, criar o bucket `backups` via comando:
```bash
vendor/bin/sail exec minio mc alias set local http://localhost:9000 sail password && vendor/bin/sail exec minio mc mb local/backups
```

Ou via dashboard em `http://localhost:9001` (credenciais: `sail` / `password`).

---

## 2. spatie/laravel-backup — Configuração

### Instalação

```bash
vendor/bin/sail composer require spatie/laravel-backup
vendor/bin/sail artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider" --no-interaction
```

### config/backup.php — valores-chave

**Fonte:**
```php
'source' => [
    'files' => [
        'include' => [],      // sem arquivos — apenas banco
        'exclude' => [],
        'followLinks' => false,
        'ignoreUnreadableDirs' => true,
        'relativePathPrefix' => null,
    ],
    'databases' => ['mysql'],
],
```

**Destino:**
```php
'destination' => [
    'filename_prefix' => '',
    'disks' => ['s3'],
],
```

**Nome do backup** (pasta no S3):
```php
'name' => env('APP_NAME', 'laravel'),
```

**Senha do zip:**
```php
'password' => null,  // S3 criptografa em repouso
'encryption' => 'default',
```

**Retenção (`cleanup.strategy`):**
```php
'strategy' => \Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy::class,
'defaultStrategy' => [
    'keepAllBackupsForDays' => 7,
    'keepDailyBackupsForDays' => 16,
    'keepWeeklyBackupsForWeeks' => 4,
    'keepMonthlyBackupsForMonths' => 3,
    'keepYearlyBackupsForYears' => 1,
    'deleteOldestBackupsWhenUsingMoreMegabytesThan' => 5000,
],
```

**Notificações via Discord:**
```php
'notifications' => [
    'notifications' => [
        \Spatie\Backup\Notifications\Notifications\BackupHasFailed::class         => ['mail'],
        \Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFound::class => ['mail'],
        \Spatie\Backup\Notifications\Notifications\CleanupHasFailed::class        => ['mail'],
        \Spatie\Backup\Notifications\Notifications\BackupWasSuccessful::class      => [],
        \Spatie\Backup\Notifications\Notifications\HealthyBackupWasFound::class    => [],
        \Spatie\Backup\Notifications\Notifications\CleanupWasSuccessful::class     => [],
    ],
    'notifiable' => \Spatie\Backup\Notifications\Notifiable::class,
    'mail' => [
        'to' => env('BACKUP_NOTIFICATION_EMAIL', 'admin@example.com'),
        'from' => [
            'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
            'name' => env('MAIL_FROM_NAME', 'Laravel'),
        ],
    ],
    'slack' => ['webhook_url' => ''],
    'discord' => ['webhook_url' => env('DISCORD_ALERT_WEBHOOK', '')],
],
```

**Nota:** `spatie/laravel-backup` não tem integração nativa com `spatie/laravel-discord-alerts`. As notificações de falha são enviadas via canal `mail` por padrão (Mailpit localmente, Resend em produção). O `BACKUP_NOTIFICATION_EMAIL` define o destinatário.

---

## 3. Agendamento

### bootstrap/app.php — adicionar ao schedule existente

```php
->withSchedule(function (Schedule $schedule): void {
    $schedule->command('horizon:snapshot')->everyFiveMinutes();
    $schedule->command('backup:run')->twiceDaily(6, 23);
    $schedule->command('backup:clean')->dailyAt('00:30');
})
```

---

## 4. Health Check

### AppServiceProvider — adicionar BackupCheck

```php
use Spatie\Health\Checks\Checks\BackupCheck;

Health::checks([
    DatabaseCheck::new(),
    RedisCheck::new(),
    HorizonCheck::new(),
    QueueCheck::new(),
    BackupCheck::new()->locatedAt(config('app.name').'/*.zip')->maxAgeInDays(1),
]);
```

O endpoint `/health` passa a reportar `warning` se o backup mais recente for de mais de 1 dia atrás.

---

## 5. Variáveis de Ambiente

Adicionar ao `.env.example`:

```dotenv
# MinIO / S3 Backup
AWS_ACCESS_KEY_ID=sail
AWS_SECRET_ACCESS_KEY=password
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=backups
AWS_ENDPOINT=http://minio:9000
AWS_USE_PATH_STYLE_ENDPOINT=true

# Backup notifications
BACKUP_NOTIFICATION_EMAIL=admin@example.com
```

---

## 6. Testes

**`BackupTest`:**
- `backup:run` command executa sem erros (usando `Storage::fake('s3')`)
- `backup:clean` command executa sem erros
- `/health` endpoint inclui check de backup na resposta

**Nota:** Testes do backup real não fazem dump real do banco — usam `Storage::fake('s3')` e testam apenas que os comandos rodam e que o health check está registrado.

---

## 7. Ordem de Implementação

| # | Tarefa |
|---|--------|
| 1 | MinIO no compose.yaml + variáveis de ambiente |
| 2 | Instalar spatie/laravel-backup, configurar config/backup.php, schedule, BackupCheck no health |
