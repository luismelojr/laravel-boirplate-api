# PRD - FutCoach Academy

## 1. Titulo do projeto

**FutCoach Academy** e um SaaS API-only para treinadores independentes de futebol criarem, venderem, entregarem e acompanharem cursos, programas de treino e assinaturas digitais em um ambiente verticalizado para o futebol brasileiro.

## 2. Contexto geral

Treinadores independentes de futebol no Brasil costumam depender de uma operacao fragmentada: Instagram para atrair alunos, WhatsApp para vender e dar suporte, ferramentas genericas para pagamentos, plataformas nao especializadas para aulas, planilhas para acompanhamento e processos manuais para medir evolucao e retencao.

O FutCoach Academy resolve essa fragmentacao com uma plataforma B2B2C:

- a empresa dona do sistema vende a plataforma para treinadores independentes;
- cada treinador opera como criador dentro de um tenant isolado;
- os treinadores vendem cursos, programas e assinaturas aos seus proprios alunos;
- os alunos finais acessam conteudos, materiais, progresso e gamificacao de acordo com compras, assinaturas ou liberacoes manuais.

O produto deve ser construido como uma API robusta, versionada e extensivel. Qualquer necessidade de landing page, area do aluno, dashboard, gamificacao ou consumo de conteudo deve ser representada por contratos e endpoints de API, sem frontend dentro do escopo.

## 3. Objetivo do sistema

O objetivo do FutCoach Academy e permitir que treinadores independentes transformem metodologia, autoridade e conteudo tecnico em um negocio digital escalavel, com operacao completa de criacao, venda, recebimento, entrega, acompanhamento e reengajamento de alunos.

Resultados esperados:

- padronizar a operacao digital de treinadores de futebol;
- reduzir dependencia de ferramentas soltas;
- permitir que cada criador receba no proprio gateway desde o primeiro dia;
- viabilizar compra avulsa e assinatura no mesmo dominio de produto;
- aumentar visibilidade sobre vendas, progresso, engajamento e retencao;
- criar uma base tecnica segura para muitos criadores e alunos sem redesenho estrutural;
- preservar isolamento estrito de dados, marca, alunos, pagamentos e conteudos por tenant.

## 4. Publico-alvo

### Administradores da plataforma

Equipe responsavel pela operacao global do FutCoach Academy. Gerencia tenants, planos, saude operacional, suporte, auditoria, integracoes, configuracoes globais e investigacoes.

### Treinadores independentes como clientes criadores

Clientes B2B da plataforma. Criam cursos, programas de treino, ofertas, landing pages por template, regras de gamificacao, configuracoes de marca, gateway proprio e automacoes para seus alunos.

### Equipe interna do treinador

Usuarios convidados pelo treinador para operar partes do tenant, como gestao de alunos, suporte, conteudo, relatorios ou financeiro, sempre com permissoes granulares.

### Instrutores auxiliares

Profissionais que ajudam na criacao ou acompanhamento pedagogico de cursos, aulas, desafios e progresso de alunos, sem acesso obrigatorio a dados financeiros.

### Financeiro do treinador

Usuario com acesso a pedidos, assinaturas, transacoes, conciliacao, relatorios financeiros, reembolsos e configuracao operacional do gateway quando autorizado.

### Alunos finais

Atletas de base, jogadores amadores e pessoas interessadas em evoluir no futebol com orientacao estruturada. Compram, assinam ou recebem acesso liberado pelo criador.

## 5. Tipo de arquitetura

O sistema deve ser especificado e implementado como:

- SaaS API-only;
- multi-tenant em banco compartilhado;
- backend monolitico modular em Django;
- sem frontend dentro do escopo;
- API versionada em `/api/v1/`;
- landing pages, area do aluno, gamificacao, dashboards e conteudo expostos por endpoints e contratos de API;
- Django Admin usado apenas como painel operacional interno.

O backend nao deve renderizar interfaces publicas, dashboards visuais, landing pages visuais, kanban, chat visual ou SPA. A responsabilidade do backend e fornecer dados, comandos, validacoes, regras de negocio, eventos, integracoes, metricas, auditoria e documentacao OpenAPI.

## 6. Stack tecnica fixa

Stack obrigatoria:

- Backend API-only em Django.
- Django REST Framework.
- Python >= 3.13.
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
- Redes overlay separadas: publica, interna e egress.
- App na rede publica e interna.
- Celery worker e beat na rede interna e egress.
- Banco, Redis e RabbitMQ apenas na rede interna.
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
- Testes minimos obrigatorios para fluxos criticos.

## 7. Requisitos funcionais

### Gestao de tenants/criadores

- Cadastrar, ativar, suspender e arquivar tenants.
- Configurar nome comercial, slug publico, dominio/subdominio, timezone, identidade basica de marca, parametros comerciais e status operacional.
- Associar cada recurso de negocio a um tenant obrigatorio.
- Permitir tenant demo para testes internos, seed inicial e validacao de onboarding.
- Registrar trilha de auditoria para alteracoes sensiveis do tenant.

### Gestao de equipe interna com perfis e permissoes

- Convidar membros por email.
- Atribuir roles e permissoes por tenant.
- Permitir perfis como dono, gestor, instrutor auxiliar, financeiro e suporte.
- Restringir acesso por recurso, acao e escopo.
- Registrar aceite de convite, alteracao de permissao e remocao de acesso.

### Cursos, modulos e aulas

- Criar cursos com titulo, descricao, nivel, categoria, capa, status, idioma, carga estimada e publico recomendado.
- Organizar cursos em modulos e aulas.
- Controlar status de rascunho, revisao interna, publicado e arquivado.
- Associar materiais complementares, videos, PDFs, planilhas e desafios.
- Permitir ordenacao, busca, filtros e publicacao segura.

### Biblioteca de arquivos e materiais

- Subir videos, PDFs, planilhas, imagens, banners e materiais de marca.
- Associar arquivos a aulas, cursos, landing pages, marca e relatorios.
- Aplicar politicas de acesso por tenant, usuario, matricula e permissao.
- Expor download apenas por endpoint protegido e auditavel.

### Landing pages gerenciadas por templates via API

- Permitir que o criador escolha templates predefinidos.
- Configurar secoes, copy, beneficios, provas, FAQ, CTA, imagens e ofertas vinculadas via API.
- Publicar, despublicar, versionar e auditar alteracoes.
- Expor endpoints publicos somente para conteudo publicado e ofertas ativas.
- Nao incluir editor visual livre no MVP.

### Checkout, pedidos e assinaturas

- Permitir compra avulsa de curso, pacote ou programa.
- Permitir assinatura recorrente por oferta.
- Conectar cada tenant ao proprio gateway brasileiro.
- Criar pedidos idempotentes.
- Processar webhooks com validacao, retries e conciliacao.
- Liberar, renovar, suspender ou revogar acesso conforme status financeiro.

### Area do aluno exposta por API

- Expor cursos adquiridos, aulas liberadas, progresso, materiais, badges, desafios e notificacoes.
- Validar que o aluno so acessa conteudo comprado, assinado ou liberado manualmente.
- Registrar eventos reais de consumo para progresso e gamificacao.

### Progresso e acompanhamento

- Registrar inicio, conclusao, tempo assistido, materiais baixados, desafios enviados e aulas revisitadas.
- Permitir que equipe autorizada acompanhe progresso por aluno, curso e periodo.
- Gerar indicadores de conclusao, engajamento, risco de abandono e retorno.

### Gamificacao

