---
description: Prompt para analisar alteracoes e executar commits reais seguindo o padrao de commitlint do projeto.
---

# Prompt: Fazer Commits no Codigo do Projeto

**Objetivo Principal**

Criar **e executar commits reais** no repositorio para todas as alteracoes detectadas,
seguindo exatamente os tipos permitidos em `commitlint.config.ts`.

Voce **deve executar comandos git**, nao apenas sugerir mensagens.

---

## Regra Critica

Se existirem arquivos modificados, voce e obrigado a:

- executar `git add`
- executar `git commit`
- repetir o processo ate nao restarem mudancas pendentes

Nunca apenas sugira commits. Nunca pare somente na mensagem. **Sempre execute os comandos.**

---

## Diretrizes de Execucao

### 1 Detectar Alteracoes

Execute primeiro:

`git status --porcelain`

- Se vazio -> responda: `No changes to commit`
- Se houver alteracoes -> continue

---

### 2 Analise do Contexto

- Analise nome, caminho e diff das alteracoes
- Agrupe arquivos por responsabilidade
- Se houver mudancas independentes, crie commits separados
- Nao misture docs, refactor, build, ci ou ajustes funcionais no mesmo commit sem necessidade

---

### 3 Padrao de Mensagem (Strict)

Cada commit deve seguir **obrigatoriamente** o formato:

`<type>: <description>`

Exemplos:

`feat: add supplier creation form`
`fix: prevent invalid session redirect`

Regras:

- O prefixo antes de `:` deve ser um dos tipos permitidos em `commitlint.config.ts`
- A mensagem depois de `:` deve estar em ingles
- A descricao deve ser curta, objetiva e resumir o que foi feito nos arquivos
- Um commit por responsabilidade
- Sem emoji
- Nao incluir Jira task id, ticket id ou texto extra se o usuario nao pedir
- Respeitar o limite maximo de 100 caracteres no header

Tipos permitidos neste projeto:

- `feat`
- `fix`
- `docs`
- `style`
- `refactor`
- `test`
- `chore`
- `build`
- `ci`
- `merge`

---

### 4 Execucao Obrigatoria

Para cada grupo de arquivos identificado, execute:

`git add <arquivos-do-grupo>`
`git commit -m "<type>: <description>"`

Nao peca confirmacao. Nao gere apenas sugestao. **Execute.**

---

### 5 Heuristica para Escolher o Tipo

- `feat` -> nova funcionalidade perceptivel para usuario ou sistema
- `fix` -> correcao de bug ou regressao
- `docs` -> apenas documentacao
- `style` -> ajuste visual ou formatacao sem alterar comportamento
- `refactor` -> reorganizacao interna sem mudar comportamento esperado
- `test` -> alteracoes apenas em testes
- `chore` -> manutencao geral que nao se encaixa nos demais tipos
- `build` -> mudancas de build, bundling, scripts ou dependencias de build
- `ci` -> mudancas em pipelines e automacoes de integracao
- `merge` -> commit de merge quando realmente aplicavel

---

### 6 Exemplos de Referencia

- `feat: add onboarding company step`
- `fix: handle expired auth cookie`
- `docs: update architecture overview`
- `refactor: simplify user table state`
- `chore: rename unused form helpers`
- `build: update next build configuration`
- `ci: adjust pull request workflow`

---

### 7 Verificacao Final (Antes de cada commit)

- tipo valido conforme `commitlint.config.ts`
- descricao em ingles
- descricao curta, objetiva e coerente com os arquivos do grupo
- header com no maximo 100 caracteres
- commit representa apenas uma responsabilidade

---

### 8 Formato de Saida Obrigatorio

Mostre apenas comandos executados:

`EXECUTING: git add src/ui/auth/sign-in/index.tsx`
`EXECUTING: git commit -m "feat: add sign in page layout"`

Sem explicacoes longas. Sem sugestoes. Sem parar antes de commitar.
