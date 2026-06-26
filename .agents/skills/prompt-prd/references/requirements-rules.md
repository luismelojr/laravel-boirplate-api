# Requirements Rules — Laravel Stack

## Propósito

Use estas regras para gerar as três seções variáveis do prompt:

- `# REQUISITOS FUNCIONAIS DO SISTEMA`
- `# REQUISITOS NÃO FUNCIONAIS DO SISTEMA`
- `# TAREFA`

## Regras de Substituição de Identidade

Antes de escrever o prompt final, substituir todos os identificadores:

- nome do produto antigo
- domínio de produção antigo
- qualquer entidade de negócio de outro projeto

Nunca deixar referências de projeto anterior como:
- `scsi.digital`, `boirplate.test` ou qualquer domínio de exemplo
- nomes de entidades de outro domínio de negócio

## Cobertura de Requisitos Funcionais

Requisitos funcionais descrevem o que o sistema deve permitir o negócio fazer.

Seguir sempre esta ordem:

1. Usuários, autenticação, permissões e bootstrap do tenant/empresa
2. Dados mestre e entidades base
3. Fluxo transacional ou operacional principal
4. Documentos, anexos e artefatos gerados
5. Dashboards, relatórios, exportações e métricas
6. Notificações, lembretes e mudanças de status
7. Admin e backoffice operacional
8. Features de IA que se encaixam no workflow (apenas se houver valor claro)

## Regras de Escrita Funcional

- Preferir módulos de negócio concretos em vez de wording genérico de CRUD.
- Se o sistema tem etapas, pipelines, aprovações ou conversões, declará-los explicitamente.
- Se a ideia implica hierarquia, papéis, comissões, agendamento, estoque ou cobrança, incluir explicitamente.
- Se é SaaS self-service, incluir landing page, cadastro, seleção de plano e onboarding.
- Se tem portal público + backoffice privado, mencionar ambos.
- Se arquivos são relevantes no domínio, mencionar anexos onde pertencem.
- Se exportações são importantes, especificar formatos: PDF e CSV/XLSX.
- Usar bullets orientados à implementação mas legíveis para o negócio.
- Usar wording direto: `Cadastro`, `Gestão`, `Painel`, `Relatórios`, `Dashboard`,
  `Anexo`, `Chat`, `Automação`, `Aprovação`, `Emissão`.

## Regras de IA (adicionar apenas quando há valor claro)

Bons padrões:
- Resumir um registro com contexto cross-entity
- Redigir mensagem, proposta, nota ou resposta
- Classificar ou extrair dados de documentos
- Gerar insights ou anomalias sobre dashboards ou workflows
- Chat assistente quando genuinamente útil para consultar toda a base do tenant

Evitar:
- Chat genérico sem valor operacional
- Repetir a mesma feature de IA com nomes diferentes
- Features de IA que ignoram fronteiras de tenant
- IA como feature de marketing em vez de funcionalidade real

Se features de IA forem incluídas:
- Disparar via job Horizon (assíncrono, nunca bloqueante)
- Notificar o usuário quando a task finalizar via Reverb ou polling
- Salvar resultado em campo dedicado no model (`ai_summary`, `ai_draft`, etc.)

## Cobertura de Requisitos Não-Funcionais

Sempre incluir:

- Responsividade em todos os tamanhos de tela
- Isolamento de tenant e segurança de permissões (se multi-tenant)
- Proteção de arquivos de mídia/anexos — servir via view autenticada, nunca URL direta
- UX excelente com jornadas fluidas baseadas no design system do projeto
- Execução assíncrona para tasks pesadas com feedback ao usuário (loading no botão +
  notificação quando concluir)
- Performance: filtros, telas e processos não bloqueantes
- Deploy zero-downtime via Forge com `horizon:terminate`
- Startup ordenado: migrations apenas no deploy (Forge), nunca em runtime de worker
- Sentry para rastreamento de erros em produção
- Pulse para monitoramento de performance em `/pulse`
- Larastan nível 6 e Pint no pipeline de qualidade
- Secrets e credenciais nunca versionados — via `.env` gerenciado pelo Forge

Adicionar quando a ideia sugere:

- Trilha de auditoria (`owen-it/laravel-auditing` já no boilerplate)
- LGPD: consentimento, anonimização, política de privacidade
- Rastreabilidade de documentos
- Integridade de documentos fiscais
- Observabilidade avançada
- Accountability em aprovações

## Regras da Seção de Tarefa

A seção de tarefa deve pedir a outro agente que crie o PRD, não que construa o software imediatamente.

Sempre solicitar:

- PRD em markdown com todos os detalhes técnicos e de planejamento
- Descrição das entidades de banco de dados principais com campos-chave
- Guia de deploy no Laravel Forge, passo a passo com comandos:
  - Criar conta no Forge e conectar servidor (Ubuntu 22.04, PHP 8.5, MySQL 8.4, Redis)
  - Criar site e conectar repositório GitHub
  - Configurar `.env` de produção (variáveis obrigatórias listadas)
  - Configurar deploy script
  - Configurar Horizon como daemon worker
  - Configurar Scheduler via cron
  - Configurar SSL via Forge (Let's Encrypt)
  - Configurar backup S3/R2
  - Configurar Sentry DSN
  - Primeira execução: migrate, seed, cache
- Plano de sprints com tarefas pequenas e bem detalhadas, em ordem lógica de
  desenvolvimento, em formato de checklist com espaço `[ ]` para marcação quando concluída.
  Cada tarefa deve ser pequena o suficiente para ser executada em horas, não dias.

## Checklist de Qualidade Final

O prompt final está pronto apenas se:

- Lê como um projeto coerente, não um fragmento reutilizado
- O stack Laravel fixo está intacto e detalhado
- Os requisitos funcionais refletem a nova ideia de negócio
- Não há placeholders sobrando (exceto se o usuário aceitou explicitamente)
- A seção de tarefa ainda produz PRD + guia Forge + checklist de sprints
- Nenhum identificador de projeto anterior vazou
- O nível de detalhe é equivalente ao do `prompt-bruto.md` original do projeto
