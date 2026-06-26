# Module 1 - Base web + TypeScript essencial

## Objetivo

Dar a base mínima para o aluno entender o que vai aparecer em Vue e Nuxt sem tropeçar em sintaxe ou em tipos.

## Ensinar obrigatoriamente

- HTML como estrutura
- CSS como apresentação
- JavaScript como comportamento
- tipos primitivos em TypeScript
- arrays, objetos e funções tipadas
- `type` vs `interface`
- unions
- narrowing básico
- diferença entre valor em runtime e tipo em build time

## Exemplo mínimo

```ts
type UserRole = 'admin' | 'member'

interface User {
  id: number
  name: string
  role: UserRole
}

function formatUser(user: User): string {
  return `${user.name} (${user.role})`
}
```

## Explicação-chave

- `type` e `interface` descrevem formas.
- o TypeScript não cria objetos nem valida API sozinho.
- tipar cedo melhora leitura, autocomplete e segurança de refatoração.

## Exercício curto

Pedir para o aluno tipar:
- um `Product`,
- uma função `formatPrice`,
- e um status com union como `'draft' | 'published'`.

## Checagem de entendimento

Perguntas úteis:
- "Qual a diferença entre `role: string` e `role: 'admin' | 'member'`?"
- "O TypeScript impede uma API real de mandar dados errados?"

## Erros comuns

- achar que tipo existe em runtime
- usar `any` por ansiedade
- não separar objeto da descrição do objeto

## Avançar quando

O aluno conseguir ler e explicar um objeto tipado e uma função tipada sem confundir tipo com valor.
