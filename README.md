<h1 align="center">
  <img src="public/assets/images/card-games.png" alt="Cardmancer icon" width="32" height="32" align="center"> Cardmancer
</h1>

<p align="center">
  Portal administrativo para organizar coleções de Magic: The Gathering, Pokémon e Yu-Gi-Oh!
</p>

<div align="center">
  <img alt="PHP 8.3" src="https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white">
  <img alt="MySQL 8.0" src="https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white">
  <img alt="Docker Compose" src="https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white">
</div>

## 🖥️ Sobre

**Cardmancer** é uma aplicação web para catalogar cartas de diferentes card games em
um único ambiente administrativo. O sistema oferece autenticação, cadastro, edição,
exclusão, busca, filtros, paginação e imagens por upload ou URL.

A aplicação foi construída sem frameworks ou dependências de Composer e npm. O backend
usa PHP com PDO e sessões nativas, enquanto a interface combina HTML renderizado no
servidor, CSS e JavaScript com ES Modules.

---

## ✨ Funcionalidades

- [x] Login e logout com sessão protegida
- [x] Catálogo unificado para Magic, Pokémon e Yu-Gi-Oh!
- [x] CRUD completo de cartas
- [x] Busca, filtros e paginação
- [x] Edições e raridades dependentes do card game escolhido
- [x] Consulta a APIs públicas com catálogo local como fallback
- [x] Imagens por upload ou URL, com preview antes do envio
- [x] Validação no frontend e no backend
- [x] Proteção CSRF nas operações de escrita
- [x] Interface responsiva para desktop e dispositivos móveis

---

## ⚙️ Arquitetura

### 🛠️ Tecnologias e ferramentas

- **PHP 8.3** e **Apache** para a aplicação web
- **MySQL 8.0** para persistência
- **PDO** para acesso ao banco de dados
- **Sessões nativas do PHP** para autenticação
- **HTML5**, **CSS3** e **JavaScript vanilla** com ES Modules na interface
- **Docker Compose** para executar aplicação e banco
- **Runner autoral em PHP** para testes automatizados

As respostas da API seguem o contrato `{ data, error, meta }`. Mais detalhes sobre
camadas, fluxo HTTP e segurança estão em
[`documentation/architecture.md`](documentation/architecture.md).

---

## 🚀 Como rodar a aplicação

