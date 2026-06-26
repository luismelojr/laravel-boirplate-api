# Module 9 - Arquitetura real e boas praticas

## Objetivo

Fechar a trilha mostrando como organizar um projeto Nuxt de verdade com coerência, boa tipagem e entendimento do ciclo de renderização.

## Ensinar obrigatoriamente

- separar UI, dados e servidor
- nomear bem componentes e composables
- evitar `any` estrutural
- noções de SEO e SSR
- noções de `routeRules`
- pensar em escalabilidade sem complicar cedo demais

## Âncoras oficiais

A introdução oficial destaca SSR por padrão, auto-imports, TypeScript zero-config e Nitro como base full-stack. A seção `Server` destaca `routeRules` e deploy híbrido. Essas ideias devem sustentar a arquitetura ensinada neste módulo.

## Explicação-chave

- arquitetura boa reduz atrito antes de virar "arquitetura bonita".
- o projeto cresce melhor quando cada pasta e abstração tem um papel claro.
- SSR, dados e rotas não são apêndices: fazem parte da decisão estrutural.

## Checklist de boas práticas

- componentes pequenos e com contrato claro
- composables focados
- tipos nomeados para dados importantes
- validação real nas fronteiras
- distinção explícita entre código de servidor e de cliente
- escolhas de fetch coerentes com SSR

## Exercício curto

Pedir para o aluno propor a estrutura de uma app pequena com:
- home,
- listagem de produtos,
- detalhe de produto,
- endpoint interno,
- e composable de filtros.

## Checagem de entendimento

- "Que abstração você criaria cedo demais aqui e por quê evitar isso?"
- "Onde a tipagem ajuda mais nesse projeto?"

## Próximos passos

Depois deste módulo, o professor pode:
- revisar qualquer módulo anterior,
- orientar um mini projeto,
- ou responder dúvidas pontuais sem abandonar a trilha.