- Configurar badges, desafios, streaks e regras por tenant.
- Disparar conquistas apenas com base em eventos reais.
- Auditar eventos de gamificacao relevantes.
- Permitir evolucao futura para rankings e certificados sem comprometer o MVP.

### Dashboards e relatorios via API

- Fornecer endpoints de metricas para vendas, receita recorrente, conversao, progresso, retencao e engajamento.
- Gerar relatorios PDF com ReportLab e PyPDF.
- Exportar CSV quando aplicavel.
- Controlar acesso por permissao, periodo, tenant e filtros.

### Notificacoes e automacoes

- Enviar emails transacionais e mensagens via WhatsApp quando configurado.
- Automatizar avisos de compra, acesso, renovacao, falha de pagamento, aluno inativo, conquista e resumo semanal.
- Registrar status, tentativas, falhas e correlacao por tenant.

### Configuracoes financeiras e de marca

- Configurar gateway, credenciais protegidas, moeda, regras de cupom, politica comercial e dados de exibicao publica.
- Configurar identidade basica por tenant: nome, logo, cores, imagens, textos institucionais e links.
- Suportar identidade de marca sem virar white label completo no MVP.

## 8. Requisitos nao funcionais

- **Performance:** endpoints de listagem devem usar paginacao, filtros indexaveis, select/prefetch quando aplicavel e cache seguro por tenant para leituras frequentes.
- **Seguranca:** todo recurso privado deve validar autenticacao, permissao e ownership por tenant.
- **Rastreabilidade:** acoes sensiveis devem gerar audit log com usuario, tenant, IP, user-agent, recurso, acao, antes/depois quando aplicavel e correlation id.
- **Escalabilidade:** o monolito modular deve permitir crescimento por dominio sem separar servicos prematuramente.
- **Observabilidade:** logs estruturados, healthchecks, status de filas, rastreio de webhooks, metricas de jobs e erros por tenant.
- **Auditabilidade:** alteracoes em cursos, ofertas, landing pages, gateways, permissoes, acessos e IA devem ser consultaveis.
- **Backups:** banco, media, configuracoes, auditoria e secrets operacionais devem ter rotinas documentadas de backup e restore.
- **Confiabilidade operacional:** webhooks, liberacao de acesso, renovacoes e automacoes devem ser idempotentes e reprocessaveis.
- **Manutencao:** codigo em ingles, apps por dominio, services para regras de negocio, managers/querysets para consultas reutilizaveis e testes nos fluxos criticos.
- **Governanca de IA:** tools e prompts devem operar apenas com dados do tenant autorizado, com logs, auditoria e bloqueio absoluto de acesso cruzado.

## 9. Modelo multi-tenant

O FutCoach Academy usa multi-tenancy por criador/cliente em banco compartilhado. Cada registro de negocio sensivel deve ter vinculo direto ou indireto com `Tenant`.

Estrategia obrigatoria:

- tenancy por criador independente;
- `tenant_id` obrigatorio nos recursos do dominio;
- resolucao de tenant por contexto autenticado, dominio, subdominio ou slug publico;
- managers/querysets que apliquem escopo de tenant em consultas comuns;
- permissions e services que validem ownership em comandos;
- endpoints publicos que resolvam tenant e exponham somente recursos publicados;
- testes que comprovem isolamento entre tenants.

Dados segregados por tenant:

- marca e configuracoes;
- catalogo de cursos, modulos, aulas e materiais;
- ofertas, cupons, landing pages e secoes;
- alunos, matriculas e progresso;
- pedidos, assinaturas, transacoes e conciliacao;
- gateway e credenciais;
- notificacoes, automacoes, relatorios e eventos de IA.

Regras anti-vazamento:

- nenhum endpoint autenticado pode aceitar `tenant_id` livre sem validar permissao administrativa global;
- usuarios internos so acessam tenants aos quais pertencem;
- alunos so acessam o tenant onde possuem vinculo;
- arquivos privados devem exigir tenant, permissao e direito de acesso ao recurso associado;
- webhooks devem mapear a conta de pagamento ao tenant correto antes de qualquer mutacao;
- cache keys devem incluir tenant e escopo;
- logs nao devem gravar secrets ou dados sensiveis desnecessarios.

## 10. Perfis de usuarios e permissoes

Modelo recomendado: RBAC por tenant com permissoes granulares e permissoes globais separadas para administracao da plataforma.

Perfis:

- **Admin da plataforma:** administra tenants, planos, suporte global, auditoria, configuracoes globais, saude operacional e investigacoes.
- **Treinador dono da conta:** controla marca, equipe, cursos, ofertas, landing pages, gateway, relatorios e automacoes do proprio tenant.
- **Gestor/equipe do treinador:** opera alunos, cursos, paginas e relatorios conforme permissoes concedidas.
- **Instrutor auxiliar:** cria ou acompanha conteudos, desafios e progresso pedagogico, sem acesso financeiro por padrao.
- **Financeiro do treinador:** acessa pedidos, assinaturas, transacoes, conciliacao, relatorios financeiros e configuracao financeira autorizada.
- **Aluno final:** consome conteudo liberado, acompanha progresso, recebe notificacoes, participa de desafios e visualiza conquistas.

Permissoes principais:

- administracao global da plataforma;
- gestao de marca e configuracoes do tenant;
- criacao, edicao, publicacao e arquivamento de cursos;
- gestao de aulas, modulos e materiais;
- operacao de alunos e matriculas;
- acesso financeiro;
- leitura e exportacao de relatorios;
- acompanhamento pedagogico;
- configuracao de gamificacao;
- gestao de automacoes;
- consumo de conteudo pelo aluno.

## 11. Entidades principais

- **Tenant/Creator:** representa o treinador/cliente criador e suas configuracoes comerciais, operacionais e de marca.
- **InternalUser:** usuario interno vinculado a um ou mais tenants com roles e permissoes.
- **Role/Permission:** conjunto de permissoes por perfil e tenant.
- **Course:** curso ou programa principal vendido pelo criador.
- **Module:** agrupamento ordenado de aulas dentro de um curso.
- **Lesson:** unidade de conteudo com video, materiais, descricoes e regras de conclusao.
- **Offer:** regra comercial que vende curso unico, pacote ou assinatura.
- **LandingPageTemplateBinding:** configuracao que vincula tenant, template e ofertas publicadas.
- **LandingPageSection:** secao configuravel da landing page por template.
- **Student:** aluno final dentro do contexto de um tenant.
- **Enrollment:** matricula que concede acesso a curso, pacote ou programa.
- **Order:** pedido de compra avulsa ou origem de assinatura.
- **Subscription:** contrato recorrente com status, ciclo, renovacao e falhas.
- **PaymentAccount:** conta/gateway proprio configurado pelo criador.
- **PaymentTransaction:** transacao financeira, evento de pagamento, estorno ou conciliacao.
- **ProgressEvent:** evento real de consumo, conclusao ou interacao do aluno.
- **Badge:** conquista atribuida por regra de gamificacao.
- **Challenge:** desafio de treino, pratica, tarefa ou marco.
- **Notification:** comunicacao enviada ou agendada.
- **MediaAsset:** arquivo privado ou publico controlado por tenant e uso.
- **SupportEvent:** evento operacional de suporte, atendimento ou incidente.
- **AnalyticsSnapshot:** agregado periodico para metricas e dashboards via API.

Todos os models devem possuir `created_at` e `updated_at`.

## 12. Relacionamentos entre entidades

