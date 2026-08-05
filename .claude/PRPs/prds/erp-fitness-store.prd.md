# ERP Não Fiscal — Loja de Roupas e Acessórios Fitness

## Problem Statement

Donos de lojas fitness que operam do zero não têm visibilidade real sobre estoque por variação (tamanho/cor), margem por produto e fluxo de caixa. Sem controle centralizado, vendem produtos que não têm, não sabem quais tamanhos encalham e não conseguem calcular se estão lucrando. O custo de não resolver é operar no escuro desde o primeiro dia.

## Evidence

- Assumption: loja abre do zero, sem nenhum sistema legado — validado pelo proprietário
- Assumption: o maior risco operacional é vender produto sem estoque ou sem conhecer a margem real
- Assumption: controle de variações (tamanho/cor) é o problema mais crítico de ERPs genéricos para roupas — baseado em análise de mercado (Bling, Tiny, ERPs genéricos falham aqui)

## Proposed Solution

ERP web construído em Laravel 13 (API) + Nuxt.js + PrimeVue (frontend), focado em controle total de produtos com variações, estoque confiável, PDV rápido, financeiro gerencial claro e API para catálogo externo. A escolha de construção própria é justificada pelo controle total sobre variações e integração com catálogo personalizado — nenhuma solução off-the-shelf atende bem a combinação de grade de roupas + catálogo externo + financeiro gerencial.

## Key Hypothesis

Acreditamos que um ERP com controle de grade (tamanho × cor), PDV rápido e margem real visível resolverá o problema de gestão operacional para Luis e sua esposa. Saberemos que funcionou quando a loja abrir com estoque 100% rastreável, margem real calculada em cada venda e zero venda de produto indisponível.

## What We're NOT Building

- Emissão de NF-e, NFC-e, SAT, cupom fiscal — fora do escopo por design (ERP não fiscal)
- Integração com SEFAZ, SPED, escrituração fiscal — fora do escopo
- App mobile nativo — acesso via browser no tablet/computador é suficiente
- Multi-tenant (múltiplas lojas) — sistema para uso próprio, expansão pode vir depois
- Integração com marketplace (Mercado Livre, Shopee) — fase futura se houver demanda

## Success Metrics

| Métrica | Meta | Como medir |
|--------|--------|--------------|
| Estoque zerado por variação no dia da abertura | 100% dos produtos cadastrados com grade completa | Relatório de estoque ao vivo |
| Margem real calculada em toda venda | 100% das vendas com custo, taxa e margem registrados | Relatório de lucratividade |
| Zero venda de produto sem estoque | 0 ocorrências | Log de vendas bloqueadas |
| PDV finaliza venda em menos de 60 segundos | ≥ 95% das vendas | Timestamp de abertura vs fechamento |
| API do catálogo respondendo em < 500ms | p95 | Monitoramento de resposta |

## Open Questions

- [ ] A loja vai ter leitor de código de barras físico? (impacta UX do PDV)
- [ ] Quais bandeiras de cartão serão aceitas? (impacta cadastro de taxas)
- [ ] O catálogo externo já tem uma plataforma definida? (impacta contrato da API)
- [ ] Vai ter entrega por transportadora ou só entrega local/retirada?
- [ ] Quantos produtos estimados no lançamento? (impacta testes de performance)

---

## Users & Context

**Usuário Primário**
- **Quem**: Luis — dono da loja, responsável por compras, estoque, financeiro e configurações
- **Comportamento atual**: Iniciando do zero, sem sistema anterior
- **Gatilho**: Precisa ter controle total antes de abrir em 6 meses
- **Estado de sucesso**: Abre a loja sabendo exatamente o que tem em estoque, quanto custou cada peça, qual é a margem real e quanto entrou de caixa no dia

**Usuário Secundário**
- **Quem**: Esposa — operação do dia a dia (vendas, atendimento, separação)
- **Comportamento atual**: Iniciando junto
- **Gatilho**: Precisa fazer vendas rápidas no PDV e consultar estoque sem depender do Luis
- **Estado de sucesso**: Consegue registrar venda e consultar disponibilidade por tamanho/cor sem treinamento extenso

**Job to Be Done**
Quando estou gerenciando minha loja fitness, quero ter controle total de estoque por variação, margem real por produto e fluxo de caixa centralizado, para poder tomar decisões rápidas de compra, precificação e promoção sem depender de planilha ou memória.

**Non-Users**
- Contador/fiscal — sistema não fiscal, sem módulos tributários
- Clientes finais — não têm acesso ao ERP (catálogo é sistema separado)
- Terceiros/fornecedores — sem acesso ao sistema

---

## Solution Detail

### Core Capabilities (MoSCoW)

