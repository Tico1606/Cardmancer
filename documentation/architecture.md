# Arquitetura do Cardmancer

## Visão geral

O Cardmancer é um monólito web sem frameworks e sem dependências de Composer ou npm.
O backend usa PHP 8.3 com Apache, o banco é MySQL 8.0 e a interface combina views PHP
renderizadas no servidor com CSS e módulos JavaScript vanilla. O acesso ao banco acontece
exclusivamente por PDO com prepared statements, e a autenticação usa sessões nativas do
PHP.

```text
navegador
   |
   | HTTP: páginas, JSON, assets e uploads
   v
Apache (DocumentRoot: public/)
   |
   | arquivos existentes --------------------> CSS, JS, imagens e uploads
   |
   `-> public/index.php
          |
          v
       Request -> Router -> Controllers
                              |       |
                              |       `-> View -> views/*.php
                              |
                              +-> Security (sessão e CSRF)
                              +-> Validation
                              +-> Services -> catálogos JSON / APIs externas / filesystem
                              `-> Repositories -> PDO -> MySQL
```

## Execução e implantação

O ambiente local é definido em `docker-compose.yml` e possui dois serviços:

- `app`: imagem baseada em PHP 8.3 com Apache, extensão PDO MySQL e módulos Apache de
  rewrite, headers e expires. O VirtualHost em `docker/apache/vhost.conf` aponta o
  `DocumentRoot` para `public/`.
- `db`: MySQL 8.0 com volume persistente. Os scripts em `database/init/` criam o schema e
  os dados iniciais somente na primeira criação do volume.

O serviço `app` espera o healthcheck do MySQL antes de iniciar e publica a porta 80 do
container em `localhost:8080`. Somente `public/uploads` é montado separadamente no app;
os dados do MySQL permanecem no volume `liga_magic_cardmancer_db`.

As configurações são lidas primeiro das variáveis de ambiente e, quando existe, do
arquivo `.env`. `Config` centraliza URL e ambiente da aplicação, nome da sessão, conexão
com o banco e limite/diretório de uploads.

## Ciclo de uma requisição

1. O Apache entrega diretamente arquivos e diretórios existentes em `public/`.
2. `public/.htaccess` encaminha as demais URLs para `public/index.php`.
3. `src/bootstrap.php` registra o autoload do namespace `Cardmancer`, carrega `Config` e
   cria `Application`.
4. `Application::create()` monta manualmente o grafo de dependências, abre a conexão PDO,
   instancia repositórios, serviços, segurança e controllers, e registra todas as rotas.
5. `Request::capture()` normaliza método, caminho, query string, headers, JSON,
   formulários e arquivos enviados.
6. `Router` encontra a rota pelo método e pelo padrão de caminho, extrai parâmetros como
   `{id}` e executa o controller.
7. O controller retorna uma `Response` HTML, JSON ou de redirecionamento.

Formulários multipart podem simular `PUT`, `PATCH` e `DELETE` enviando `_method` em uma
requisição `POST`. Erros esperados usam `HttpException`. O front controller os converte
em JSON para `/api/*` e em uma página de erro para rotas HTML; exceções inesperadas são
registradas no log e retornam erro 500 genérico.

## Camadas e responsabilidades

- `src/Core`: configuração, conexão PDO, request, response, exceções HTTP, roteador,
  renderização de views e composição da aplicação.
- `src/Security`: ciclo de vida da sessão autenticada e validação de tokens CSRF.
- `src/Controllers`: contratos HTTP das páginas, autenticação, perfil, catálogo de opções
  e CRUD de cartas.
- `src/Validation`: validação e normalização compartilhadas dos dados de uma carta.
- `src/Services`: composição do catálogo local/remoto e armazenamento seguro de imagens.
- `src/Repositories`: consultas e mutações nas tabelas `users` e `cards`.
- `views`: documentos HTML renderizados pelo PHP para login, catálogo, detalhe e perfil.
- `public/assets`: folha de estilos, imagens estáticas e módulos JavaScript por página.
- `data`: fallback local de coleções e fonte das raridades válidas por card game.
- `database/init`: schema e seed executados pelo MySQL ao inicializar o volume.
- `tests`: runner de integração autoral executado diretamente com PHP.

Não há container de injeção de dependências nem middleware. As verificações de acesso e
CSRF são chamadas explicitamente pelos controllers responsáveis.

## Páginas e API

### Páginas HTML

| Método | Rota | Acesso | Responsabilidade |
| --- | --- | --- | --- |
| `GET` | `/` | público | Redirecionar para `/cards` ou `/login` conforme a sessão |
| `GET` | `/login` | público | Renderizar o login; redirecionar usuários autenticados |
| `GET` | `/cards` | sessão | Renderizar a estrutura do catálogo e os dados públicos do usuário |
| `GET` | `/cards/{id}` | sessão | Renderizar a estrutura da página de detalhe; a carta é carregada pela API |
| `GET` | `/profile` | sessão | Renderizar o formulário e o resumo do perfil |

### API JSON

| Método | Rota | Acesso | Responsabilidade |
| --- | --- | --- | --- |
| `POST` | `/api/auth/login` | público | Validar credenciais e criar a sessão |
| `POST` | `/api/auth/logout` | sessão + CSRF | Encerrar a sessão |
| `GET` | `/api/auth/session` | público | Informar o estado atual da sessão |
| `POST` | `/api/profile` | sessão + CSRF | Alterar nome, e-mail e, opcionalmente, senha |
| `GET` | `/api/catalog/options?game=...` | público | Retornar coleções e raridades do jogo |
| `GET` | `/api/cards` | sessão | Buscar, filtrar, ordenar e paginar cartas |
| `GET` | `/api/cards/{id}` | sessão | Retornar uma carta |
| `POST` | `/api/cards` | sessão + CSRF | Criar uma carta |
| `PUT` ou `PATCH` | `/api/cards/{id}` | sessão + CSRF | Atualizar uma carta |
| `DELETE` | `/api/cards/{id}` | sessão + CSRF | Excluir uma carta |

Todas as respostas da API usam o envelope `{ data, error, meta }` e o header
`Cache-Control: no-store`. Erros de validação podem incluir mensagens por campo em
`error.fields`. A listagem aceita `q`, `game`, `page`, `per_page`, `sort` e `direction`;
o limite máximo é de 100 itens por página.

## Autenticação e segurança

1. O login procura o e-mail normalizado em `users` e verifica o hash com
   `password_verify()`.
2. Um login válido regenera o ID da sessão, grava `user_id` e cria um token CSRF aleatório
   de 32 bytes.
3. O cookie de sessão tem escopo `/`, `HttpOnly`, `SameSite=Lax`, duração até o fim da
   sessão do navegador e `Secure` quando `APP_URL` usa HTTPS.
4. Páginas protegidas redirecionam para `/login` quando não há usuário. Endpoints
   protegidos respondem com 401.
5. Toda mutação autenticada exige o token no header `X-CSRF-Token` ou no campo `_csrf`.
6. O logout limpa os dados da sessão, expira o cookie e destrói a sessão no servidor.

As APIs nunca retornam `password_hash`. A atualização do perfil verifica formato e
unicidade do e-mail, exige no mínimo oito caracteres para uma nova senha e persiste
somente o hash criado por `password_hash()`.

## Persistência

O schema possui duas tabelas InnoDB com `utf8mb4`:

- `users`: nome, e-mail único, hash da senha e timestamps.
- `cards`: nomes em inglês e português, jogo, identificador da coleção, raridade, origem e
  valor da imagem, além de timestamps. `name_en` é único e há índices por jogo, coleção e
  pela combinação dos dois.

`CardRepository` implementa busca textual nos dois nomes, filtro por jogo, paginação e
uma allowlist de colunas de ordenação antes de compor o `ORDER BY`. Valores fornecidos
pelo cliente são enviados ao MySQL por parâmetros preparados.

Na criação da aplicação, os repositórios também reconciliam instalações antigas: garantem
as credenciais do usuário inicial e tentam adicionar a restrição de nome único caso ela
não exista. O schema e o seed canônicos continuam em `database/init/`.

## Catálogo de jogos

`CatalogService` carrega `data/editions.json` e `data/rarities.json` ao ser instanciado.
Para as coleções, ele consulta de forma síncrona as fontes públicas correspondentes:

- Magic: Scryfall (`/sets`).
- Pokemon: Pokémon TCG API (`/v2/sets`).
- Yu-Gi-Oh!: YGOPRODeck (`/api/v7/cardsets.php`).

Cada consulta possui timeout de oito segundos. As coleções remotas são normalizadas,
mescladas com as locais e deduplicadas por identificador; se uma API falhar, o catálogo
local permanece como fallback. O resultado remoto fica em memória durante a instância de
`CatalogService`, isto é, durante a requisição PHP atual. As raridades são sempre lidas do
JSON local.

O frontend recarrega `/api/catalog/options` quando o jogo muda. No backend,
`CardValidator` usa o mesmo serviço para normalizar o jogo e rejeitar combinações de
coleção ou raridade incompatíveis, independentemente da validação feita no navegador.

## Imagens

Uma carta pode usar uma URL externa ou um arquivo local:

- URLs aceitam somente os esquemas `http` e `https` e têm no máximo 500 caracteres.
- Uploads aceitam JPEG, PNG e WebP até o limite configurado, 5 MB por padrão.
- `UploadService` confere o código do upload, tamanho, origem temporária, MIME com
  `finfo` e conteúdo de imagem com `getimagesize`.
- Arquivos válidos recebem um nome aleatório de 32 caracteres hexadecimais e são salvos
  em `public/uploads/cards`.
- O `.htaccess` do diretório desabilita listagem e bloqueia execução de extensões de
  script.

Ao criar ou atualizar, um arquivo novo é removido se a persistência falhar. Na
substituição, limpeza ou exclusão de uma carta, o arquivo anterior gerenciado pela
aplicação é removido somente depois da mutação no banco.

## Frontend

As views PHP entregam o shell da página, dados públicos do usuário e o token CSRF em
atributos `data-*`. Os módulos em `public/assets/js` assumem as interações depois do
carregamento:

- `api.js`: cliente `fetch` compartilhado, envelope JSON, envio automático do CSRF e
  evento global de sessão expirada em respostas 401 ou 419.
- `login.js`: validação e envio do login, além da detecção de uma sessão existente.
- `cards.js`: estado do catálogo, sincronização com a query string, listagem, filtros,
  ordenação, paginação, formulário de criação/edição, preview de imagem e exclusão.
- `detail.js`: carregamento da carta por ID, renderização do detalhe e fluxos de edição e
  exclusão.
- `profile.js`: validação e atualização dos dados pessoais e da senha.

No desktop, o catálogo usa tabela, controles de consulta e formulário em drawer. No
mobile, os mesmos dados são renderizados como uma lista de cards e o formulário ocupa a
tela. A navegação para `/cards/{id}` oferece uma página de detalhe dedicada. Estados de
carregamento, vazio, erro, processamento, validação, confirmação destrutiva e sessão
expirada são controlados pelos módulos de cada página.

## Testes

`tests/run.php` é um runner PHP sem biblioteca externa. Ele instancia componentes reais,
despacha requests diretamente pela `Application` e usa o MySQL configurado no ambiente.
Atualmente cobre catálogos e combinações válidas, autenticação, perfil, proteção CSRF,
CRUD, unicidade de nome, busca, paginação e regras de upload. A execução prevista é
`docker compose exec app php tests/run.php`.