---
name: prompt-saas-api
description: Use when the user wants a technical master prompt for generating a markdown PRD for a Django API-only SaaS, ERP, CRM, marketplace, portal, or management system with multi-tenancy, fixed backend stack, and professional Docker Swarm/Traefik deploy.
---

# Prompt SaaS API

## Overview

Produce the final `prompt mestre`, not the PRD itself. Keep the Django API-only SaaS stack and professional deploy model fixed in every project. Adapt only the business domain, workflows, entities, deploy identity values, and acceptance criteria.

Read the references in this order:

1. `references/prompt-template.md`
2. `references/django-swarm-traefik-deploy.md`
3. `references/generation-rules.md`

## Trigger Contract

Use this skill when the user wants to:

- gerar um prompt tecnico para um novo SaaS;
- estruturar um prompt de PRD para um sistema API-only;
- transformar uma ideia de ERP, CRM, marketplace, portal, sistema de gestao, assinatura, delivery, clinica, cursos, financeiro ou similar em um prompt profissional;
- manter uma stack Django fixa enquanto muda apenas o dominio do negocio;
- gerar uma especificacao com deploy profissional em Docker Swarm, Traefik, Cloudflare, GHCR, healthchecks, secrets, redes overlay e Celery isolado da rede publica.

Do not use this skill to generate the PRD itself unless the user explicitly asks for a different workflow.

## Input Contract

Capture or derive before generating:

- `domain_summary`: que tipo de sistema sera criado;
- `project_title`: nome sugerido do sistema;
- `project_slug`: slug tecnico em lowercase snake_case para stack, redes, volumes e nomes de servicos;
- `production_domain`: dominio de producao;
- `registry_image`: imagem da aplicacao no GHCR ou registry equivalente;
- `stack_name`: nome do stack no Docker Swarm;
- `test_policy`: `required` por padrao, ou `disabled_by_user_request` somente quando o usuario pedir explicitamente para nao implementar testes;
- `system_goal`: objetivo central do produto;
- `target_audience`: publico-alvo principal;
- `pain_points`: dores que o sistema resolve;
- `modules`: modulos funcionais do dominio;
- `main_entities`: entidades principais;
- `entity_relationships`: relacionamentos relevantes;
- `business_rules`: regras de negocio centrais;
- `user_profiles`: perfis de usuario;
- `permissions`: modelo de permissao;
- `main_flows`: fluxos principais;
- `api_areas`: areas de endpoints necessarias;
- `reports`: relatorios importantes;
- `dashboard_metrics`: metricas e indicadores via API;
- `async_jobs`: tarefas assincronas;
- `ai_capabilities`: recursos de IA aplicaveis;
- `integrations`: integracoes uteis;
- `technical_risks`: riscos tecnicos do dominio;
- `acceptance_criteria`: criterios de aceite de alto nivel;
- `out_of_scope`: itens explicitamente fora do escopo.

If the user gives only the domain, infer a professional first version for the remaining fields without changing the fixed stack.

If `project_slug` is missing, derive it from `project_title`. If `stack_name` is missing, use `project_slug`. If network names are missing, derive them as `traefik_public`, `${project_slug}_internal`, and `${project_slug}_egress`.

If `production_domain` or `registry_image` is missing and the user expects a production-ready deploy prompt, ask at most one concise question for the missing deploy identity. If the user wants to continue anyway, use explicit placeholders in the generated prompt.

If the latest user message does not identify any product, sector, or system type, ask one concise question before continuing.

## Non-Negotiables

- Never change the fixed Django stack.
- Never turn the solution into a visual full stack scope.
- Never add React, Vue, Next.js, Angular, Django Templates, or SPA responsibilities to the generated prompt.
- Never remove multi-tenancy, JWT, Celery, RabbitMQ, Redis, Docker Swarm, Traefik, OpenAPI, or tenant isolation.
- Never keep project-specific names such as `scsi`, `scsi.digital`, or `scsi_v1` unless the user explicitly supplies them.
- Never put `celery_worker`, `celery_beat`, PostgreSQL, Redis, or RabbitMQ on the public Traefik network.
- Never use HTTP-01 or TLS-01 for wildcard certificates; require Cloudflare DNS-01.
- Never expose files publicly.
- Never leave tenant boundaries implicit.
- Never answer with a generic CRUD-only prompt.
- Never generate the PRD directly when the user asked for the prompt.
- Never contradict `test_policy`.

## Workflow

1. Read `references/prompt-template.md`.
2. Read `references/django-swarm-traefik-deploy.md`.
3. Read `references/generation-rules.md`.
4. Infer or normalize the input contract from the user request.
5. Replace every placeholder in the template with domain-specific content and deploy identity values.
6. Embed the deploy rules from `references/django-swarm-traefik-deploy.md` into the generated prompt, adapted to the project slug/domain/registry.
7. Keep the fixed Django stack, security model, deployment standards, and sprint structure intact.
8. Return only the final prompt in Brazilian Portuguese, ready to copy and paste into another AI.

## Output Contract

The response must be:

- em portugues brasileiro;
- tecnica;
- organizada;
- profunda;
- nao generica;
- pronta para copiar e colar;
- fiel a stack fixa;
- adaptada ao dominio informado;
- com deploy profissional parametrizado por projeto;
- orientada a gerar um PRD em markdown.

## References

- `references/prompt-template.md` - template completo do prompt final
- `references/django-swarm-traefik-deploy.md` - regras obrigatorias de deploy profissional Django em Swarm/Traefik
- `references/generation-rules.md` - regras de adaptacao, proibicoes e criterio de qualidade