### 🔧 Pré-requisitos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) com Docker Compose
- [Git](https://git-scm.com/) apenas se o projeto for obtido por um repositório Git
- Porta `8080` disponível na máquina

Não é necessário instalar PHP, Apache, MySQL, Composer, Node.js, npm ou Bun localmente.

### 📟 Inicialização passo a passo

1. Abra um terminal na raiz do projeto, onde está o arquivo `docker-compose.yml`.

2. Opcionalmente, crie um `.env` caso queira alterar os valores padrão:

```bash
# Linux, macOS ou Git Bash
cp .env.example .env
```

```powershell
# Windows PowerShell
Copy-Item .env.example .env
```

O projeto funciona sem esse passo porque o Docker Compose já define valores padrão.

3. Construa as imagens e inicie os containers em segundo plano:

```bash
docker compose up --build -d
```

4. Verifique se os serviços `app` e `db` estão em execução:

```bash
docker compose ps
```

Na primeira inicialização, aguarde o banco ficar saudável. O esquema, o usuário de teste
e seis cartas iniciais são criados automaticamente.

5. Acesse [http://localhost:8080](http://localhost:8080) no navegador.

### 🔐 Credenciais para teste

| Campo | Valor |
| --- | --- |
| E-mail | `admin@indigoplateau.local` |
| Senha | `Indigo@123` |

O seed armazena apenas o hash bcrypt da senha. A senha em texto puro não é persistida
no banco de dados.

### Encerrar a aplicação

```bash
docker compose down
```

Esse comando encerra os containers sem apagar os dados salvos no volume do MySQL.

---

## 🔑 Variáveis de ambiente

O arquivo [`.env.example`](.env.example) documenta todas as configurações disponíveis:

| Variável | Padrão | Descrição |
| --- | --- | --- |
| `APP_ENV` | `local` | Ambiente da aplicação |
| `APP_DEBUG` | `1` | Ativa mensagens detalhadas de erro |
| `APP_URL` | `http://localhost:8080` | URL pública da aplicação |
| `APP_SESSION_NAME` | `cardmancer_session` | Nome do cookie de sessão |
| `DB_HOST` | `db` | Host do MySQL na rede Docker |
| `DB_PORT` | `3306` | Porta interna do MySQL |
| `DB_NAME` | `cardmancer` | Nome do banco de dados |
| `DB_USER` | `cardmancer` | Usuário da aplicação no MySQL |
| `DB_PASSWORD` | `cardmancer` | Senha do usuário da aplicação |
| `DB_ROOT_PASSWORD` | `root` | Senha inicial do usuário root do MySQL |
| `UPLOAD_MAX_BYTES` | `5242880` | Limite de upload em bytes (5 MB) |

---

## 🧪 Testes e comandos úteis

Com os containers em execução, rode a suíte automatizada:

```bash
docker compose exec app php tests/run.php
```

O runner cobre catálogos dependentes, login válido e inválido, proteção de rota, CSRF,
validação de combinações, CRUD, busca, paginação e limite de upload.

```bash
docker compose logs app       # exibe os logs da aplicação
docker compose logs db        # exibe os logs do banco
docker compose up --build -d  # reconstrói e inicia os serviços
docker compose down           # encerra os serviços e preserva os dados
```

### Resetar o banco

Para apagar os dados locais e executar novamente o esquema e o seed:

```bash
docker compose down -v
docker compose up --build -d
```

> O primeiro comando remove permanentemente o volume local do banco. Use-o somente
> quando o reset completo for desejado.

---

## 🔌 Endpoints principais

| Método | Rota | Acesso | Uso |
| --- | --- | --- | --- |
| `GET` | `/login` | Público | Tela de autenticação |
| `GET` | `/cards` | Sessão | Catálogo administrativo |
| `POST` | `/api/auth/login` | Público | Criar sessão |
| `POST` | `/api/auth/logout` | Sessão + CSRF | Encerrar sessão |
| `GET` | `/api/auth/session` | Público | Consultar sessão atual |
| `GET` | `/api/catalog/options?game=magic` | Público | Listar edições e raridades |
| `GET` | `/api/cards` | Sessão | Listar, buscar e paginar cartas |
| `GET` | `/api/cards/{id}` | Sessão | Detalhar carta |
| `POST` | `/api/cards` | Sessão + CSRF | Criar carta |
| `POST` | `/api/cards/{id}` com `_method=PUT` | Sessão + CSRF | Editar carta com multipart |
| `DELETE` | `/api/cards/{id}` | Sessão + CSRF | Excluir carta |

---

## 🗂️ Catálogos externos

Ao escolher um card game, `/api/catalog/options` consulta as coleções atuais nas APIs
Scryfall (Magic), Pokémon TCG API (Pokémon) e YGOPRODeck (Yu-Gi-Oh!). Os arquivos
`data/editions.json` e `data/rarities.json` funcionam como fallback caso um serviço
externo esteja indisponível.

As raridades mantêm valores internos estáveis para validação e recebem traduções em
português na interface. O backend valida novamente a relação entre jogo, edição e
raridade antes de persistir uma carta.

---

## 📁 Estrutura

```text
data/                 # catálogos locais em JSON
database/init/        # esquema e seed executados pelo MySQL
docker/               # imagem PHP e VirtualHost do Apache
documentation/        # documentação de arquitetura
public/               # entrada HTTP, CSS, JavaScript, imagens e uploads
src/                  # núcleo, segurança, controllers, serviços e repositórios
tests/                # runner e testes automatizados em PHP
views/                # telas renderizadas no servidor
```

---

## 💡 Decisões de UX e Produto

### 1. Ações concentradas no inspetor lateral

As ações de editar e excluir ficam no inspetor lateral, em vez de serem repetidas em
cada linha da tabela. Essa escolha reduz ruído visual e cliques destrutivos acidentais,
mantendo a listagem livre para comparar cartas e navegar entre registros.

### 2. Campos dependentes com revelação progressiva

Edição e raridade permanecem desabilitadas até que um card game seja selecionado. Em
seguida, as opções válidas são carregadas pelo backend. Isso orienta a ordem natural de
preenchimento, evita combinações impossíveis e deixa claro por que um campo ainda não
pode ser usado.

### 3. Preview único para upload e URL

As duas formas de adicionar uma imagem compartilham o mesmo preview antes do envio. A
pessoa consegue confirmar visualmente o resultado sem sair do formulário nem perder o
contexto da listagem, reduzindo cadastros com imagens incorretas.

### 4. Integrações externas com fallback local

O produto busca coleções atualizadas em APIs especializadas, mas preserva um catálogo
local para indisponibilidades. Assim, os dados podem estar atualizados sem tornar o
fluxo principal de cadastro dependente da estabilidade de terceiros.

---

## 🃏 Dados iniciais

O seed inclui seis cartas dos três jogos, com nomes em inglês, traduções quando
disponíveis e imagens locais em `public/assets/images`. Esses registros permitem avaliar
a listagem, os filtros e a navegação logo após o primeiro acesso.