- Um criador possui varios cursos, paginas, ofertas, membros de equipe e alunos.
- Um curso possui varios modulos.
- Um modulo possui varias aulas.
- Uma aula pode possuir varios materiais e eventos de progresso.
- Uma oferta pode vender curso unico, pacote de cursos ou assinatura.
- Uma landing page pertence a um tenant e publica uma ou mais ofertas ativas.
- Um aluno pode ter varias matriculas dentro de um mesmo criador.
- Um pedido ou assinatura gera acesso conforme regra da oferta.
- Uma matricula conecta aluno, curso/oferta, origem financeira e status de acesso.
- Progresso, badges e desafios se conectam ao aluno dentro do curso e do tenant.
- Uma conta de pagamento do criador se relaciona com pedidos, webhooks, transacoes e conciliacao.
- Analytics snapshots agregam eventos de pedidos, progresso, acesso, landing pages e gamificacao.

## 13. Endpoints principais da API

Todos os endpoints devem estar sob `/api/v1/`, salvo `/health/` publico.

### Autenticacao e sessao

- `POST /api/v1/auth/login/`
- `POST /api/v1/auth/refresh/`
- `POST /api/v1/auth/logout/`
- `POST /api/v1/auth/password/reset/`
- `POST /api/v1/auth/password/confirm-reset/`
- `GET /api/v1/auth/me/`
- `PATCH /api/v1/auth/me/`
- `GET /api/v1/auth/sessions/`
- `DELETE /api/v1/auth/sessions/{id}/`

Contratos: login por email, access token curto, refresh token renovavel, invalidacao em logout, resposta de erro em portugues brasileiro e rate limiting.

### Tenants e configuracoes de marca

- `GET /api/v1/tenants/`
- `POST /api/v1/tenants/`
- `GET /api/v1/tenants/{id}/`
- `PATCH /api/v1/tenants/{id}/`
- `POST /api/v1/tenants/{id}/activate/`
- `POST /api/v1/tenants/{id}/suspend/`
- `GET /api/v1/tenant/settings/`
- `PATCH /api/v1/tenant/settings/`
- `GET /api/v1/tenant/brand/`
- `PATCH /api/v1/tenant/brand/`
- `GET /api/v1/public/creators/{slug}/`

### Usuarios internos, roles e permissoes

- `GET /api/v1/team/members/`
- `POST /api/v1/team/invitations/`
- `POST /api/v1/team/invitations/{token}/accept/`
- `PATCH /api/v1/team/members/{id}/`
- `DELETE /api/v1/team/members/{id}/`
- `GET /api/v1/roles/`
- `POST /api/v1/roles/`
- `PATCH /api/v1/roles/{id}/`
- `GET /api/v1/permissions/`

### Cursos, modulos, aulas e materiais

- `GET /api/v1/courses/`
- `POST /api/v1/courses/`
- `GET /api/v1/courses/{id}/`
- `PATCH /api/v1/courses/{id}/`
- `POST /api/v1/courses/{id}/publish/`
- `POST /api/v1/courses/{id}/archive/`
- `GET /api/v1/courses/{id}/modules/`
- `POST /api/v1/courses/{id}/modules/`
- `PATCH /api/v1/modules/{id}/`
- `POST /api/v1/modules/{id}/reorder/`
- `GET /api/v1/modules/{id}/lessons/`
- `POST /api/v1/modules/{id}/lessons/`
- `GET /api/v1/lessons/{id}/`
- `PATCH /api/v1/lessons/{id}/`
- `POST /api/v1/lessons/{id}/publish/`
- `POST /api/v1/lessons/{id}/attach-media/`

### Midia, uploads, arquivos e streaming metadata

- `POST /api/v1/media/uploads/`
- `GET /api/v1/media/assets/`
- `GET /api/v1/media/assets/{id}/`
- `PATCH /api/v1/media/assets/{id}/`
- `DELETE /api/v1/media/assets/{id}/`
- `GET /api/v1/media/assets/{id}/download/`
- `GET /api/v1/media/assets/{id}/streaming-metadata/`
- `GET /api/v1/media/assets/{id}/audit/`

Arquivos privados nunca devem ser servidos por URL publica direta.

### Ofertas, precos, cupons e regras comerciais

- `GET /api/v1/offers/`
- `POST /api/v1/offers/`
- `GET /api/v1/offers/{id}/`
- `PATCH /api/v1/offers/{id}/`
- `POST /api/v1/offers/{id}/activate/`
- `POST /api/v1/offers/{id}/deactivate/`
- `GET /api/v1/coupons/`
- `POST /api/v1/coupons/`
- `PATCH /api/v1/coupons/{id}/`
- `POST /api/v1/public/offers/{slug}/validate-coupon/`

### Landing pages por template

- `GET /api/v1/pages/`
- `POST /api/v1/pages/`
- `GET /api/v1/pages/{id}/`
- `PATCH /api/v1/pages/{id}/`
- `POST /api/v1/pages/{id}/preview-data/`
- `POST /api/v1/pages/{id}/publish/`
- `POST /api/v1/pages/{id}/unpublish/`
- `GET /api/v1/pages/{id}/sections/`
- `POST /api/v1/pages/{id}/sections/`
- `PATCH /api/v1/pages/sections/{id}/`
- `POST /api/v1/pages/{id}/reorder-sections/`
- `GET /api/v1/public/pages/{tenant_slug}/{page_slug}/`
- `GET /api/v1/public/pages/{tenant_slug}/{page_slug}/offers/`

### Checkout, pedidos, assinaturas e webhooks

- `POST /api/v1/checkout/orders/`
- `GET /api/v1/checkout/orders/{id}/`
- `POST /api/v1/checkout/orders/{id}/confirm/`
- `POST /api/v1/checkout/orders/{id}/cancel/`
- `GET /api/v1/orders/`
- `GET /api/v1/orders/{id}/`
- `GET /api/v1/subscriptions/`
- `POST /api/v1/subscriptions/{id}/cancel/`
- `POST /api/v1/subscriptions/{id}/retry-payment/`
- `GET /api/v1/payment/accounts/`
- `POST /api/v1/payment/accounts/`
- `PATCH /api/v1/payment/accounts/{id}/`
- `POST /api/v1/webhooks/payments/{provider}/`
- `GET /api/v1/webhooks/events/`
- `POST /api/v1/webhooks/events/{id}/replay/`

Checkout e webhooks devem ser idempotentes, rastreaveis e resilientes a retries.

### Alunos, matriculas e progresso

- `GET /api/v1/students/`
- `POST /api/v1/students/`
- `GET /api/v1/students/{id}/`
- `PATCH /api/v1/students/{id}/`
- `GET /api/v1/students/{id}/enrollments/`
- `POST /api/v1/students/{id}/manual-enrollments/`
- `POST /api/v1/enrollments/{id}/revoke/`
- `GET /api/v1/student-area/courses/`
- `GET /api/v1/student-area/courses/{id}/`
- `GET /api/v1/student-area/lessons/{id}/`
- `POST /api/v1/student-area/lessons/{id}/progress/`
- `POST /api/v1/student-area/lessons/{id}/complete/`
- `GET /api/v1/progress/events/`

### Gamificacao