| Prioridade | Módulo | Justificativa |
|------------|--------|---------------|
| Must | Produtos + Variações (grade tamanho×cor) | Sem isso, nada funciona |
| Must | Controle de Estoque | Core do negócio |
| Must | PDV com formas de pagamento e taxa de cartão | Operação diária |
| Must | Clientes | Histórico de compras e relacionamento |
| Must | Fornecedores | Controle de compras |
| Must | Entrada de Produtos / Compras | Alimentação do estoque |
| Must | Financeiro (contas a pagar/receber, caixa) | Saúde do negócio |
| Must | Usuários e Permissões | Dois usuários com papéis diferentes |
| Should | Kits e Combos | Estratégia comercial importante para fitness |
| Should | Promoções e Cupons | Necessário para abertura com campanha |
| Should | Trocas e Devoluções | Muito comum em roupas |
| Should | Relatórios gerenciais | Tomada de decisão |
| Should | Dashboard | Visão rápida diária |
| Should | API para Catálogo | Canal de vendas externo |
| Should | Orçamentos / Pré-vendas | Vendas pelo WhatsApp/Instagram |
| Should | Pedidos do Catálogo | Receber pedidos externos |
| Could | CRM e Relacionamento | Retenção de clientes |
| Could | Precificador | Simulação antes de precificar |
| Could | Análise de Grade / Curva ABC | Inteligência de compras |
| Could | Entregas e Logística | Controle de envios |
| Could | Comissão de Vendedores | Expansão futura de equipe |
| Could | Metas de Venda | Gestão de performance |
| Could | Sugestão de Reposição | Automação de compras |
| Won't (v1) | NF-e / NFC-e / SAT | Fora do escopo por definição |
| Won't (v1) | Integração com marketplace | Fase futura |
| Won't (v1) | App mobile nativo | Browser em tablet é suficiente |

### MVP Scope

Sistema com cadastro completo de produtos+variações, estoque confiável, PDV funcional com controle de margem, financeiro básico (contas a pagar/receber + caixa) e permissões para dois usuários. É o mínimo para a loja operar com controle real desde o primeiro dia.

### User Flow — Caminho Crítico do PDV

```
Abrir venda → Buscar produto (nome/SKU/código de barras)
→ Selecionar variação (cor + tamanho) → Ver disponibilidade em tempo real
→ Adicionar ao carrinho → Aplicar desconto/promoção se houver
→ Selecionar forma de pagamento → Ver margem calculada automaticamente
→ Finalizar → Estoque baixado → Financeiro atualizado → Comprovante gerado
```

### User Flow — Entrada de Produto

```
Novo pedido de compra → Selecionar fornecedor → Adicionar produtos + variações + quantidades
→ Informar custo + frete + despesas → Ratear custo automaticamente
→ Registrar parcelas a pagar → Confirmar recebimento → Estoque atualizado
→ Custo médio recalculado automaticamente
```

---

## Technical Approach

**Viabilidade**: ALTA

**Decisões de Arquitetura**

| Decisão | Escolha | Alternativas consideradas | Justificativa |
|---------|---------|--------------------------|---------------|
| Backend | Laravel 13 (este boilerplate) | NestJS, Django | Já tem auth, permissões, media, audit, Domain-Driven pronto |
| Frontend | Nuxt.js + PrimeVue | Next.js + ShadCN, Vue puro | Escolha do proprietário — PrimeVue tem componentes de grid/tabela ricos |
| Banco de dados | MySQL 8.4 | PostgreSQL | Já configurado no boilerplate |
| Autenticação | Sanctum (já implementado) | — | Pronto no boilerplate |
| Permissões | Spatie Permission (já implementado) | — | Pronto; roles admin/user adaptáveis |
| Media (fotos) | Spatie Media Library (já implementado) | — | Pronto para fotos de produtos |
| Auditoria | Laravel Auditing (já implementado) | — | Pronto para histórico de alterações |
| Multi-tenancy | Desligado para v1 (single-store) | Ativado | Complexidade desnecessária para uso próprio |
| API do Catálogo | REST API pública com cache Redis | GraphQL | Simples, rápida, compatível com Nuxt |
| Custo de produto | Custo médio ponderado | PEPS/UEPS | Mais simples e adequado para pequena operação |
| Concorrência no PDV | SELECT FOR UPDATE (pessimistic lock) | Optimistic locking | Evita race condition em vendas simultâneas |

**O que o boilerplate já entrega (não construir):**
- Autenticação completa (login, logout, reset de senha, verificação de email)
- Controle de usuários e convite de membros
- Roles e permissões (spatie/laravel-permission)
- Upload e conversão de fotos (spatie/laravel-medialibrary)
- Auditoria de mudanças em modelos (owen-it/laravel-auditing)
- Rate limiting nas APIs
- Estrutura Domain-Driven (Services + DTOs)
- Padrão `ApiResponse` consistente
- Health checks e monitoramento

