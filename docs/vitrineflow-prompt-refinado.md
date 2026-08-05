# Prompt Refinado — VitrineFlow

Cole este prompt no CLI/modelo da sua preferência e substitua o bloco `<source_prd>` pelo PRD bruto completo do VitrineFlow.

```xml
<context>
Você receberá um PRD bruto e deverá transformá-lo em um PRD técnico, funcional e operacional completo para o produto VitrineFlow.

O VitrineFlow é um SaaS API-only, multi-tenant, voltado para pequenas lojas de moda que vendem principalmente por Instagram e WhatsApp. O sistema deve entregar catálogo público via API, gestão operacional da loja, controle de estoque, pedidos, clientes, fornecedores, compras, vendedoras, comissões, stories, relatórios, IA, planos, assinaturas e cobrança recorrente via Asaas.

O backoffice interno do dono do SaaS deverá usar obrigatoriamente Django Admin. Tenants não poderão acessar o Django Admin.
</context>

<role>
Atue como arquiteto sênior de software, product manager técnico, especialista em SaaS multi-tenant, Django/API-only, segurança, billing recorrente, integração Asaas, documentação técnica e planejamento de MVP.
</role>

<objective>
Gerar um PRD final em Markdown, robusto e pronto para orientar implementação futura do VitrineFlow.

NÃO implemente código.
NÃO gere migrations.
NÃO crie frontend.
NÃO altere a stack definida.
NÃO transforme o produto em CRUD genérico.
Apenas estruture, refine, complete e organize o PRD com linguagem técnica, mandatória e verificável.
</objective>

<source_prd>
[COLE AQUI INTEGRALMENTE O PRD BRUTO DO VITRINEFLOW]
</source_prd>

<non_negotiable_rules>
1. Preserve obrigatoriamente o nome VitrineFlow e o slug técnico vitrine_flow.
2. Preserve obrigatoriamente a abordagem SaaS API-only multi-tenant.
3. Preserve obrigatoriamente a stack: Python > 3.13, Django >= 6.0, Django REST Framework, drf-spectacular, Swagger, PostgreSQL, Redis, Celery, RabbitMQ, Docker, Docker Swarm, Traefik, Cloudflare DNS, wildcard TLS via DNS-01, Asaas e MKDocs.
4. Preserve obrigatoriamente Django Admin como backoffice interno do dono do SaaS.
5. Proíba explicitamente acesso de tenants ao Django Admin.
6. Proíba frontend visual no MVP, incluindo React, Vue, Next.js, Angular e Django Templates.
7. Não remova Asaas, cobrança recorrente, webhooks, idempotência, bloqueio/liberação por pagamento, auditoria, backups, restore validado e testes obrigatórios.
8. Não exponha PostgreSQL, Redis, RabbitMQ ou Celery em rede pública.
9. Não versionar secrets. Docker Secrets devem ser usados para segredos críticos, incluindo CLOUDFLARE_DNS_API_TOKEN.
10. Preserve os placeholders explícitos do material-fonte apenas quando forem valores ainda não definidos, como domínio de produção e imagem de registry. Não crie placeholders novos sem necessidade.
11. Quando houver ambiguidade, tome uma decisão conservadora, alinhada ao PRD fonte, e registre em uma seção de premissas técnicas.
12. Não invente integrações fora do escopo. Instagram automático, app mobile, checkout completo do cliente final e emissão fiscal ficam fora do MVP.
13. Use raciocínio interno para revisar consistência, mas não exponha cadeia de pensamento. Mostre apenas decisões finais, justificativas objetivas e critérios verificáveis.
</non_negotiable_rules>

<critical_requirements>
Inclua e detalhe obrigatoriamente:
- visão geral do produto, público-alvo, dores, oportunidade e escopo;
- escopo dentro e fora do MVP;
- arquitetura API-only e separação entre API pública, API autenticada, API interna e Django Admin;
- modelo multi-tenant explícito, com tenant em todos os modelos operacionais;
- regras de isolamento, permissões, papéis e testes cross-tenant;
- módulos: tenancy, billing, accounts, stores, catalog, inventory, kits, customers, orders, suppliers, purchases, commissions, content/stories, Instagram manual, reports, notifications, AI e audit;
- integração Asaas com webhooks persistidos, idempotentes, assíncronos e auditáveis;
- catálogo público com carrinho e finalização via WhatsApp;
- estoque por variação, movimentações imutáveis, kits e bloqueio de estoque negativo por padrão;
- arquivos privados, mídia, LGPD, logs estruturados, observabilidade, jobs Celery e rate limiting;
- OpenAPI/Swagger com drf-spectacular;
- MKDocs com diagramas Mermaid;
- deploy Docker Swarm com Traefik, redes isoladas, Cloudflare DNS-01 e wildcard TLS;
- backups recorrentes e restore validado;
- roadmap em sprints com checklists e critérios de aceite.
</critical_requirements>

<output_contract>
Retorne somente o PRD final em Markdown.
Idioma: português brasileiro.
Tom: técnico, direto, mandatário e orientado à implementação.
Formato obrigatório:
1. Título principal com o nome do produto.
2. Seções numeradas, preservando a lógica do PRD fonte.
3. Tabelas quando houver stack, apps, responsabilidades, status, permissões ou limites.
4. Checklists em roadmap e critérios de aceite.
5. Diagramas Mermaid apenas quando agregarem clareza.
6. Critérios de aceite objetivos e testáveis.
7. Nenhum comentário antes ou depois do Markdown final.
</output_contract>

<quality_bar>
O PRD final será considerado bom somente se:
- um time técnico conseguir iniciar a implementação sem perguntas básicas;
- todas as restrições críticas do VitrineFlow estiverem explícitas;
- nenhuma decisão contrariar o PRD fonte;
- multi-tenancy, billing Asaas, segurança, deploy, arquivos privados, jobs e testes estiverem tratados como pontos centrais;
- o MVP continuar focado em lojas de moda que vendem por Instagram e WhatsApp;
- o documento evitar generalidades e transformar cada requisito em orientação verificável.
</quality_bar>

<forbidden_output_patterns>
- Responder com resumo curto.
- Gerar apenas ideias soltas.
- Criar frontend no MVP.
- Trocar Django por outra stack.
- Remover API-only.
- Remover multi-tenancy.
- Permitir tenant no Django Admin.
- Tratar Asaas como checkout do cliente final no MVP.
- Ignorar idempotência de webhooks.
- Ignorar testes de isolamento por tenant.
- Expor serviços internos na rede pública.
- Versionar secrets.
- Criar roadmap superficial.
- Usar frases vagas como "implementar boas práticas" sem especificar o que deve ser feito.
</forbidden_output_patterns>
```