- `GET /api/v1/gamification/badges/`
- `POST /api/v1/gamification/badges/`
- `PATCH /api/v1/gamification/badges/{id}/`
- `GET /api/v1/gamification/challenges/`
- `POST /api/v1/gamification/challenges/`
- `PATCH /api/v1/gamification/challenges/{id}/`
- `POST /api/v1/gamification/challenges/{id}/publish/`
- `GET /api/v1/student-area/badges/`
- `GET /api/v1/student-area/challenges/`
- `POST /api/v1/student-area/challenges/{id}/submit/`
- `GET /api/v1/gamification/events/`

### Notificacoes, automacoes e campanhas

- `GET /api/v1/notifications/`
- `POST /api/v1/notifications/test/`
- `GET /api/v1/automation/rules/`
- `POST /api/v1/automation/rules/`
- `PATCH /api/v1/automation/rules/{id}/`
- `POST /api/v1/automation/rules/{id}/activate/`
- `POST /api/v1/automation/rules/{id}/deactivate/`
- `GET /api/v1/automation/events/`

### Dashboards, relatorios, metricas e exportacoes

- `GET /api/v1/metrics/sales/`
- `GET /api/v1/metrics/recurring-revenue/`
- `GET /api/v1/metrics/course-revenue/`
- `GET /api/v1/metrics/landing-page-conversion/`
- `GET /api/v1/metrics/students/active/`
- `GET /api/v1/metrics/progress/completion-rate/`
- `GET /api/v1/metrics/gamification/engagement/`
- `GET /api/v1/reports/`
- `POST /api/v1/reports/{type}/generate/`
- `GET /api/v1/reports/jobs/{id}/`
- `GET /api/v1/reports/jobs/{id}/download/`
- `GET /api/v1/exports/{type}.csv`

### IA aplicada

- `POST /api/v1/ai/landing-copy/generate/`
- `POST /api/v1/ai/course-description/generate/`
- `POST /api/v1/ai/engagement-summary/run/`
- `POST /api/v1/ai/churn-risk/run/`
- `POST /api/v1/ai/recommendations/run/`
- `GET /api/v1/ai/jobs/{id}/`
- `GET /api/v1/ai/jobs/{id}/result/`
- `GET /api/v1/ai/chat/sessions/`
- `POST /api/v1/ai/chat/sessions/`
- `POST /api/v1/ai/chat/sessions/{id}/messages/`

### Suporte operacional e auditoria

- `GET /api/v1/support/events/`
- `POST /api/v1/support/events/`
- `GET /api/v1/audit/events/`
- `GET /api/v1/audit/events/{id}/`
- `GET /api/v1/system/status/`
- `GET /health/`

### Integracoes externas

Gateway de pagamento brasileiro:

- autenticacao por credenciais protegidas por tenant;
- webhooks assinados;
- retries com backoff;
- conciliacao periodica;
- idempotency keys em pedidos, transacoes e eventos;
- alertas para divergencias financeiras.

Email transacional:

- templates por evento;
- status de entrega;
- logs sem dados sensiveis desnecessarios;
- retries assincronos.

WhatsApp:

- envio via provider externo;
- opt-in/opt-out por aluno;
- limites por tenant;
- registro de campanhas e falhas.

Hospedagem/entrega de video:

- armazenamento privado;
- metadata de streaming por API;
- autorizacao antes de expor qualquer URL temporaria;
- logs de acesso quando aplicavel.

Analytics basico:

- eventos de pagina publica, checkout, progresso e gamificacao;
- agregacoes em `AnalyticsSnapshot`;
- filtros por tenant e periodo.

Integracoes futuras:

- CRM;
- afiliados;
- ferramentas de anuncios;
- automacao de marketing;
- push notifications.

## 14. Padrao profissional da API

- **Versionamento:** toda API funcional deve ficar em `/api/v1/`; mudancas incompativeis exigem nova versao.
- **Serializacao:** serializers explicitos por caso de uso, evitando expor campos internos ou secrets.
- **Paginacao:** listagens devem usar paginacao padronizada com `count`, `next`, `previous` e `results`.
- **Filtros:** filtros por status, periodo, curso, oferta, aluno, tenant implicito, busca textual e ordenacao quando fizer sentido.
- **Ordenacao:** parametros consistentes, como `ordering=created_at` ou `ordering=-created_at`.
- **Erros padronizados:** respostas com `code`, `message`, `fields`, `correlation_id` e mensagens em portugues brasileiro.
- **Validacoes:** validações de dominio em serializers e services, com mensagens claras e codigos estaveis.
- **Idempotencia:** obrigatoria para checkout, webhooks, liberacao de acesso, retries financeiros, relatorios pesados e jobs de IA.
- **Naming:** recursos no plural, acoes de workflow como sub-rotas verbais e nomes consistentes em ingles no codigo.
- **OpenAPI:** contratos completos com schemas, exemplos de request/response, autenticacao, erros, filtros e permissoes.
- **Endpoints publicos:** devem expor apenas dados publicados e necessarios.
- **Endpoints sensiveis de pagamento:** devem exigir validacao adicional, auditoria e rate limiting.

## 15. Autenticacao e autorizacao

Autenticacao obrigatoria:

- login por email usando usuarios nativos do Django;
- JWT com access token e refresh token;
- refresh token com rotacao/invalidation quando necessario;
- logout com invalidacao;
- troca e recuperacao segura de senha;
- rate limiting em login, refresh e reset de senha.

Autorizacao:

- usuarios internos autenticados devem possuir vinculo ativo com tenant;
- alunos autenticados devem acessar apenas recursos comprados, assinados ou liberados;
- admins da plataforma devem usar permissoes globais separadas do escopo do tenant;
- equipe interna deve obedecer RBAC e permissoes por acao;
- paginas publicas e checkout devem resolver tenant de forma segura por slug, dominio ou subdominio;
- endpoints autenticados nao devem confiar em tenant informado pelo cliente quando o tenant pode ser derivado do contexto.

## 16. Seguranca e isolamento de dados

Exigencias:

- isolamento estrito por tenant em queries, services, permissions, cache e jobs;
- validacao de ownership em todo comando;
- rate limiting para login, checkout, webhooks, reset de senha, downloads, IA e endpoints publicos suscetiveis a abuso;
- trilha de auditoria para acoes sensiveis;
- arquivos acessados apenas por endpoints protegidos;
- secrets em Docker Secrets ou variaveis seguras por ambiente;
- CORS configuravel por ambiente;
- protecao contra abuso, enumeracao, scraping e acesso indevido;
- mascaramento de dados sensiveis em logs;
- testes automatizados de acesso cruzado.

Exigencias especificas:

- webhooks devem validar assinatura, origem, timestamp e idempotencia;
- gateway proprio por tenant deve impedir que credenciais ou eventos de um criador afetem outro;
- dados sensiveis de alunos devem ter acesso minimo necessario;
- paginas publicas podem expor ofertas ativas, mas nunca conteudo privado;
- IA deve acessar apenas dados do tenant autorizado, com logs, auditoria e tools restritas.

## 17. Gestao de arquivos e anexos

Tipos suportados:

- videos de aula;
- PDFs de treino;
- planilhas de acompanhamento;
- imagens de capa e banners;
- materiais complementares;
- arquivos de marca do criador;
- certificados futuros.

Requisitos:

- upload associado ao tenant e usuario responsavel;
- validacao de tipo, tamanho, extensao e finalidade;
- armazenamento em volume nomeado de media no ambiente definido;
- metadados em `MediaAsset`;
- associacao com curso, aula, landing page, marca, relatorio ou aluno quando aplicavel;
- download por endpoint protegido;
- autorizacao por tenant, permissao e direito de acesso ao conteudo;
- exclusao logica quando houver dependencias;
- auditoria de upload, download sensivel, exclusao e vinculacao;
- politica para arquivos privados de alunos e materiais pagos;
- suporte futuro a URLs temporarias apenas apos autorizacao.

