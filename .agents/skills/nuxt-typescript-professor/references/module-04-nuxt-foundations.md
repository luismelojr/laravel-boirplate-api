# Module 4 - Entrada no Nuxt com TypeScript

## Objetivo

Mostrar o que o Nuxt adiciona sobre Vue: convenções, estrutura, SSR por padrão, auto-imports e organização full-stack.

## Ensinar obrigatoriamente

- o valor do Nuxt como framework
- `app.vue`
- `pages/`
- `components/`
- `composables/`
- `server/`
- `nuxt.config.ts`
- auto-imports

## Âncoras oficiais

Segundo a introdução oficial do Nuxt, o framework enfatiza convenções, SSR pronto para uso, auto-imports e suporte TypeScript zero-config. Ele também destaca `server/api/` e `server/middleware/` como partes do fluxo full-stack com Nitro.

## Explicação-chave

- Vue te ensina a construir a interface.
- Nuxt decide muito da estrutura por convenção.
- isso reduz configuração manual e ajuda a manter projetos coerentes.

## Exercício curto

Pedir para o aluno explicar o papel de cada pasta:
- `pages`
- `components`
- `composables`
- `server`

## Checagem de entendimento

- "O que o Nuxt faz automaticamente que um projeto Vue puro faria manualmente?"
- "Por que `nuxt.config.ts` é parte importante do projeto?"

## Erros comuns

- pensar que tudo precisa de import manual
- tratar `server/` como algo separado do app
- não perceber que Nuxt mistura frontend e backend no mesmo projeto

## Avançar quando

O aluno entende o mapa mental do projeto Nuxt antes de entrar em rotas, dados e SSR.
