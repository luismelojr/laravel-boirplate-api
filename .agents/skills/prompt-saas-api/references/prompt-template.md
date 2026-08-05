# Titulo do Projeto

Gere um PRD profissional em markdown para o sistema `[PROJECT_TITLE]`.

Use este prompt como especificacao obrigatoria. O dominio do negocio pode ser adaptado, mas a stack tecnica, o modelo arquitetural, os requisitos de seguranca, o padrao de deploy, o modelo multi-tenant e a estrutura das sprints nao podem ser alterados.

## 1. Titulo do projeto

Use o nome `[PROJECT_TITLE]`.

## 2. Contexto geral

Considere um sistema SaaS API-only para `[DOMAIN_SUMMARY]`.

Contexto do negocio:

- objetivo central: `[SYSTEM_GOAL]`
- publico-alvo: `[TARGET_AUDIENCE]`
- dores que resolve: `[PAIN_POINTS]`

Identidade tecnica e deploy:

- slug tecnico do projeto: `[PROJECT_SLUG]`
- nome do stack Docker Swarm: `[STACK_NAME]`
- dominio de producao: `[PRODUCTION_DOMAIN]`
- imagem da aplicacao no registry: `[REGISTRY_IMAGE]`
- rede publica Traefik: `[PUBLIC_NETWORK]`
- rede interna isolada: `[INTERNAL_NETWORK]`
- rede de saida para APIs externas: `[EGRESS_NETWORK]`
- secret do token Cloudflare DNS: `[CLOUDFLARE_SECRET_NAME]`
- politica de testes: `[TEST_POLICY]`

## 3. Objetivo do sistema

Descreva o objetivo do sistema com foco em resultado operacional, eficiencia, escalabilidade e padronizacao do dominio.

## 4. Publico-alvo

Detalhe os perfis de clientes, operadores internos, administradores e demais usuarios relevantes para o dominio.

## 5. Tipo de arquitetura

O sistema deve ser especificado como:

- SaaS API-only;
- multi-tenant em banco compartilhado;
- backend monolitico modular em Django;
- sem frontend dentro do escopo;
- tudo exposto via API versionada em `/api/v1/`.

## 6. Stack tecnica fixa

Mantenha exatamente a stack abaixo em todo o PRD:

- Backend API-only em Django.
- Django REST Framework.
- Python > 3.13.
- Django >= 6.0.
- PostgreSQL.
- SaaS multi-tenant em modelo compartilhado.
- Login por email usando o sistema nativo de usuarios do Django.
- JWT com access token e refresh token.
- API versionada em `/api/v1/`.
- OpenAPI/Swagger com drf-spectacular.
- Django Admin apenas como painel operacional interno.
- Sem frontend em Django Templates.
- Sem React, Vue, Next.js, Angular ou SPA dentro do escopo.
- Sem landing page visual.
- Sem dashboard visual.
- Sem kanban visual.
- Sem chat visual no escopo de interface.
- Tudo deve ser exposto via API.
- Celery para tarefas assincronas.
- RabbitMQ como broker do Celery.
- Redis como result backend e cache.
- dj-celery-panel no Django Admin.
- Docker e Docker Compose para desenvolvimento local.
- Docker Swarm para producao em VPS.
- Traefik como web server e load balancer.
- TLS wildcard com Let's Encrypt via DNS-01 e Cloudflare.
- Cloudflare DNS API Token via Docker Secret.
- GHCR como registry.
- Deploy com `docker stack deploy --with-registry-auth`.
- Healthcheck publico em `/health/`.
- Healthchecks para app, PostgreSQL, Redis e RabbitMQ.
- `wait_for_db` nos entrypoints.
- Migrations com advisory lock do PostgreSQL.
- `collectstatic --clear`.
- Volumes nomeados para banco, Redis, RabbitMQ, media, staticfiles e certificados.
- Redes overlay separadas: `[PUBLIC_NETWORK]`, `[INTERNAL_NETWORK]` e `[EGRESS_NETWORK]`.
- App na rede `[PUBLIC_NETWORK]` e `[INTERNAL_NETWORK]`.
- Celery worker e beat na rede `[INTERNAL_NETWORK]` e `[EGRESS_NETWORK]`.
- Banco, Redis e RabbitMQ apenas na rede `[INTERNAL_NETWORK]`.
- Nunca expor Celery na rede publica.
- `.env` gitignored.
- Docker Secrets para segredos sensiveis.
- CORS configuravel por ambiente.
- Logs estruturados.
- Auditoria para acoes sensiveis.
- Rate limiting em endpoints sensiveis.
- Protecao contra acesso cruzado entre tenants.
- Arquivos acessados apenas por endpoints protegidos.
- ReportLab e PyPDF para relatorios PDF.
- CSV para exportacoes quando aplicavel.
- MKDocs com suporte a Mermaid.
- Codigo em ingles.
- Mensagens de API voltadas ao usuario em portugues brasileiro.
- Timezone `America/Sao_Paulo`.
- Apps Django separados por dominio.
- App principal chamado `core`.
- App compartilhado chamado `base`.
- `.venv` na raiz.
- `requirements.txt` atualizado.
- Apenas um `settings.py`.
- Codigo simples, PEP8, aspas simples.
- Todos os models com `created_at` e `updated_at`.
- Uso de ViewSets, APIViews, Serializers, Permissions, Filters, Routers, Services, Managers e QuerySets.
- Politica de testes controlada por `[TEST_POLICY]`.

