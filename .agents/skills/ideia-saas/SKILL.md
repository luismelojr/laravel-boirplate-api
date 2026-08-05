---
name: ideia-saas
description: Use when the user brings a raw or partially defined idea for a SaaS, ERP, CRM, marketplace, portal, platform, or management system and needs the product vision structured before technical architecture, technical prompt generation, or PRD work.
---

# Ideia SaaS

## Overview

Structure the product idea before any technical architecture step. This skill turns a rough SaaS idea into a strategic, organized product vision that can feed a later technical skill such as `$prompt-saas-api`.

Read `references/output-template.md` first. Then read `references/analysis-rules.md`.

## Trigger Contract

Use this skill when the user wants to:

- estruturar uma ideia de SaaS ainda bruta;
- amadurecer um produto antes de arquitetura tecnica;
- organizar MVP, V1 e V2;
- mapear modulos, entidades, fluxos, permissoes e riscos;
- validar se a ideia esta ampla demais, generica demais ou mal recortada.

Do not use this skill to generate code, PRD final, or fixed technical stack details.

## Input Contract

Capture or derive before generating:

- `system_idea`: que tipo de sistema o usuario quer criar;
- `suggested_name`: nome sugerido do sistema;
- `strategic_summary`: resumo objetivo da ideia;
- `target_audience`: publico-alvo;
- `pain_points`: dores que o sistema resolve;
- `value_proposition`: proposta de valor;
- `possible_differentiators`: diferenciais provaveis;
- `mvp_scope`: recorte do MVP;
- `v1_scope`: escopo de V1;
- `v2_scope`: escopo de V2;
- `functional_modules`: modulos funcionais;
- `user_profiles`: perfis de usuarios;
- `initial_permissions`: permissoes iniciais;
- `main_entities`: entidades principais;
- `entity_relationships`: relacionamentos entre entidades;
- `core_flows`: fluxos principais;
- `business_rules`: regras de negocio;
- `dashboard_metrics`: dashboards e indicadores;
- `reports`: relatorios necessarios;
- `automations`: automacoes e tarefas recorrentes;
- `ai_features`: recursos de IA aplicaveis;
- `integrations`: integracoes possiveis;
- `notifications`: notificacoes necessarias;
- `attachments`: arquivos e anexos necessarios;
- `product_risks`: riscos de produto;
- `likely_technical_risks`: riscos tecnicos provaveis;
- `business_model_attention_points`: pontos de atencao do modelo de negocio;
- `success_criteria`: criterios de sucesso;
- `strategic_questions`: perguntas estrategicas recomendadas.

If the user gives only the domain, infer a strong first version and clearly mark what was inferred.

If important information is missing, do not block waiting for answers. Deliver the first structured version and finish with recommended strategic questions.

## Non-Negotiables

- Never generate code.
- Never generate the final PRD.
- Never lock the answer into a detailed technical stack.
- Never ask for confirmation before delivering the first structured version.
- Never respond with generic praise or shallow agreement.
- Never leave scope risk unmentioned when the idea is too broad.

## Workflow

1. Read `references/output-template.md`.
2. Read `references/analysis-rules.md`.
3. Infer or normalize the product idea from the user request.
4. Diagnose whether the idea is clear, generic, too broad, or strategically promising.
5. Separate the solution into `MVP`, `V1`, and `V2`.
6. Map modules, entities, roles, permissions, flows, rules, dashboards, reports, automations, AI, integrations, notifications, files, and risks.
7. Make inferred assumptions explicit.
8. End with `## Entrada pronta para a Skill Tecnica`.

## Output Contract

The response must be:

- em portugues brasileiro;
- estrategica e pratica;
- completa sem enrolacao;
- critica quando necessario;
- organizada com titulos claros;
- util para alimentar uma skill tecnica posterior.

## References

- `references/output-template.md` - estrutura obrigatoria da resposta
- `references/analysis-rules.md` - regras de analise, inferencia e recorte