## 18. Dashboards e metricas via API

Toda necessidade visual deve ser convertida em endpoints de metricas.

Indicadores obrigatorios:

- vendas por periodo;
- receita recorrente;
- receita por curso;
- conversao por landing page;
- ticket medio;
- alunos ativos;
- taxa de conclusao;
- aulas mais consumidas;
- cancelamento de assinatura;
- retencao e retorno de alunos;
- engajamento em desafios e badges.

Padrao dos endpoints:

- filtros por periodo, curso, oferta, landing page e status;
- tenant derivado do contexto;
- permissao de leitura de relatorios;
- agregacoes cacheaveis com expiracao segura;
- respostas com valores brutos e metadados de periodo;
- possibilidade de snapshots para dados pesados.

## 19. Relatorios e exportacoes

### Relatorio de vendas

- Objetivo: acompanhar receita, pedidos, status e canais.
- Filtros: periodo, curso, oferta, status, cupom e landing page.
- Dados: pedidos, valores, descontos, impostos quando aplicavel, status e origem.
- Formatos: PDF e CSV.
- Perfis autorizados: dono, financeiro, gestor autorizado.

### Relatorio de assinaturas

- Objetivo: acompanhar MRR, renovacoes, falhas e cancelamentos.
- Filtros: periodo, status, oferta e curso.
- Dados: assinaturas ativas, churn, renovacoes, falhas, retries e receita.
- Formatos: PDF e CSV.
- Perfis autorizados: dono, financeiro.

### Relatorio de conversao por pagina

- Objetivo: medir eficacia das landing pages por template.
- Filtros: pagina, oferta, periodo e fonte quando disponivel.
- Dados: visitas, cliques em CTA, inicios de checkout, compras e conversao.
- Formatos: PDF e CSV.
- Perfis autorizados: dono, gestor, marketing autorizado.

### Relatorio de progresso dos alunos

- Objetivo: acompanhar aprendizagem e consumo.
- Filtros: curso, aluno, turma/grupo futuro, periodo e status.
- Dados: aulas concluidas, tempo estimado, desafios, badges e inatividade.
- Formatos: PDF e CSV.
- Perfis autorizados: dono, gestor, instrutor auxiliar.

### Relatorio de cursos mais vendidos

- Objetivo: identificar produtos de maior resultado comercial.
- Filtros: periodo, categoria e tipo de oferta.
- Dados: vendas, receita, conversao, reembolsos e ticket medio.
- Formatos: PDF e CSV.
- Perfis autorizados: dono, financeiro, gestor autorizado.

### Relatorio de retencao/churn

- Objetivo: monitorar continuidade e risco de cancelamento.
- Filtros: periodo, oferta, assinatura e curso.
- Dados: churn, renovacoes, alunos inativos, falhas de pagamento e retorno.
- Formatos: PDF e CSV.
- Perfis autorizados: dono, financeiro, gestor autorizado.

### Relatorio de engajamento por aula

- Objetivo: entender consumo e qualidade percebida do conteudo.
- Filtros: curso, modulo, aula e periodo.
- Dados: visualizacoes, conclusoes, abandono, downloads e repeticoes.
- Formatos: PDF e CSV.
- Perfis autorizados: dono, gestor, instrutor auxiliar.

### Relatorio financeiro consolidado do criador

- Objetivo: consolidar receita, transacoes, conciliacao e pendencias.
- Filtros: periodo, gateway, status e tipo de receita.
- Dados: bruto, liquido estimado, taxas quando disponiveis, falhas, estornos e conciliacao.
- Formatos: PDF e CSV.
- Perfis autorizados: dono, financeiro.

## 20. Tarefas assincronas

Tarefas obrigatorias:

- liberacao automatica de acesso apos pagamento aprovado;
- processamento de webhooks;
- aviso de renovacao ou falha de assinatura;
- recuperacao de carrinho abandonado;
- disparo de badge/conquista;
- resumo semanal para o treinador;
- alerta de aluno inativo;
- geracao de relatorios pesados;
- analise de IA para engajamento e abandono;
- conciliacao e retries de eventos de integracao.

Padroes:

- Celery para execucao;
- RabbitMQ como broker;
- Redis como result backend e cache;
- filas separadas por natureza quando util: default, payments, notifications, reports, ai;
- retries com backoff e limite;
- idempotencia por chave de evento;
- jobs com status consultavel;
- falhas registradas com correlation id, tenant e causa;
- beat scheduler para rotinas recorrentes;
- dj-celery-panel no Django Admin para operacao interna;
- nunca expor workers ou beat na rede publica.

## 21. Recursos de IA

Stack fixa:

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

Casos de uso:

- geracao assistida de copy para landing page;
- geracao assistida de descricao de curso e aulas;
- resumo de engajamento dos alunos;
- sinal de risco de abandono;
- recomendacao de proximos programas e cursos;
- assistente futuro de suporte interno para o criador, sempre restrito aos dados do proprio tenant.

Governanca:

- todo job de IA deve registrar solicitante, tenant, objetivo, inputs permitidos e resultado;
- prompts devem evitar incluir dados sensiveis desnecessarios;
- tools devem filtrar dados por tenant antes de montar contexto;
- respostas devem ser armazenadas com status, versao do modelo e metadados;
- endpoints de IA devem ter rate limiting e permissoes proprias.

## 22. Logs, auditoria e rastreabilidade

Logs estruturados devem conter:

- timestamp;
- nivel;
- servico;
- ambiente;
- tenant quando aplicavel;
- usuario quando aplicavel;
- correlation id;
- request id;
- evento;
- status;
- duracao;
- erro normalizado quando houver.

Eventos auditaveis:

- criacao e alteracao de cursos;
- alteracao de ofertas e precos;
- publicacao de landing pages;
- configuracao de gateway;
- liberacao e revogacao de acesso;
- alteracao de permissoes;
- execucao de automacoes;
- uso de IA;
- eventos de suporte;
- conciliacao financeira;
- download de arquivos sensiveis;
- replay de webhooks.

Investigacao operacional:

- todo webhook deve ser rastreavel da entrada ate a mutacao final;
- todo pedido deve apontar para transacoes, eventos e matriculas geradas;
- toda matricula automatica deve apontar para pedido, assinatura ou liberacao manual;
- toda acao administrativa sensivel deve indicar autor e motivo quando aplicavel.

## 23. Documentacao OpenAPI/Swagger

O projeto deve usar drf-spectacular para documentacao OpenAPI.

Obrigatorio documentar:

- autenticacao JWT;
- headers e contexto de tenant quando aplicavel;
- schemas de request e response;
- erros padronizados;
- paginacao, filtros e ordenacao;
- webhooks e payloads de pagamento;
- checkout;
- gestao de cursos;
- landing pages por template;
- progresso;
- gamificacao;
- dashboards;
- relatorios;
- IA;
- arquivos protegidos;
- auditoria e suporte.

Cada endpoint critico deve ter exemplos de payloads validos, exemplos de erro e descricao das permissoes necessarias.

## 24. Documentacao tecnica com MKDocs

O projeto deve incluir MKDocs com suporte a Mermaid para documentacao tecnica.

Estrutura sugerida:

- visao geral da arquitetura;
- modelo multi-tenant;
- apps Django e responsabilidades;
- jornada do criador;
- jornada do aluno;
- fluxos de pagamento;
- fluxo de publicacao de landing page por template;
- fluxo de gamificacao;
- governanca de IA;
- rotinas operacionais;
- deploy local;
- deploy em producao;
- troubleshooting;
- backups e restore;
- seguranca e auditoria.

Diagramas Mermaid obrigatorios:

- arquitetura de containers;
- fluxo de checkout e webhook;
- fluxo de liberacao de acesso;
- fluxo de publicacao de landing page;
- fluxo de progresso e gamificacao;
- fluxo de IA assincrona;
- redes Docker Swarm.

## 25. Estrutura sugerida de apps Django

Apps obrigatorios:

- `core`: app principal do projeto.
- `base`: app compartilhado com models abstratos, mixins, utils, exceptions e helpers comuns.

Apps por contexto:

- `identity`: usuarios, autenticacao, sessoes e perfis.
- `tenants`: criadores, configuracoes, resolucao de tenant e marca.
- `catalog`: cursos, modulos, aulas e estrutura pedagogica.
- `content`: conteudo de aulas, materiais e publicacao.
- `offers`: ofertas, precos, cupons e regras comerciais.
- `checkout`: pedidos, checkout e comandos de compra.
- `billing`: assinaturas, transacoes, gateways e conciliacao.
- `students`: alunos, matriculas e area do aluno.
- `progress`: eventos de progresso e acompanhamento.
- `gamification`: badges, desafios, streaks e conquistas.
- `pages`: landing pages por template, secoes e publicacao.
- `notifications`: notificacoes, automacoes e campanhas.
- `reporting`: metricas, relatorios, exportacoes e snapshots.
- `ai`: jobs de IA, sessoes, tools, resultados e auditoria.
- `media`: uploads, arquivos, anexos e downloads protegidos.
- `support`: eventos de suporte e operacao.
- `audit`: trilhas de auditoria e eventos sensiveis.

Padroes internos:

- `services.py` ou pacote `services/` para regras de negocio;
- `managers.py` e `querysets.py` para consultas reutilizaveis;
- `serializers.py` por contratos de API;
- `permissions.py` por regras de acesso;
- `filters.py` para filtros de listagem;
- `viewsets.py` e `views.py` conforme complexidade;
- `tasks.py` para Celery;
- testes por app e fluxo critico.

## 26. Deploy local com Docker Compose

Ambiente local deve conter:

- app Django;
- PostgreSQL;
- Redis;
- RabbitMQ;
- Celery worker;
- Celery beat;
- servico para healthchecks quando aplicavel;
- volumes nomeados para banco, Redis, RabbitMQ, media e staticfiles;
- rede interna local;
- `.env` gitignored;
- entrypoint com `wait_for_db`;
- migrations com advisory lock;
- `collectstatic --clear`;
- seed inicial opcional.

Fluxo local:

1. criar `.env` a partir de exemplo seguro;
2. subir containers;
3. aguardar banco com `wait_for_db`;
4. executar migrations com advisory lock;
5. executar `collectstatic --clear`;
6. criar superusuario quando necessario;
7. seedar tenant demo, criador demo, curso demo, oferta demo e aluno demo;
8. executar testes;
9. validar `/health/`;
10. validar processamento de fila, webhook local e job de relatorio.

O ambiente local deve permitir desenvolvimento completo de cursos, pagamentos, webhooks, relatorios, IA, uploads, automacoes e endpoints publicos de landing page.

## 27. Deploy em producao com Docker Swarm

Servicos:

- API Django;
- Celery worker;
- Celery beat;
- PostgreSQL;
- Redis;
- RabbitMQ;
- Traefik;
- servico auxiliar de manutencao quando necessario.

Redes overlay:

- `public`: Traefik e app;
- `internal`: app, banco, Redis, RabbitMQ, workers e beat;
- `egress`: workers e beat para chamadas externas controladas.

Regras de rede:

- app na rede publica e interna;
- Celery worker e beat na rede interna e egress;
- banco, Redis e RabbitMQ apenas na rede interna;
- nunca expor Celery na rede publica;
- Traefik recebe trafego publico e encaminha para app.

Volumes nomeados:

- PostgreSQL;
- Redis;
- RabbitMQ;
- media;
- staticfiles;
- certificados.

Secrets:

- Django secret key;
- database password;
- Redis credentials quando aplicavel;
- RabbitMQ credentials;
- Cloudflare DNS API Token;
- OpenAI API key;
- gateway credentials por tenant quando nao armazenadas em provider externo seguro;
- GHCR token em ambiente de deploy.

Rollout:

- imagem publicada no GHCR;
- deploy com `docker stack deploy --with-registry-auth`;
- healthcheck antes de considerar servico saudavel;
- migrations protegidas por advisory lock;
- rollback documentado para imagem anterior;
- logs estruturados consultaveis;
- workers atualizados sem expor fila publicamente.

## 28. Guia de deploy em VPS Ubuntu do zero

Passo a passo:

1. Provisionar VPS Ubuntu com recursos adequados para API, banco, Redis, RabbitMQ e workers.
2. Criar usuario operacional sem uso direto de root para deploy.
3. Instalar Docker Engine e plugins necessarios.
4. Inicializar Docker Swarm.
5. Criar redes overlay `public`, `internal` e `egress`.
6. Criar volumes nomeados.
7. Configurar Cloudflare DNS para dominio principal, wildcard e subdominios.
8. Criar Cloudflare DNS API Token com escopo minimo necessario.
9. Salvar token e demais segredos como Docker Secrets.
10. Configurar Traefik com Let's Encrypt DNS-01 e TLS wildcard.
11. Autenticar no GHCR.
12. Publicar imagem da aplicacao.
13. Criar stack file de producao com servicos, healthchecks, redes, volumes e secrets.
14. Executar `docker stack deploy --with-registry-auth`.
15. Validar `/health/`.
16. Validar OpenAPI/Swagger em ambiente protegido conforme politica.
17. Validar worker, beat, filas e jobs de teste.
18. Validar webhook do gateway em modo sandbox.
19. Validar tenant demo, landing page publica por API e checkout.
20. Configurar rotina de backups e teste inicial de restore.

Boas praticas para operacao no Brasil:

- usar timezone `America/Sao_Paulo`;
- preparar dominios e subdominios por tenant;
- manter CORS por ambiente;
- garantir mensagens de API em portugues brasileiro;
- validar gateways brasileiros e webhooks em sandbox antes de producao;
- monitorar filas, pagamentos e falhas de notificacao.

## 29. Scripts obrigatorios

Scripts/comandos operacionais minimos:

- bootstrap local;
- subida de containers;
- parada de containers;
- migrations;
- coleta de estaticos;
- execucao de testes;
- lint/formato;
- criacao de superusuario;
- seed inicial;
- deploy;
- rollback;
- verificacao de saude;
- status de filas;
- replay de webhooks em ambiente local;
- geracao de relatorio teste;
- execucao de job de IA teste;
- backup;
- restore em ambiente de teste.

Seed inicial deve criar:

- tenant demo;
- criador demo;
- membro de equipe demo;
- curso demo;
- modulo e aulas demo;
- oferta de compra avulsa;
- oferta de assinatura;
- landing page demo por template;
- aluno demo;
- pedido demo;
- matricula demo;
- eventos de progresso demo.

## 30. Backups

Estrategia:

- backup do PostgreSQL com retencao definida;
- backup de media privada;
- backup de staticfiles quando necessario;
- backup de configuracoes operacionais;
- backup ou registro seguro de secrets conforme politica;
- retencao separada para auditoria;
- teste periodico de restore.

Consideracoes:

- dados de cursos e alunos sao criticos e devem ter restore validado;
- arquivos privados e midia precisam manter relacao com `MediaAsset`;
- configuracoes de tenant e gateway exigem cuidado extra;
- trilha de auditoria nao deve ser descartada sem politica formal;
- backups devem ser protegidos contra acesso indevido;
- restore deve ser testado em ambiente isolado antes de qualquer acao em producao.

## 31. Testes minimos obrigatorios

Testes obrigatorios:

- multi-tenant isolation;
- permissoes por perfil;
- login por email e JWT;
- refresh token e logout;
- criacao e publicacao de curso;
- criacao, ordenacao e publicacao de modulos/aulas;
- landing page por template via API;
- checkout para compra avulsa;
- assinatura, renovacao, falha e cancelamento;
- webhooks com validacao e idempotencia;
- liberacao e revogacao de acesso;
- aluno acessando apenas conteudo permitido;
- progresso e gamificacao;
- relatorios PDF;
- exportacoes CSV;
- arquivos privados e downloads protegidos;
- jobs Celery criticos;
- IA com controles de tenant;
- auditoria de acoes sensiveis;
- rate limiting em endpoints sensiveis;
- healthcheck publico.

Criterio de qualidade:

- testes devem cobrir sucesso, erro e tentativa de acesso cruzado;
- factories devem representar tenants, usuarios, alunos, cursos, ofertas e pedidos;
- fluxos financeiros devem usar mocks/fakes do gateway;
- jobs assincronos devem ser testados com comportamento idempotente;
- endpoints publicos devem provar que nao vazam dados privados.

## 32. Criterios de aceite

- Um treinador publica e vende o primeiro curso rapidamente.
- O criador recebe no proprio gateway sem friccao.
- O aluno compra e acessa o conteudo sem suporte manual.
- O criador acompanha progresso e vendas por endpoints de metricas claros.
- A retencao melhora com acompanhamento, notificacoes e gamificacao.
- O produto e percebido como feito para futebol.
- O MVP entrega valor suficiente para o criador topar pagar no primeiro mes.
- A plataforma suporta compra avulsa e assinatura desde o MVP.
- O isolamento multi-tenant e comprovavel em testes automatizados.
- O fluxo de landing page por template e comercialmente util sem exigir editor livre.
- Webhooks financeiros sao idempotentes, auditaveis e reprocessaveis.
- Arquivos privados nunca sao expostos publicamente sem autorizacao.
- IA opera com governanca e sem acesso cruzado entre tenants.
- Deploy local e producao possuem healthchecks, secrets e rotinas documentadas.

## 33. Roadmap em sprints

Ordem base:

1. Fundacao do projeto.
2. Autenticacao e usuarios.
3. Multi-tenant.
4. Entidades principais.
5. Regras de negocio centrais.
6. Arquivos e anexos.
7. Relatorios.
8. Dashboards via API.
9. Tarefas assincronas.
10. IA.
11. Seguranca avancada.
12. Documentacao.
13. Deploy local.
14. Deploy em producao.
15. Testes e validacao final.

## 34. Checklist detalhado das sprints

### Sprint 1 - Fundacao do projeto

- tarefa a ser executada: criar projeto Django com `core`, `base`, `.venv`, `requirements.txt` e apenas um `settings.py`.
- tarefa a ser executada: configurar Django REST Framework, drf-spectacular, PostgreSQL, Redis, RabbitMQ e Celery.
- tarefa a ser executada: configurar timezone `America/Sao_Paulo`, logs estruturados e padrao de erros da API.
- tarefa a ser executada: criar `/health/` publico.
- tarefa a ser executada: preparar estrutura inicial de testes.

### Sprint 2 - Autenticacao e usuarios

- tarefa a ser executada: implementar login por email com usuario nativo do Django.
- tarefa a ser executada: implementar JWT com access token e refresh token.
- tarefa a ser executada: implementar logout, refresh, reset de senha e endpoint `/me`.
- tarefa a ser executada: criar modelos base para usuarios internos e alunos.
- tarefa a ser executada: testar autenticacao, refresh, logout e rate limiting.

### Sprint 3 - Multi-tenant

- tarefa a ser executada: criar `Tenant/Creator` e resolucao de tenant por contexto.
- tarefa a ser executada: aplicar escopo de tenant em managers, querysets, permissions e services.
- tarefa a ser executada: implementar configuracoes de marca e slug publico.
- tarefa a ser executada: testar isolamento multi-tenant em leitura, escrita, cache e endpoints publicos.

### Sprint 4 - Entidades principais

- tarefa a ser executada: implementar Course, Module, Lesson, Offer, Student, Enrollment e MediaAsset.
- tarefa a ser executada: criar serializers, viewsets, filtros e rotas principais.
- tarefa a ser executada: implementar ordenacao de modulos e aulas.
- tarefa a ser executada: criar factories e testes de CRUD com escopo por tenant.

### Sprint 5 - Regras de negocio centrais

- tarefa a ser executada: implementar publicacao de curso.
- tarefa a ser executada: implementar ofertas de compra avulsa e assinatura.
- tarefa a ser executada: implementar liberacao manual e automatica de acesso.
- tarefa a ser executada: implementar area do aluno por API.
- tarefa a ser executada: testar acesso do aluno apenas ao que comprou, assinou ou recebeu manualmente.

### Sprint 6 - Landing pages por template

- tarefa a ser executada: implementar `LandingPageTemplateBinding` e `LandingPageSection`.
- tarefa a ser executada: expor endpoints de configuracao, preview-data, publicacao e pagina publica.
- tarefa a ser executada: vincular ofertas ativas a landing pages.
- tarefa a ser executada: testar que pagina publica so mostra ofertas ativas do tenant correto.

### Sprint 7 - Checkout e gateway proprio

- tarefa a ser executada: implementar PaymentAccount por tenant.
- tarefa a ser executada: implementar Order, Subscription e PaymentTransaction.
- tarefa a ser executada: criar checkout idempotente para compra avulsa.
- tarefa a ser executada: criar fluxo de assinatura, renovacao, falha e cancelamento.
- tarefa a ser executada: implementar webhooks validados e reprocessaveis.
- tarefa a ser executada: testar gateway proprio por tenant sem vazamento cruzado.

### Sprint 8 - Arquivos e anexos

- tarefa a ser executada: implementar upload e metadata de arquivos.
- tarefa a ser executada: proteger downloads por endpoint autorizado.
- tarefa a ser executada: associar arquivos a aulas, materiais, marca e relatorios.
- tarefa a ser executada: auditar upload, download e exclusao.
- tarefa a ser executada: testar arquivos privados e downloads protegidos.

### Sprint 9 - Progresso e gamificacao

- tarefa a ser executada: implementar ProgressEvent.
- tarefa a ser executada: implementar badges, desafios e regras basicas.
- tarefa a ser executada: disparar conquistas por eventos reais.
- tarefa a ser executada: expor endpoints da area do aluno para progresso, badges e desafios.
- tarefa a ser executada: testar progresso, conclusao, streaks e gamificacao por tenant.

### Sprint 10 - Dashboards e relatorios