## 7. Requisitos funcionais

Detalhe os requisitos funcionais do dominio cobrindo obrigatoriamente estes modulos e expandindo quando necessario:

`[MODULES]`

Regras de negocio que devem aparecer com profundidade:

`[BUSINESS_RULES]`

Fluxos principais que precisam ser detalhados:

`[MAIN_FLOWS]`

## 8. Requisitos nao funcionais

Inclua requisitos de performance, seguranca, rastreabilidade, escalabilidade, observabilidade, auditabilidade, backups, confiabilidade operacional e manutencao.

## 9. Modelo multi-tenant

Explique o modelo multi-tenant compartilhado, a estrategia de isolamento, a segregacao de dados e as regras para impedir acesso cruzado entre tenants.

## 10. Perfis de usuarios e permissoes

Considere estes perfis e modele RBAC ou equivalente:

- perfis: `[USER_PROFILES]`
- permissoes: `[PERMISSIONS]`

## 11. Entidades principais

Liste e detalhe estas entidades principais:

`[MAIN_ENTITIES]`

## 12. Relacionamentos entre entidades

Explique claramente estes relacionamentos:

`[ENTITY_RELATIONSHIPS]`

## 13. Endpoints principais da API

Especifique os grupos de endpoints necessarios para:

`[API_AREAS]`

Ao definir endpoints, cubra CRUD quando fizer sentido, mas tambem comandos de negocio, filtros, buscas, acoes de workflow, exportacoes, anexos, auditoria e endpoints de status.

Considere tambem as integracoes externas relevantes para:

`[INTEGRATIONS]`

Explique contratos, autenticacao, retries, webhooks, conciliacao e observabilidade das integracoes quando aplicavel.

## 14. Padrao profissional da API

Descreva convencoes para:

- versionamento;
- serializacao;
- paginacao;
- filtros;
- ordenacao;
- erros padronizados;
- validacoes;
- idempotencia quando aplicavel;
- naming consistente;
- contratos OpenAPI.

## 15. Autenticacao e autorizacao

Mantenha login por email, JWT com access e refresh token, invalidacao quando necessario, controle por tenant e autorizacao por perfil/permissao.

## 16. Seguranca e isolamento de dados

Inclua:

- isolamento estrito por tenant;
- validacao de ownership;
- rate limiting em endpoints sensiveis;
- trilha de auditoria;
- protecao de arquivos;
- seguranca de segredos;
- CORS por ambiente;
- mitigacao de abuso e acesso indevido.

## 17. Gestao de arquivos e anexos

Especifique upload, armazenamento, autorizacao de acesso, download seguro, exclusao, trilha de auditoria e politicas por tenant quando o dominio exigir anexos.

## 18. Dashboards e metricas via API

Converta qualquer necessidade visual em endpoints de metricas e indicadores.

Cubra:

`[DASHBOARD_METRICS]`

## 19. Relatorios e exportacoes

Inclua relatorios em PDF e exportacoes CSV quando fizer sentido, cobrindo:

`[REPORTS]`

## 20. Tarefas assincronas

Detalhe tarefas assincronas relevantes para o dominio:

`[ASYNC_JOBS]`

Explique fila, retries, agendamento, idempotencia, monitoramento e comportamento esperado em falhas.

## 21. Recursos de IA

Quando fizer sentido para o dominio, inclua esta stack fixa de IA:

- LangChain >= 1.0.
- LangGraph.
- GPT-5.5-mini via OpenAI.
- Celery para processamento assincrono.
- Endpoints para disparar analises.
- Endpoints para consultar status.
- Endpoints para recuperar resultados.
- Chat com agente via API quando aplicavel.
- Sessoes de chat salvas por usuario e tenant.
- Tools com acesso restrito aos dados do tenant.
- Respostas da IA em markdown.
- Logs e auditoria de uso de IA.
- Bloqueio absoluto de acesso cruzado entre tenants.

Adapte os casos de uso para:

`[AI_CAPABILITIES]`

## 22. Logs, auditoria e rastreabilidade