**Riscos Técnicos**

| Risco | Probabilidade | Mitigação |
|-------|--------------|-----------|
| Complexidade da grade de variações (N×M combinações) | Alta | Modelar variações como entidade separada com estoque próprio desde o início |
| Performance de relatórios com grande volume | Média | Índices no banco + paginação + jobs para relatórios pesados |
| Race condition no PDV (venda simultânea) | Média | Pessimistic locking nas transações de baixa de estoque |
| Custo médio recalculado incorretamente | Alta | Cobrir exaustivamente com testes Pest antes de usar em produção |
| API do catálogo sobrecarregada | Baixa | Cache Redis + rate limiting já no boilerplate |

---

## Implementation Phases

| # | Fase | Descrição | Status | Paralelo | Depende | PRP |
|---|------|-----------|--------|----------|---------|-----|
| 1 | Fundação do Produto | Produtos, variações, categorias, marcas, fotos | in-progress | com 2 | - | `.claude/PRPs/plans/phase-1-product-foundation.plan.md` |
| 2 | Fornecedores e Compras | Fornecedores, pedidos de compra, recebimento, custo médio | pending | com 1 | - | - |
| 3 | Controle de Estoque | Movimentações, ajustes, alertas, inventário | pending | - | 1, 2 | - |
| 4 | Clientes e PDV | Clientes, PDV completo, formas de pagamento, margem ao vivo | pending | com 5 | 3 | - |
| 5 | Financeiro | Contas a pagar/receber, caixa, DRE gerencial, fluxo de caixa | pending | com 4 | 3 | - |
| 6 | Comercial | Kits, promoções, cupons, orçamentos, trocas/devoluções | pending | - | 4, 5 | - |
| 7 | API do Catálogo | API pública de produtos, disponibilidade, pedidos externos | pending | com 8 | 3, 6 | - |
| 8 | Relatórios e Dashboard | Relatórios gerenciais, dashboard, curva ABC, análise de grade | pending | com 7 | 5, 6 | - |
| 9 | CRM e Gestão Avançada | CRM, lista de espera, pós-venda, metas, comissão, sugestão de reposição | pending | - | 6, 8 | - |

### Detalhamento das Fases

**Fase 1: Fundação do Produto** (~3 semanas)
- **Goal**: Cadastrar qualquer produto fitness com todas as variações possíveis
- **Scope**: CRUD de produtos, variações (tamanho × cor × modelo), categorias, subcategorias, marcas, coleções, fotos por variação, SKU automático, código de barras, preço, custo, margem, status, visibilidade no catálogo
- **Success signal**: Consigo cadastrar "Legging Preta" com tamanhos PP/P/M/G/GG e 3 cores, cada uma com foto, preço e estoque individual

**Fase 2: Fornecedores e Compras** (~3 semanas, paralelo com Fase 1)
- **Goal**: Registrar de onde vieram os produtos e quanto custaram
- **Scope**: CRUD de fornecedores, pedido de compra com grade por variação, recebimento parcial/total, rateio de frete e despesas, cálculo de custo real, parcelas a pagar, histórico de compras, devolução ao fornecedor
- **Success signal**: Registro uma compra de 50 peças de 3 fornecedores, rateio o frete e vejo o custo unitário de cada variação atualizado

**Fase 3: Controle de Estoque** (~2 semanas)
- **Goal**: Estoque sempre confiável e rastreável
- **Scope**: Estoque por variação, movimentações automáticas (entrada/saída), ajuste manual com justificativa, alerta de estoque baixo, inventário, histórico completo, valor total em estoque, cobertura e giro
- **Success signal**: Qualquer divergência de estoque tem rastreamento completo de quem fez o quê e quando

**Fase 4: Clientes e PDV** (~4 semanas)
- **Goal**: Registrar vendas rápido e corretamente
- **Scope**: Cadastro de clientes, PDV com busca por produto/SKU/barcode, seleção de variação, desconto por item/geral, múltiplas formas de pagamento, taxa de cartão, margem calculada ao vivo, finalização, baixa de estoque automática, cancelamento/estorno, comprovante
- **Success signal**: Venda registrada em menos de 60 segundos, estoque baixado automaticamente, margem calculada na hora

**Fase 5: Financeiro** (~3 semanas, paralelo com Fase 4)
- **Goal**: Saber exatamente quanto dinheiro entrou, saiu e sobrou
- **Scope**: Contas a pagar (compras + despesas), contas a receber, caixa diário (abertura/fechamento/suprimento/sangria), categorias financeiras, centros de custo, DRE gerencial, fluxo de caixa, margem por venda/produto/categoria
- **Success signal**: No fim do dia sei: vendas brutas, taxas descontadas, custo dos produtos, lucro líquido estimado