- tarefa a ser executada: implementar endpoints de metricas de vendas, receita, conversao, alunos ativos, conclusao e gamificacao.
- tarefa a ser executada: implementar AnalyticsSnapshot para agregacoes pesadas.
- tarefa a ser executada: implementar relatorios PDF com ReportLab e PyPDF.
- tarefa a ser executada: implementar exportacoes CSV.
- tarefa a ser executada: testar filtros, permissoes e geracao de relatorios.

### Sprint 11 - Tarefas assincronas e automacoes

- tarefa a ser executada: configurar filas Celery por dominio.
- tarefa a ser executada: implementar liberacao de acesso apos pagamento.
- tarefa a ser executada: implementar avisos de renovacao, falha de pagamento e aluno inativo.
- tarefa a ser executada: implementar carrinho abandonado e resumo semanal do treinador.
- tarefa a ser executada: implementar retries, status de jobs e replay de webhooks.
- tarefa a ser executada: testar idempotencia e comportamento em falha.

### Sprint 12 - IA aplicada com criterio

- tarefa a ser executada: configurar LangChain, LangGraph e GPT-5.5-mini via OpenAI.
- tarefa a ser executada: implementar jobs assincronos para copy, descricao de curso, resumo de engajamento e risco de abandono.
- tarefa a ser executada: implementar endpoints de disparo, status e resultado.
- tarefa a ser executada: implementar sessoes de chat via API quando aplicavel.
- tarefa a ser executada: auditar uso de IA e restringir tools por tenant.
- tarefa a ser executada: testar bloqueio absoluto de acesso cruzado entre tenants.

### Sprint 13 - Seguranca avancada

- tarefa a ser executada: reforcar rate limiting em endpoints sensiveis.
- tarefa a ser executada: revisar CORS por ambiente.
- tarefa a ser executada: mascarar logs e proteger secrets.
- tarefa a ser executada: criar testes de abuso, enumeracao e acesso indevido.
- tarefa a ser executada: revisar auditoria de permissoes, gateway, arquivos, IA e webhooks.

### Sprint 14 - Documentacao

- tarefa a ser executada: completar OpenAPI/Swagger com drf-spectacular.
- tarefa a ser executada: criar MKDocs com Mermaid.
- tarefa a ser executada: documentar arquitetura, apps, multi-tenancy, pagamentos, landing pages, gamificacao, IA e deploy.
- tarefa a ser executada: documentar operacao, troubleshooting, backup e restore.

### Sprint 15 - Deploy local

- tarefa a ser executada: criar Docker Compose para app, banco, Redis, RabbitMQ, worker e beat.
- tarefa a ser executada: configurar volumes, healthchecks e `wait_for_db`.
- tarefa a ser executada: implementar migrations com advisory lock e `collectstatic --clear`.
- tarefa a ser executada: criar seed demo completo.
- tarefa a ser executada: validar localmente API, filas, uploads, webhooks, relatorios e IA.

### Sprint 16 - Deploy em producao

- tarefa a ser executada: preparar Docker Swarm em VPS Ubuntu.
- tarefa a ser executada: configurar Traefik, TLS wildcard, Cloudflare DNS-01 e Docker Secrets.
- tarefa a ser executada: publicar imagem no GHCR.
- tarefa a ser executada: criar stack file com redes publica, interna e egress.
- tarefa a ser executada: executar `docker stack deploy --with-registry-auth`.
- tarefa a ser executada: validar healthchecks, workers, beat, webhooks e rotinas de backup.

### Sprint 17 - Testes e validacao final

- tarefa a ser executada: executar suite completa de testes criticos.
- tarefa a ser executada: validar isolamento multi-tenant em todos os modulos.
- tarefa a ser executada: validar compra avulsa e assinatura ponta a ponta.
- tarefa a ser executada: validar landing pages por template e area do aluno via API.
- tarefa a ser executada: validar progresso, gamificacao, relatorios, automacoes e IA.
- tarefa a ser executada: revisar criterios de aceite e gerar checklist final de pronto para MVP.

## 35. Riscos tecnicos e mitigacao

### Multi-tenant com paginas publicas por criador

Risco: pagina publica resolver tenant errado ou expor oferta/conteudo de outro criador.

Mitigacao: resolucao de tenant centralizada, slugs unicos por escopo, testes de isolamento em endpoints publicos, cache com tenant na chave e auditoria de publicacao.

### Gateway proprio por tenant

Risco: credenciais, webhooks ou conciliacao de um tenant afetarem outro.

Mitigacao: PaymentAccount por tenant, validacao de assinatura, mapeamento seguro de conta externa, idempotencia, auditoria e testes de eventos cruzados.

### Compra avulsa e assinatura no mesmo dominio

Risco: regras de acesso ficarem ambiguas entre pedido, assinatura, matricula e revogacao.

Mitigacao: modelar Offer com tipo claro, Enrollment como fonte de acesso, services dedicados para concessao/revogacao e testes de coexistencia.

### Permissao fina para equipe interna

Risco: RBAC complexo demais ou permissivo demais.

Mitigacao: roles padrao, permissoes granulares por modulo, denies por padrao, auditoria de mudancas e testes por perfil.

### Custos e entrega de video

Risco: armazenamento e banda crescerem rapido.

Mitigacao: metadata de streaming, politicas por tenant, limites por plano futuros, arquivos protegidos e possibilidade de provider especializado.

### Relatorios de consumo pesados

Risco: consultas lentas afetarem API principal.

Mitigacao: AnalyticsSnapshot, jobs assincronos, filtros indexados, cache por tenant e exportacoes geradas em background.

### Webhooks e eventos assincronos

Risco: eventos duplicados, fora de ordem ou perdidos.

Mitigacao: idempotency keys, tabela de eventos, retries com backoff, replay manual, conciliacao periodica e logs correlacionados.

### Governanca de IA com dados de alunos

Risco: IA acessar dados de outro tenant ou expor informacoes sensiveis.

Mitigacao: tools com escopo obrigatorio de tenant, minimizacao de dados, logs de uso, permissoes proprias e testes de isolamento.

### Crescimento prematuro do escopo

Risco: tentar entregar LMS completo, marketing automation, CRM, comunidade, afiliados e mobile ao mesmo tempo.

Mitigacao: MVP focado em criacao, venda, pagamento, acesso, progresso, gamificacao basica, metricas e automacoes essenciais; itens complexos ficam fora do escopo inicial.

## 36. Itens fora do escopo

Ficam fora do escopo inicial:

- editor visual livre de landing pages;
- comunidade social complexa entre alunos;
- chat em tempo real;
- white label completo;
- marketplace aberto entre criadores;
- app mobile nativo;
- afiliados complexos;
- IA conversacional ampla para alunos sem validacao previa;
- recursos de CRM externos profundos;
- frontend dentro do backend Django;
- Django Templates para interfaces publicas;
- React, Vue, Next.js, Angular ou SPA no escopo do backend;
- dashboard visual;
- landing page visual;
- kanban visual;
- chat visual.

## Regras finais de qualidade

- A stack fixa nao pode ser alterada.
- O projeto nao deve virar full stack visual.
- Nenhum frontend deve entrar no escopo.
- Arquivos nao podem ser expostos publicamente sem autorizacao.
- Isolamento por tenant deve ser explicito em models, services, permissions, queries, cache, jobs e testes.
- O PRD nao deve resultar em CRUD generico.
- A implementacao deve priorizar profundidade tecnica, clareza e orientacao pratica.
- O MVP deve provar valor comercial para o treinador independente de futebol sem comprometer seguranca, pagamentos e isolamento.
