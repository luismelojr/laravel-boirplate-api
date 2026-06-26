# Module 2 - Fundamentos de Vue com TypeScript

## Objetivo

Apresentar a base mental do Vue com foco em `template + reatividade + eventos`, já com `script setup lang="ts"`.

## Ensinar obrigatoriamente

- Single File Components
- `<template>` e `<script setup lang="ts">`
- `ref`
- `computed`
- `v-if`
- `v-for`
- eventos
- `v-model`

## Exemplo mínimo

```vue
<script setup lang="ts">
import { computed, ref } from 'vue'

const count = ref<number>(0)
const doubled = computed(() => count.value * 2)

function increment(): void {
  count.value += 1
}
</script>

<template>
  <div>
    <p>Count: {{ count }}</p>
    <p>Double: {{ doubled }}</p>
    <button @click="increment">Increment</button>
  </div>
</template>
```

## Explicação-chave

- `ref` guarda estado reativo.
- em `<script>`, usa-se `.value`.
- no `<template>`, o Vue desempacota refs.
- `computed` descreve valor derivado, não ação.

## Exercício curto

Criar um componente com:
- um campo de nome,
- um contador de caracteres,
- e um aviso com `v-if` quando o nome estiver vazio.

## Checagem de entendimento

- "Por que `count` é `ref<number>` e não só `number`?"
- "Quando usar `computed` em vez de função comum?"

## Erros comuns

- esquecer `.value` no script
- usar `computed` para causar efeito colateral
- confundir renderização condicional com ocultação por CSS

## Avançar quando

O aluno consegue montar um componente simples reativo e explicar o papel de `ref` e `computed`.
