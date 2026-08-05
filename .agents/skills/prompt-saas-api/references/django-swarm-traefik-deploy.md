# Deploy Django Swarm Traefik

Use this reference to make the generated prompt production-grade for Django API-only SaaS projects.

## Deployment Identity

Always adapt deployment names from project-specific variables:

- `project_slug`: lowercase snake_case slug, for example `medflow_v1`.
- `stack_name`: default to `project_slug`.
- `production_domain`: root production domain, for example `medflow.com`.
- `registry_image`: full container image, for example `ghcr.io/org/medflow_v1`.
- `public_network`: default `traefik_public`.
- `internal_network`: default `${project_slug}_internal`.
- `egress_network`: default `${project_slug}_egress`.
- `cloudflare_secret_name`: default `CLOUDFLARE_DNS_API_TOKEN`.

Do not keep `scsi`, `scsi.digital`, or `scsi_v1` unless the user explicitly provides those values.

## Required Services

The Swarm stack must include or account for:

- Django app service.
- PostgreSQL service for the application database.
- Celery worker service.
- Celery beat service.
- RabbitMQ service as Celery broker.
- Redis service as Celery result backend and app cache.
- Traefik service as web server and load balancer.

If Traefik is managed in a separate shared stack, the generated PRD must say so and still require the application service to attach to `public_network`.

## Network Model

Use exactly three network roles:

- Public network: `${public_network}`.
  - Overlay network, usually external and shared with Traefik.
  - Receives external HTTP/HTTPS traffic.
  - Only `traefik` and the Django `app` service should attach to it.
- Internal network: `${internal_network}`.
  - Overlay network with `internal: true`.
  - Used for backend service communication.
  - `app`, `postgresql`, `redis`, `rabbitmq`, `celery_worker`, and `celery_beat` attach to it.
- Egress network: `${egress_network}`.
  - Overlay network without `internal: true`.
  - Used only by services that must call external APIs.
  - `celery_worker` and `celery_beat` attach to it for OpenAI or other external APIs.

Never attach `celery_worker`, `celery_beat`, PostgreSQL, Redis, or RabbitMQ to the public Traefik network.

## TLS and Cloudflare

Traefik must issue a wildcard TLS certificate for:

- `${production_domain}`
- `*.${production_domain}`

Use Let's Encrypt DNS-01 challenge with Cloudflare. DNS-01 is mandatory for wildcard certificates. Do not use TLS-01 or HTTP-01 for wildcard certificates, and do not enable tlschallenge and dnschallenge on the same resolver.

The Cloudflare token must:

- have DNS edit scope for the target zone;
- be stored as a Docker Swarm Secret named `${cloudflare_secret_name}`;
- be read by Traefik through `CF_DNS_API_TOKEN_FILE=/run/secrets/${cloudflare_secret_name}`;
- never be committed in compose, stack, or a versioned `.env`.

Production secrets should prefer Docker Secrets whenever possible.

## Environment Variables

Use separate `.env` files for development and production. Keep `.env` gitignored.

Services may receive variables through `env_file`, read directly by Docker. Any shell script that needs `.env` values must use a safe `KEY=VALUE` parser and must not use `source` or `.` because special characters such as `&`, `$`, `*`, and `@` can break shell parsing.

For Django production settings:

- Read `ALLOWED_HOSTS` from `.env` as a comma-separated list through django-environ.
- Recommended production value: `${production_domain},.${production_domain},localhost,127.0.0.1`.
- The leading dot covers subdomains.
- Include `localhost` and `127.0.0.1` so the container healthcheck passes.
- Read `CSRF_TRUSTED_ORIGINS` from `.env` as a comma-separated list.
- Recommended production value: `https://${production_domain},https://*.${production_domain}`.
- `ALLOWED_HOSTS` contains hostnames only, never URLs with schemes.

Because TLS terminates at Traefik and Django receives internal HTTP with `X-Forwarded-Proto`, configure:

- `SECURE_PROXY_SSL_HEADER = ('HTTP_X_FORWARDED_PROTO', 'https')`.
- `SECURE_REDIRECT_EXEMPT` for the healthcheck route.
- Traefik trusted forwarded headers for Cloudflare IP ranges.
- HTTP to HTTPS redirect at Traefik.

## Healthchecks and Startup

The Django app must expose `/health/`:

- public path;
- returns HTTP 200;
- no database access;
- no authentication;
- used by the container healthcheck and Traefik load balancer healthcheck.

Swarm services must include healthchecks:

- app: HTTP check against `/health/`;
- PostgreSQL: `pg_isready`;
- Redis: `redis-cli ping`;
- RabbitMQ: `rabbitmq-diagnostics check_port_connectivity`.

Use a suitable `start_period`. Swarm ignores `depends_on` at runtime, so service readiness must rely on healthchecks plus entrypoints.

## Entrypoints

The app entrypoint must:

1. wait for the database through a Django command such as `wait_for_db`;
2. run migrations protected by a PostgreSQL advisory lock so only one app replica migrates at a time;
3. run `collectstatic --clear`;
4. start the app server.

Celery worker and beat must use separate entrypoints that:

1. wait for the database;
2. do not run migrations;
3. do not run `collectstatic`;
4. start their respective Celery process.

## Static Files and Media

Use named volumes for:

- PostgreSQL data;
- Redis data;
- RabbitMQ data;
- media;
- staticfiles;
- Let's Encrypt certificates.

Files and media must be accessed only through authenticated and authorized Django endpoints. Never expose private uploaded files directly through a public static path.

## Registry and Deploy

The application image must be pushed to `registry_image`, normally GHCR.

Deploy with:

```bash
docker stack deploy --with-registry-auth
```

The production deploy flow should include a `scripts/deploy.sh` executed on the VPS. It must:

- parse `.env` safely;
- validate Swarm is active;
- validate `${cloudflare_secret_name}` exists;
- validate `${public_network}` and `${egress_network}` exist or are created intentionally;
- validate `DEBUG=False`;
- validate `localhost` is present in `ALLOWED_HOSTS`;
- run `git pull`;
- build the Docker image;
- push the image to the registry;
- run `docker stack deploy --with-registry-auth`;
- force rollout of app, celery worker, and celery beat services when needed;
- support `--skip-build` for config-only redeploys.

## Backups

Require `scripts/backup.sh` for PostgreSQL and media backups. It must include retention/rotation by time and a restore procedure in the documentation.

Backups must cover:

- database;
- media files;
- relevant production configuration;
- verification of restore procedure.

