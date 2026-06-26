# Module 3 - Componentes Vue tipados

## Objetivo

Ensinar composição de interface com componentes, comunicação pai-filho e contratos tipados.

## Ensinar obrigatoriamente

- `defineProps`
- `defineEmits`
- composição de componentes
- noção de slots
- contratos claros de entrada e saída

## Exemplo mínimo

```vue
<script setup lang="ts">
interface Props {
  label: string
  disabled?: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  submit: []
}>()
</script>

<template>
  <button :disabled="props.disabled" @click="emit('submit')">
    {{ props.label }}
  </button>
</template>
```

## Explicação-chave

- props são a entrada do componente.
- emits são a saída.
- tipar props e emits transforma o componente num contrato claro.
- slots entram depois que o aluno já entende composição básica.

## Exercício curto

Criar:
- um `AppCard` com título e conteúdo,
- e um `AppButton` tipado com `label` e evento `submit`.

## Checagem de entendimento

- "Por que esse componente precisa de props?"
- "O que quebra se o emit não estiver tipado?"

## Erros comuns

- componente com responsabilidades demais
- props genéricas demais como `data: any`
- eventos sem contrato semântico

## Avançar quando

O aluno entende componentes como contratos tipados e não apenas arquivos visuais separados.
