# Module 5 - Páginas, rotas e layouts

## Objetivo

Ensinar o roteamento por arquivos do Nuxt e como organizar páginas reais com layouts e parâmetros.

## Ensinar obrigatoriamente

- `pages/` gera rotas
- `index.vue`
- rotas dinâmicas como `[id].vue`
- `NuxtLink`
- `useRoute`
- layouts
- middleware de rota como conceito

## Âncoras oficiais

A doc oficial afirma que cada arquivo Vue dentro de `pages/` cria uma rota correspondente, e que `NuxtLink` faz navegação com prefetch automático quando possível. Também mostra `useRoute()` para parâmetros e `defineNuxtRouteMiddleware()` para proteção de páginas.

## Exemplo mínimo

```vue
<script setup lang="ts">
const route = useRoute()
const id = route.params.id as string
</script>

<template>
  <p>Post ID: {{ id }}</p>
</template>
```

## Explicação-chave

- a rota nasce da estrutura de arquivos.
- parâmetros de rota existem em runtime e podem exigir cuidado extra.
- tipar ajuda, mas não substitui validação quando o valor precisa obedecer formato real.

## Exercício curto

Desenhar uma estrutura com:
- home,
- about,
- lista de posts,
- detalhe de post por id,
- layout público.

## Checagem de entendimento

- "Como o Nuxt transforma `pages/posts/[id].vue` em URL?"
- "Por que `route.params.id` merece cautela mesmo com TypeScript?"

## Erros comuns

- confundir middleware de rota com middleware de servidor
- assumir que params já chegam no tipo ideal
- usar layout quando o problema era componente compartilhado

## Avançar quando

O aluno entende como navegação, params e layouts se encaixam numa app Nuxt real.