Defina logs estruturados, eventos auditaveis, trilha de quem fez o que, correlacao por tenant e estrategia de investigacao operacional.

## 23. Documentacao OpenAPI/Swagger

Exija documentacao completa com drf-spectacular, exemplos de payloads, schemas claros e cobertura das rotas principais.

## 24. Documentacao tecnica com MKDocs

Peça estrutura de documentacao tecnica com MKDocs e Mermaid cobrindo arquitetura, apps, fluxos, deploy, operacao e troubleshooting.

## 25. Estrutura sugerida de apps Django

Proponha uma divisao de apps orientada ao dominio, preservando:

- `core` como app principal;
- `base` como app compartilhado;
- separacao por contexto de negocio;
- services, managers e querysets onde agregarem clareza.

## 26. Deploy local com Docker Compose

Descreva o ambiente local com app, banco, Redis, RabbitMQ, worker, beat, healthchecks, volumes e fluxo de inicializacao.

## 27. Deploy em producao com Docker Swarm

Especifique servicos, redes, volumes, secrets, healthchecks, estrategia de rollout e uso de Traefik no Docker Swarm.

Use obrigatoriamente estas regras de deploy:

`[DJANGO_SWARM_TRAEFIK_DEPLOY_RULES]`

## 28. Guia de deploy em VPS Ubuntu do zero

Inclua um passo a passo desde provisionamento da VPS ate stack em producao, cobrindo Docker, Swarm, Traefik, DNS, TLS wildcard via Cloudflare DNS-01, GHCR, secrets, redes overlay, healthchecks e deploy.

## 29. Scripts obrigatorios

Liste scripts e comandos operacionais minimos para:

- bootstrap local;
- subida de containers;
- migrations;
- coleta de estaticos;
- testes;
- lint;
- deploy;
- rollback quando aplicavel;
- verificacao de saude.

Inclua especificamente:

- `scripts/deploy.sh` executado na VPS, com parser seguro de `.env`, validacoes de pre-condicoes, build/push da imagem, `docker stack deploy --with-registry-auth`, rollout forçado quando necessario e modo `--skip-build`;
- `scripts/backup.sh` para PostgreSQL e media, com retencao por tempo e procedimento de restore.

## 30. Backups

Descreva estrategia de backup e restauracao para banco, arquivos, configuracoes e validacao periodica do processo.

## 31. Testes e validacao

Se `[TEST_POLICY]` for `required`, defina testes minimos obrigatorios para fluxos criticos cobrindo autenticacao, autorizacao, isolamento por tenant, regras centrais, anexos, exportacoes, filas, IA e casos de erro.

Se `[TEST_POLICY]` for `disabled_by_user_request`, nao coloque testes como tarefa obrigatoria de implementacao. Em vez disso, inclua checklist de validacao manual, criterios de aceite verificaveis e registre a ausencia de testes automatizados como risco tecnico.

## 32. Criterios de aceite

Use e expanda estes criterios:

`[ACCEPTANCE_CRITERIA]`

## 33. Roadmap em sprints

Organize o desenvolvimento em sprints coerentes com o dominio e com esta ordem base:

1. Fundacao do projeto
2. Autenticacao e usuarios
3. Multi-tenant
4. Entidades principais
5. Regras de negocio centrais
6. Arquivos e anexos
7. Relatorios
8. Dashboards via API
9. Tarefas assincronas
10. IA
11. Seguranca avancada
12. Documentacao
13. Deploy local
14. Deploy em producao
15. Testes e validacao final conforme `[TEST_POLICY]`

## 34. Checklist detalhado das sprints

Cada sprint deve ter checklist obrigatorio neste formato:

- [ ] tarefa a ser executada

Monte tarefas detalhadas, tecnicas e sequenciais.

## 35. Riscos tecnicos e mitigacao

Detalhe estes riscos tecnicos e as respectivas mitigacoes:

`[TECHNICAL_RISKS]`

## 36. Itens fora do escopo

Liste explicitamente o que ficara fora do escopo inicial, considerando:

`[OUT_OF_SCOPE]`

## Regras Finais de Qualidade

- O resultado final deve ser um PRD profissional em markdown.
- Nao mude a stack fixa.
- Nao transforme o projeto em full stack visual.
- Nao proponha frontend dentro do escopo.
- Nao exponha arquivos publicamente.
- Nao deixe o isolamento por tenant implicito.
- Nao entregue apenas CRUD generico.
- Nao use nomes de deploy de outro projeto sem o usuario fornecer esses valores.
- Nao coloque Celery, PostgreSQL, Redis ou RabbitMQ na rede publica.
- Nao use HTTP-01 ou TLS-01 para wildcard TLS.
- Respeite `[TEST_POLICY]`.
- Mantenha profundidade tecnica, clareza e orientacao pratica para implementacao.
