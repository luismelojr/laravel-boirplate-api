---
name: prompt-prd
description: Use when the user wants to turn a new software, SaaS, ERP, CRM, portal, or management-system idea into a complete reusable master prompt for PRD generation, always using the fixed Laravel 13 / PHP 8.5 / MySQL / Redis / Horizon / Forge stack from the boilerplate.
---

# Prompt PRD — Laravel Stack

## Overview

Produce o `prompt mestre`, não o PRD em si. Mantém a base técnica Laravel fixa em todos os projetos. Regenera a identidade do projeto e escreve as seções variáveis para que o prompt final esteja pronto para alimentar outro agente que vai construir o PRD.

Leia `references/prompt-template.md` primeiro. Depois leia `references/requirements-rules.md`.

## Protocolo de Interação

Faça perguntas **uma por vez**, em ordem. Aguarde a resposta antes de fazer a próxima. Nunca faça mais de uma pergunta por mensagem.

### Sequência de Perguntas

1. **Nome do sistema** — Como se chama o sistema? (ex: ERP Vestuário, CRM Imóveis, Portal RH)
2. **Domínio de produção** — Qual o domínio onde o sistema vai rodar? (ex: meuapp.com.br)
3. **Tenancy** — O sistema é **multi-tenant** (várias empresas isoladas no mesmo banco) ou **single-tenant** (uma empresa só)?
4. **Frontend** — Tem frontend? Se sim, qual: **Next.js**, **React Native**, **Inertia React**, ou **nenhum** (só API)?
5. **Módulos principais** — Descreva livremente os principais módulos e funcionalidades do sistema.
6. **Recursos especiais** — Tem algum recurso especial? (relatórios PDF, uploads de arquivos, exportações CSV/XLSX, notificações em tempo real via WebSocket, feature flags por plano, integrações de IA, validações de CPF/CNPJ/CEP)
7. **Integrações externas** — Tem integrações com serviços externos? (gateways de pagamento, WhatsApp, SMS, APIs de terceiros, webhooks)
8. **Perfis e permissões** — Como são os perfis de usuário? (ex: admin/usuário, hierarquia de papéis, permissões granulares por módulo)

Após coletar todas as respostas, gere o documento completo e salve em `docs/spec.md`.

## Input Contract

Capture ou derive antes de gerar:

- `system_context`: frase de abertura em português (ex: "Vou desenvolver um Sistema de Gestão de...")
- `system_name`: nome do produto/sistema
- `domain`: domínio de produção
- `is_multitenant`: booleano derivado da pergunta 3
- `frontend_type`: `nextjs` | `react-native` | `inertia` | `none`
- `business_summary`: o que o sistema faz
- `modules`: lista dos módulos funcionais principais
- `special_resources`: PDF, uploads, realtime, IA, validações BR, etc.
- `integrations`: APIs e serviços externos
- `user_roles`: modelo de permissões

Se o domínio de produção estiver ausente e o usuário quiser o prompt completo, peça em uma mensagem concisa.

Se o usuário aceitar placeholders, use `[DOMAIN]`, `[SYSTEM_NAME]`.

## Non-Negotiables

- Não alterar o stack Laravel fixo nem as cláusulas de deploy via Forge.
- Não resumir os tech specs em uma frase vaga — manter o nível de detalhe completo.
- Não deixar identificadores de outro projeto no prompt final.
- Manter o prompt final em português.
- Manter a ordem das seções exatamente como o template define.
- Manter os títulos das seções exatamente como:
  - `# TECH SPECS DO SISTEMA`
  - `# REQUISITOS FUNCIONAIS DO SISTEMA`
  - `# REQUISITOS NÃO FUNCIONAIS DO SISTEMA`
  - `# TAREFA`
- Sempre salvar o resultado final em `docs/spec.md`.
- Criar o diretório `docs/` se não existir.

## Workflow de Geração

1. Ler `references/prompt-template.md`.
2. Ler `references/requirements-rules.md`.
3. Aplicar substituições de identidade:
   - `[SYSTEM_CONTEXT]` → frase de abertura
   - `[SYSTEM_NAME]` → nome do sistema
   - `[DOMAIN]` → domínio de produção
   - `[TENANCY_CLAUSE]` → bloco multi ou single tenant do template
   - `[FRONTEND_CLAUSE]` → bloco de integração de frontend (ou omitir se `none`)
4. Gerar requisitos funcionais a partir das respostas coletadas.
5. Gerar requisitos não funcionais combinando as garantias da plataforma Laravel com necessidades específicas do domínio.
6. Gerar a seção de tarefa solicitando PRD + guia de deploy no Forge + checklist de sprints.
7. Substituir todos os placeholders em `references/prompt-template.md`.
8. Salvar o resultado completo em `docs/spec.md`.
9. Confirmar para o usuário com o caminho do arquivo.

## Referências

- `references/prompt-template.md` — skeleton fixo do stack Laravel
- `references/requirements-rules.md` — regras de geração e checklist de cobertura
