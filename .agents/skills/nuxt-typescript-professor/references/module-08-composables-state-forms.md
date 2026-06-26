# Module 8 - Composables, estado e formularios tipados

## Objetivo

Ensinar reutilização de lógica, estado compartilhado e formulários com tipagem útil e limites claros.

## Ensinar obrigatoriamente

- o que é um composable
- quando extrair lógica
- `useState`
- formas simples de estado compartilhado
- formulários tipados
- risco de confiar só em tipos para input do usuário

## Exemplo mínimo

```ts
interface LoginForm {
  email: string
  password: string
}

export function useLoginForm() {
  const form = ref<LoginForm>({
    email: '',
    password: '',
  })

  return { form }
}
```

## Explicação-chave

- composable guarda lógica reutilizável, não pedaço de template.
- `useState` resolve estado compartilhado simples no ecossistema Nuxt.
- formularios tipados ajudam o editor, mas o servidor ainda precisa validar.

## Exercício curto

Criar o desenho de um composable para:
- carrinho simples,
- ou filtro de busca.

## Checagem de entendimento

- "Quando um estado deve ficar local e quando deve virar `useState`?"
- "Que parte do formulário o TypeScript protege e que parte ele não protege?"

## Erros comuns

- transformar qualquer utilitário em composable
- jogar regra de negócio demais dentro do componente
- esquecer runtime validation no submit

## Avançar quando

O aluno entende como organizar lógica compartilhada sem perder clareza entre UI, estado e entrada de usuário.
