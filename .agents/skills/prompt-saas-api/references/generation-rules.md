# Regras de Geracao

## Objetivo

Transformar uma ideia de sistema SaaS em um prompt tecnico completo para outro modelo gerar um PRD em markdown, mantendo a stack Django API-only fixa e variando apenas o dominio e a identidade de deploy.

## Regra Principal

- Gere sempre um `prompt final completo`.
- Nao gere o PRD diretamente.
- Se o usuario trouxer poucos detalhes, infera uma primeira estrutura profissional.
- Leia `django-swarm-traefik-deploy.md` e incorpore suas regras no prompt final.
- O resultado final deve ser autocontido; nao deixe o proximo modelo depender de arquivos desta skill.

## Como Inferir o Dominio

Derive ou proponha, quando faltarem detalhes:

- nome sugerido do sistema;
- slug tecnico do projeto;
- dominio de producao;
- imagem de registry;
- nome do stack;
- nomes das redes Swarm;
- politica de testes;
- objetivo do produto;
- publico-alvo;
- dores resolvidas;
- modulos funcionais;
- entidades principais;
- relacionamentos;
- regras de negocio;
- perfis de usuarios;
- permissoes;
- fluxos principais;
- endpoints relevantes;
- relatorios;
- dashboards via API;
- tarefas assincronas;
- recursos de IA aplicaveis;
- integracoes uteis;
- riscos tecnicos;
- criterios de aceite;
- roadmap em sprints.

## Como Parametrizar Deploy

Quando faltarem valores de deploy:

- derive `project_slug` a partir de `project_title`, em lowercase snake_case;
- use `stack_name = project_slug`;
- use `public_network = traefik_public`;
- use `internal_network = ${project_slug}_internal`;
- use `egress_network = ${project_slug}_egress`;
- use `cloudflare_secret_name = CLOUDFLARE_DNS_API_TOKEN`;
- se `production_domain` ou `registry_image` nao vierem, faca uma unica pergunta curta; se o usuario mandar continuar, use placeholders claros.

Nunca preserve `scsi`, `scsi.digital`, `scsi_v1`, `ghcr.io/pycodebr/scsi_v1` ou nomes equivalentes de outro projeto, a menos que o usuario tenha fornecido esses valores explicitamente.

## Politica de Testes

Use `test_policy = required` por padrao. Isso exige testes minimos para autenticacao, autorizacao, isolamento por tenant, regras centrais, anexos, exportacoes, filas, IA e casos de erro.

Use `test_policy = disabled_by_user_request` somente se o usuario pedir explicitamente para nao implementar testes. Nesse caso:

- nao inclua implementacao obrigatoria de testes;
- inclua criterios de aceite verificaveis;
- inclua checklist de validacao manual;
- mantenha a recomendacao de testes como risco tecnico, nao como tarefa obrigatoria.

## Adaptacao de Funcionalidades Visuais

Quando o dominio naturalmente sugerir telas ou interfaces visuais, converta isso para contrato de API:

- dashboard visual -> endpoints de metricas, agregacoes, comparativos e indicadores;
- kanban visual -> endpoints de pipeline, etapas, cards, movimentacoes e auditoria;
- chat visual -> endpoints de sessoes, mensagens, historico, streaming e status;
- relatorios visuais -> endpoints de dados tabulares, PDF e CSV;
- notificacoes visuais -> endpoints de notificacoes, leitura, filtros e status;
- uploads visuais -> endpoints protegidos de upload, listagem, download e exclusao.

## Regras de IA

Inclua recursos de IA somente quando fizer sentido real para o dominio. Quando incluir:

- proponha casos de uso concretos;
- mantenha processamento assincrono com Celery;
- inclua endpoints para disparo, status e resultado;
- preserve isolamento absoluto por tenant;
- salve sessoes, uso e auditoria quando houver conversa ou analise persistida.

## Proibicoes

Nunca:

- mude a stack fixa;
- transforme o escopo em frontend;
- sugira React, Vue, Next.js, Angular ou Templates Django;
- remova multi-tenant, JWT, Docker Swarm, Traefik, Celery, RabbitMQ, Redis ou OpenAPI;
- use nomes de deploy de outro projeto sem autorizacao explicita;
- coloque Celery, PostgreSQL, Redis ou RabbitMQ na rede publica;
- use HTTP-01 ou TLS-01 para wildcard TLS;
- exponha media/arquivos privados por URL publica direta;
- exponha arquivos sem autenticacao e autorizacao;
- deixe endpoints sem isolamento por tenant;
- entregue apenas CRUD generico;
- ignore `test_policy`;
- remova o formato de sprints em checklist.

## Checklist Anti-Erro

Antes de responder, confirme mentalmente:

- nenhum placeholder interno ficou sem substituicao, exceto placeholders de deploy assumidos por falta de entrada;
- o prompt final continua Django API-only;
- nao ha React, Vue, Next.js, Angular, Django Templates ou SPA no escopo;
- multi-tenancy, JWT, OpenAPI, Celery, RabbitMQ, Redis, Docker Swarm e Traefik continuam presentes;
- redes publica, interna e egress estao definidas e isoladas corretamente;
- Celery worker e beat nao estao na rede publica;
- TLS wildcard usa Cloudflare DNS-01;
- Cloudflare token usa Docker Secret;
- `/health/`, healthchecks, `wait_for_db`, advisory lock e `collectstatic --clear` aparecem;
- scripts de deploy e backup aparecem;
- arquivos privados sao servidos apenas por endpoints protegidos;
- sprints usam checklist `- [ ]`;
- o texto final e apenas o prompt mestre, sem comentario antes ou depois.

## Qualidade da Saida

A resposta final deve ser:

- profunda;
- coerente com o dominio;
- tecnicamente detalhada;
- pronta para copiar e colar;
- organizada para orientar outro modelo sem ambiguidades;
- suficientemente especifica para preservar arquitetura, seguranca, deploy e padrao profissional.
