<?php

declare(strict_types=1);

$userName = (string) ($user['name'] ?? 'Administrador');
$userEmail = (string) ($user['email'] ?? '');
$initials = strtoupper(substr($userName, 0, 1) . substr(strrchr($userName, ' ') ?: '', 1, 1));
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Gerencie o catálogo de cartas do Cardmancer.">
    <title>Cardmancer</title>
    <link rel="icon" href="/assets/images/card-games.png?v=2" type="image/png">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="app-page management-page" data-page="cards" data-csrf-token="<?= htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
    <a class="skip-link" href="#main-content">Pular para o conteúdo</a>

    <div class="management-shell">
        <aside class="management-sidebar" aria-label="Navegação principal">
            <a class="sidebar-brand" href="/cards" aria-label="Cardmancer, gerenciamento da coleção">
                <img class="brand-logo-image" src="/assets/images/card-games.png" alt="">
                <span><strong>CARDMANCER</strong><small>Gerencie sua coleção</small></span>
            </a>

            <nav class="sidebar-navigation">
                <a class="sidebar-nav-item is-active" href="/cards">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="4" y="4" width="6" height="6" rx="1"></rect><rect x="14" y="4" width="6" height="6" rx="1"></rect><rect x="4" y="14" width="6" height="6" rx="1"></rect><rect x="14" y="14" width="6" height="6" rx="1"></rect></svg>
                    <span>Painel</span>
                </a>
            </nav>

            <div class="sidebar-account">
                <a class="sidebar-profile" href="/profile" aria-label="Abrir meu perfil">
                    <span class="sidebar-profile-avatar" aria-hidden="true"><?= htmlspecialchars($initials !== '' ? $initials : 'AD', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                    <span class="sidebar-profile-copy"><strong><?= htmlspecialchars($userName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong><small><?= htmlspecialchars($userEmail !== '' ? $userEmail : 'Sem e-mail', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small></span>
                </a>
                <button class="sidebar-logout" id="logout-button" type="button">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h4"></path><path d="m9 4 6 2v12l-6 2V4Z"></path><path d="M12 12h9M17 8l4 4-4 4"></path></svg>
                    <span>Sair</span>
                </button>
            </div>
        </aside>

        <div class="management-main">
            <header class="management-topbar">
                <span>Bem-vindo, <?= htmlspecialchars($userName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
            </header>

            <main id="main-content" class="management-content">
                <div class="management-inner">
                    <section class="management-hero" aria-labelledby="catalog-title">
                        <h1 id="catalog-title">Gerencie suas cartas</h1>
                        <p>Organize, edite e expanda sua coleção em um só lugar.</p>
                    </section>

                    <section class="summary-grid" aria-label="Resumo do catálogo">
                        <article class="summary-card">
                            <span class="summary-copy"><small>Total de cartas</small><strong id="summary-count">—</strong></span>
                        </article>
                        <article class="summary-card">
                            <span class="summary-copy"><small>Magic: The Gathering</small><strong id="summary-magic">—</strong></span>
                        </article>
                        <article class="summary-card">
                            <span class="summary-copy"><small>Pokémon</small><strong id="summary-pokemon">—</strong></span>
                        </article>
                        <article class="summary-card">
                            <span class="summary-copy"><small>Yu-Gi-Oh!</small><strong id="summary-yugioh">—</strong></span>
                        </article>
                    </section>

                    <section class="cards-panel" aria-labelledby="cards-panel-title">
                        <header class="cards-panel-header">
                            <div class="panel-copy">
                                <h2 id="cards-panel-title">Cartas</h2>
                                <p>Gerencie todas as cartas da sua coleção.</p>
                            </div>
                            <div class="panel-controls">
                                <div class="search-field management-search">
                                    <span aria-hidden="true">⌕</span>
                                    <label class="visually-hidden" for="search-input">Buscar carta</label>
                                    <input id="search-input" type="search" placeholder="Buscar cartas..." autocomplete="off">
                                    <kbd>⌘ K</kbd>
                                </div>
                                <button class="panel-filter-button" type="button" id="filter-toggle" aria-expanded="false" aria-controls="catalog-filters">
                                    <span aria-hidden="true">≡</span> Filtrar
                                </button>
                                <button class="panel-new-button" type="button" id="new-card-button">
                                    <span aria-hidden="true">+</span> Nova carta
                                </button>
                                <button class="panel-filter-reset" type="button" id="reset-filters" hidden>Remover Filtros</button>
                            </div>
                        </header>

                        <div class="catalog-filters" id="catalog-filters" hidden>
                            <div class="toolbar-select">
                                <label for="game-filter">Jogo</label>
                                <select id="game-filter">
                                    <option value="">Todos os jogos</option>
                                    <option value="magic">Magic: The Gathering</option>
                                    <option value="pokemon">Pokémon</option>
                                    <option value="yugioh">Yu-Gi-Oh!</option>
                                </select>
                            </div>
                            <div class="toolbar-select toolbar-sort">
                                <label for="sort-filter">Ordenar catálogo</label>
                                <select id="sort-filter">
                                    <option value="updated:desc">Mais recentes</option>
                                    <option value="name:asc">Nome: A a Z</option>
                                    <option value="name:desc">Nome: Z a A</option>
                                    <option value="game:asc">Jogo</option>
                                    <option value="rarity:asc">Raridade</option>
                                </select>
                            </div>
                            <span class="toolbar-result" id="toolbar-result" aria-live="polite">0 resultados.</span>
                        </div>

                        <div class="loading-panel" id="loading-state" hidden aria-label="Carregando cartas">
                            <div class="skeleton-row"><span></span><span></span><span></span><span></span></div>
                            <div class="skeleton-row"><span></span><span></span><span></span><span></span></div>
                            <div class="skeleton-row"><span></span><span></span><span></span><span></span></div>
                            <div class="skeleton-row"><span></span><span></span><span></span><span></span></div>
                        </div>

                        <div class="table-card" id="table-state">
                            <div class="table-scroll">
                                <table>
                                    <caption class="visually-hidden">Cartas cadastradas</caption>
                                    <thead>
                                        <tr>
                                            <th scope="col">Imagem</th>
                                            <th scope="col">Nome (inglês)</th>
                                            <th scope="col">Nome (português)</th>
                                            <th scope="col">Jogo</th>
                                            <th scope="col">Edição</th>
                                            <th scope="col">Raridade</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cards-table-body"></tbody>
                                </table>
                            </div>
                            <div class="mobile-card-list" id="mobile-card-list"></div>
                            <div class="table-footer">
                                <span id="page-caption">Página 1 de 1</span>
                                <div class="pagination" aria-label="Paginação">
                                    <button type="button" id="previous-page" aria-label="Página anterior">‹</button>
                                    <div id="page-buttons"></div>
                                    <button type="button" id="next-page" aria-label="Próxima página">›</button>
                                </div>
                            </div>
                        </div>

                        <div class="state-panel" id="empty-state" hidden>
                            <div class="state-icon state-icon-empty" aria-hidden="true">◇</div>
                            <p class="eyebrow">CATÁLOGO VAZIO</p>
                            <h2>Nenhuma carta encontrada</h2>
                            <p>Tente remover os filtros para voltar a ver o catálogo.</p>
                        </div>

                        <div class="state-panel" id="error-state" hidden>
                            <div class="state-icon state-icon-error" aria-hidden="true">!</div>
                            <p class="eyebrow">FALHA DE CONEXÃO</p>
                            <h2>Não foi possível carregar as cartas</h2>
                            <p>Verifique a conexão com o servidor e tente novamente.</p>
                            <button class="button button-secondary" type="button" id="retry-action">Tentar novamente</button>
                        </div>
                    </section>
                </div>
            </main>
        </div>
    </div>

    <aside class="drawer" id="card-drawer" aria-hidden="true" aria-labelledby="drawer-title">
        <button class="drawer-backdrop" id="drawer-backdrop" type="button" tabindex="-1" aria-label="Fechar formulário"></button>
        <div class="drawer-panel" role="dialog" aria-modal="true">
            <div class="drawer-header">
                <div><p class="eyebrow" id="drawer-kicker">NOVA CARTA</p><h2 id="drawer-title">Adicionar carta</h2></div>
                <button class="icon-button" id="drawer-close" type="button" aria-label="Fechar formulário">×</button>
            </div>
            <form id="card-form" novalidate>
                <input type="hidden" id="form-card-id" name="card_id" value="">
                <div class="drawer-body">
                    <div class="catalog-message" id="catalog-message" role="status" aria-live="polite"></div>
                    <div class="field-group">
                        <label for="field-name-en">Nome em inglês <span class="required-mark">*</span></label>
                        <input id="field-name-en" name="name_en" type="text" maxlength="180" required>
                        <p class="field-help">Como o nome aparece na carta.</p>
                        <p class="field-message" data-error-for="name_en"></p>
                    </div>
                    <div class="field-group">
                        <label for="field-name-pt">Nome em português</label>
                        <input id="field-name-pt" name="name_pt" type="text" maxlength="180">
                        <p class="field-help">Opcional. Ajuda na busca da coleção.</p>
                        <p class="field-message" data-error-for="name_pt"></p>
                    </div>
                    <div class="form-grid-two">
                        <div class="field-group">
                        <label for="field-game">Jogo <span class="required-mark">*</span></label>
                            <select id="field-game" name="game" required>
                                <option value="">Escolha o jogo</option>
                                <option value="magic">Magic: The Gathering</option>
                                <option value="pokemon">Pokémon</option>
                                <option value="yugioh">Yu-Gi-Oh!</option>
                            </select>
                            <p class="field-message" data-error-for="game"></p>
                        </div>
                        <div class="field-group">
                            <label for="field-edition">Coleção <span class="required-mark">*</span></label>
                            <select id="field-edition" name="edition_id" disabled required><option value="">Escolha o jogo primeiro</option></select>
                            <p class="field-message" data-error-for="edition_id"></p>
                        </div>
                    </div>
                    <div class="field-group">
                        <label for="field-rarity">Raridade <span class="required-mark">*</span></label>
                        <select id="field-rarity" name="rarity" disabled required><option value="">Escolha o jogo primeiro</option></select>
                        <p class="field-message" data-error-for="rarity"></p>
                    </div>

                    <fieldset class="image-fieldset">
                        <legend>Imagem da carta</legend>
                        <div class="image-source-tabs" role="tablist" aria-label="Origem da imagem">
                            <label class="source-tab is-active" for="source-upload"><input id="source-upload" type="radio" name="image_source" value="upload" checked>Arquivo</label>
                            <label class="source-tab" for="source-url"><input id="source-url" type="radio" name="image_source" value="url">URL</label>
                        </div>
                        <div class="image-source-panel" id="upload-panel">
                            <label class="upload-zone" for="field-image">
                                <span class="upload-symbol" aria-hidden="true">↑</span>
                                <strong id="upload-zone-label">Escolha uma imagem</strong>
                                <small id="upload-zone-help">JPG, PNG ou WebP. Até 5 MB.</small>
                                <input id="field-image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
                            </label>
                        </div>
                        <div class="image-source-panel" id="url-panel" hidden>
                            <label class="visually-hidden" for="field-image-url">URL da imagem</label>
                            <input id="field-image-url" name="image_value" type="url" placeholder="https://exemplo.com/carta.jpg">
                            <p class="field-help">Use uma URL HTTP ou HTTPS pública.</p>
                        </div>
                        <p class="field-message" data-error-for="image"></p>
                        <p class="field-message" data-error-for="image_value"></p>
                        <div class="image-preview" id="image-preview" hidden>
                            <img id="image-preview-img" src="/assets/images/card-fallback.svg" alt="Prévia da imagem da carta">
                            <div><strong id="image-preview-name">Prévia</strong><button type="button" class="text-button" id="remove-image">Remover</button></div>
                        </div>
                    </fieldset>
                </div>
                <div class="drawer-footer">
                    <button class="button button-secondary" type="button" id="form-cancel">Cancelar</button>
                    <button class="button button-primary" type="submit" id="form-submit"><span class="button-label">Salvar carta</span><span class="button-spinner" aria-hidden="true"></span></button>
                </div>
            </form>
        </div>
    </aside>

    <dialog class="confirm-dialog" id="delete-dialog" aria-labelledby="delete-title">
        <div class="dialog-header"><div class="danger-symbol" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M12 7.5v5M12 16.5h.01" /></svg></div><button class="icon-button" id="delete-close" type="button" aria-label="Fechar confirmação">×</button></div>
        <div class="dialog-body"><p class="eyebrow">EXCLUIR CARTA</p><h2 id="delete-title">Excluir esta carta?</h2><p>Tem certeza de que deseja excluir <strong id="delete-card-name"></strong>? Essa ação não pode ser desfeita.</p></div>
        <div class="dialog-footer"><button class="button button-secondary" id="delete-cancel" type="button">Cancelar</button><button class="button button-destructive" id="delete-confirm" type="button"><span class="button-label">Excluir carta</span><span class="button-spinner" aria-hidden="true"></span></button></div>
    </dialog>

    <div class="toast-region" id="toast-region" aria-live="polite" aria-atomic="true"></div>
    <script type="module" src="/assets/js/cards.js"></script>
</body>
</html>
