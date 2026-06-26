# Module 7 - Server routes e full-stack no Nuxt

## Objetivo

Ensinar como o Nuxt cria endpoints e lógica de servidor no mesmo projeto usando Nitro.

## Ensinar obrigatoriamente

- papel do Nitro
- `server/api/`
- `defineEventHandler`
- retorno de `json`, `text` ou `html`
- fronteira entre tipagem e validação
- noção de middleware de servidor

## Âncoras oficiais

A doc oficial apresenta o servidor do Nuxt como baseado em Nitro e mostra endpoints em `server/api/*.ts` com `defineEventHandler`. Também destaca `routeRules` como parte da camada de renderização e deploy híbrido.

## Exemplo mínimo

```ts
interface ProductDto {
  id: number
  name: string
}

export default defineEventHandler((): ProductDto[] => {
  return [
    { id: 1, name: 'Keyboard' },
    { id: 2, name: 'Mouse' },
  ]
})
```

## Explicação-chave

- `server/api/` é backend dentro do mesmo código-base.
- Nitro cuida do runtime e da portabilidade.
- o retorno pode estar tipado, mas entrada de usuário ainda precisa de validação real.

## Exercício curto

Pedir para o aluno descrever um endpoint `/api/profile`:
- qual dado ele devolveria,
- qual tipo ele usaria,
- e qual entrada precisaria validar em runtime.

## Checagem de entendimento

- "O que o Nitro faz por trás do Nuxt?"
- "Por que um tipo de retorno não basta para confiar em dados que chegam do cliente?"

## Erros comuns

- achar que endpoint tipado já está seguro
- misturar middleware de rota com middleware de servidor
- não perceber que Nuxt permite app full-stack real

## Avançar quando

O aluno enxerga `server/api/` como parte natural da arquitetura Nuxt, não como um anexo estranho.
