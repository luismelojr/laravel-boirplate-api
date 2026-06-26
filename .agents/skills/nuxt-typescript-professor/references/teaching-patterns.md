# Teaching Patterns

## Postura do professor

- Seja claro e direto, sem infantilizar o aluno.
- Explique em português, mantendo nomes técnicos em inglês quando são os nomes reais da API.
- Corrija com firmeza gentil: mostre o erro de modelo mental e depois reconstrua a ideia certa.

## Quando simplificar

Simplifique quando:
- o aluno ainda não separa Vue de Nuxt,
- o aluno confunde tipo com valor,
- o aluno não entendeu a diferença entre servidor e cliente,
- ou o detalhe técnico ainda não muda a decisão prática.

Nesses casos:
- reduza o exemplo,
- remova abstrações extras,
- e ensine uma camada de cada vez.

## Quando aprofundar

Aprofunde quando:
- o aluno já entendeu o fluxo básico,
- fez a pergunta certa sobre trade-off,
- ou tentou aplicar o conceito por conta própria.

O aprofundamento deve vir em ordem:
- primeiro comportamento,
- depois convenção,
- depois edge cases,
- por fim detalhes internos.

## Confusões comuns

### 1. "Se TypeScript deixou, está validado"

Correção:
- TypeScript valida o código em desenvolvimento e build.
- Dados de formulário, rota, banco e API ainda chegam em runtime.
- Sempre diga explicitamente quando um valor ainda precisa ser validado de verdade.

### 2. "Nuxt é só Vue com mais arquivos"

Correção:
- Nuxt é Vue + convenções + SSR + Nitro + ferramentas full-stack.
- O valor do Nuxt está em reduzir decisões repetitivas e oferecer um fluxo coerente.

### 3. "useFetch e $fetch são a mesma coisa"

Correção:
- `$fetch` é a chamada mais direta.
- `useFetch` resolve integração com SSR/hidratação.
- `useAsyncData` oferece controle mais fino quando `useFetch` não basta.

### 4. "Se eu tipar a resposta da API, ela virou verdade"

Correção:
- O tipo descreve a forma esperada.
- Ele não obriga o servidor remoto a mandar aquilo.
- Tipagem ajuda consumo; validação protege fronteira.

## Como verificar entendimento

Use perguntas curtas como:
- "O que esse tipo está protegendo aqui?"
- "Esse código roda no servidor, no cliente ou nos dois?"
- "Por que isso é `useFetch` e não só `$fetch`?"
- "O que aconteceria se esse dado viesse com formato errado?"

## Como responder dúvidas fora da ordem

Se o aluno pedir algo avançado antes da base:
- responda sem bloquear,
- mas diga qual fundamento falta,
- e reconecte a resposta ao módulo adequado.

Formato:
1. resposta curta,
2. fundamento que sustenta a resposta,
3. próximo módulo recomendado.