**Fase 6: Comercial** (~4 semanas)
- **Goal**: Vender mais com kits, promoções e controle de trocas
- **Scope**: Kits/combos (com controle de estoque dos itens), promoções (% / valor fixo / leve X pague Y / valor mínimo), cupons, orçamentos/pré-vendas com validade e reserva, trocas e devoluções com estorno e volta ao estoque
- **Success signal**: Promoção "compre legging + top ganhe 15% off" aplica automaticamente no PDV com margem calculada

**Fase 7: API do Catálogo** (~3 semanas, paralelo com Fase 8)
- **Goal**: Disponibilizar produtos para catálogo Nuxt.js sem duplicar cadastro
- **Scope**: API pública REST de produtos com variações, fotos, preços, disponibilidade em tempo real, destaque, filtros (categoria/cor/tamanho/preço), recebimento e validação de pedidos externos, reserva de estoque, conversão em venda
- **Success signal**: Catálogo Nuxt.js lê a API e exibe disponibilidade em tempo real; pedido do cliente chega no ERP

**Fase 8: Relatórios e Dashboard** (~3 semanas, paralelo com Fase 7)
- **Goal**: Inteligência para decidir o que comprar, promover e liquidar
- **Scope**: Dashboard com KPIs diários, relatórios de vendas/estoque/financeiro/compras/clientes, curva ABC de produtos, análise de grade (quais tamanhos vendem mais), análise de cores, relatórios de promoções e cupons
- **Success signal**: Em 30 segundos sei faturamento do mês, margem média, produtos parados e tamanhos com maior saída

**Fase 9: CRM e Gestão Avançada** (~3 semanas)
- **Goal**: Reter clientes e automatizar decisões de compra
- **Scope**: Segmentação de clientes, lista de espera por produto, notificação de reposição, sugestão de reposição automática, pós-venda com lembrete, metas por período, comissão por vendedor, campanhas comerciais
- **Success signal**: Sistema sugere compra de "Legging P Preta" porque está em estoque mínimo e é o item mais vendido

### Notas de Paralelismo

- **Fases 1 e 2** correm em paralelo — produtos e fornecedores são independentes
- **Fases 4 e 5** correm em paralelo — PDV e financeiro têm pontos de integração mas podem ser desenvolvidos juntos
- **Fases 7 e 8** correm em paralelo — API e relatórios são independentes entre si
- **Fase 3** (estoque) é o hub central — nenhum módulo que movimenta estoque pode ser construído antes dela

### Cronograma Estimado (6 meses)

| Mês | Fases em andamento |
|-----|-------------------|
| Mês 1 | Fase 1 + Fase 2 (paralelo) |
| Mês 2 | Fase 3 + início Fase 4 |
| Mês 3 | Fase 4 + Fase 5 (paralelo) |
| Mês 4 | Fase 6 |
| Mês 5 | Fase 7 + Fase 8 (paralelo) |
| Mês 6 | Fase 9 + testes de integração + ajustes finais |

---

## Decisions Log

| Decisão | Escolha | Alternativas | Justificativa |
|---------|---------|--------------|---------------|
| Usar o boilerplate existente | Sim | Partir do zero | Economiza 3-4 semanas de infraestrutura |
| Multi-tenancy | Desligado (single-store) | Ativado | Complexidade desnecessária para uso próprio |
| Variações como entidade própria | Sim (ProductVariant) | Atributos no produto | Permite estoque individual, foto, preço e SKU por variação |
| API do catálogo separada do frontend | Sim | Tudo num sistema | Permite evolução independente do catálogo |
| Frontend separado (Nuxt.js) | Sim | Blade + Inertia | Escolha do proprietário; permite interface mais rica |
| Custo médio ponderado | Sim | PEPS/UEPS | Mais simples e adequado para pequena operação |
| SELECT FOR UPDATE no PDV | Sim | Optimistic locking | Evita race condition em baixas simultâneas de estoque |

---

## Research Summary

**Contexto de mercado**
ERPs genéricos (Bling, Tiny) falham no controle de grades de roupas — a variação tamanho×cor não é tratada como entidade com estoque individual. ERPs específicos para moda são complexos e caros para pequena operação. Oportunidade clara para sistema próprio simples e preciso, especialmente pelo canal de catálogo personalizado.

**Contexto técnico**
O boilerplate entrega: auth completo, permissões, media library, auditoria, domain-driven architecture, API response pattern, rate limiting, health checks. Economiza ~4 semanas de trabalho base. A maior complexidade técnica está na modelagem correta de variações e no cálculo de custo médio ponderado na entrada de produtos.

---

*Gerado: 2026-06-26*
*Status: DRAFT — validar perguntas em aberto antes de iniciar implementação*
