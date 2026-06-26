# Module 6 - Dados, SSR e hidratação

## Objetivo

Construir a base mental correta para buscar dados no Nuxt sem confundir cliente, servidor e hidratação.

## Ensinar obrigatoriamente

- SSR por padrão
- hidratação
- `$fetch`
- `useFetch`
- `useAsyncData`
- `data`, `error`, `status`
- diferença entre busca inicial e interação disparada pelo usuário

## Âncoras oficiais

A doc oficial diz que:
- `$fetch` é a forma mais simples de fazer request,
- `useFetch` é um wrapper SSR-safe em torno de `$fetch`,
- `useAsyncData` dá controle mais fino,
- e usar apenas `$fetch` no setup pode causar fetch duplo entre servidor e cliente.

## Exemplo mínimo

```vue
<script setup lang="ts">
interface Post {
  id: number
  title: string
}

const { data, error, status } = await useFetch<Post[]>('/api/posts')
</script>
```

## Explicação-chave

- `useFetch` e `useAsyncData` existem para integrar dados com SSR e payload.
- `$fetch` é ótimo para ações disparadas pelo usuário, como submit.
- tipar o retorno melhora o consumo, mas a API ainda pode devolver algo errado.

## Exercício curto

Comparar dois cenários:
- carregar lista inicial de posts,
- enviar um formulário de comentário.

Perguntar qual usa `useFetch` e qual usa `$fetch`, e por quê.

## Checagem de entendimento

- "Qual problema aparece se você usar só `$fetch` no setup universal?"
- "Quando `useAsyncData` entra no lugar de `useFetch`?"

## Erros comuns

- achar que tudo deve ser `$fetch`
- ignorar hidratação
- usar `useAsyncData` para efeito colateral

## Avançar quando

O aluno consegue justificar a escolha entre `useFetch`, `useAsyncData` e `$fetch`.
