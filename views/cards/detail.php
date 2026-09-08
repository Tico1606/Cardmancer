<?php

declare(strict_types=1);

$userName = (string) ($user['name'] ?? 'Administrador');
$userEmail = (string) ($user['email'] ?? '');
$initials = strtoupper(substr($userName, 0, 1) . substr(strrchr($userName, ' ') ?: '', 1, 1));
$cardId = (int) ($cardId ?? 0);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Detalhes da carta no catálogo Cardmancer.">
    <title>Cardmancer</title>
    <link rel="icon" href="/assets/images/card-games.png?v=2" type="image/png">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="app-page detail-page" data-page="detail" data-card-id="<?= $cardId ?>" data-csrf-token="<?= htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
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

            <main id="main-content" class="detail-content" aria-busy="true">
                <div class="detail-inner">
                    <a class="detail-back-button" href="/cards"><span aria-hidden="true">←</span> Voltar para a lista</a>

                    <section class="card-detail-panel" aria-labelledby="detail-card-name">
                        <div class="selected-card-visual-column">
                            <div class="selected-card-image-frame">
                                <img id="detail-image" src="/assets/images/card-fallback.svg" alt="Imagem da carta">
                                <span class="card-image-shine" aria-hidden="true"></span>
                            </div>
                        </div>

                        <div class="card-detail-info">
                            <header class="detail-title-actions">
                                <div class="detail-title-block">
                                    <div class="detail-game-line"><span id="detail-game">Carregando carta</span></div>
                                    <h1 id="detail-card-name">Detalhes da carta</h1>
                                    <p id="detail-card-pt">—</p>
                                </div>
                                <div class="detail-action-buttons">
                                    <button class="detail-edit-button" id="edit-card-button" type="button"><span aria-hidden="true">✎</span> Editar carta</button>
                                    <button class="detail-delete-button" id="delete-card-button" type="button"><span aria-hidden="true">×</span> Excluir</button>
                                </div>
                            </header>

                            <div class="detail-tags" aria-label="Classificações da carta">
                                <span class="detail-tag detail-tag-accent" id="detail-rarity">Raridade</span>
                                <span class="detail-tag" id="detail-edition">Edição</span>
                            </div>

                            <div class="detail-meta-grid">
                                <div class="detail-meta-item"><small>Jogo</small><strong id="meta-game">—</strong></div>
                                <div class="detail-meta-item"><small>Edição</small><strong id="meta-edition">—</strong></div>
                                <div class="detail-meta-item"><small>Raridade</small><strong class="meta-rarity" id="meta-rarity">—</strong></div>
                            </div>

                        </div>
                    </section>

                    <div class="detail-message" id="detail-message" role="status" aria-live="polite"></div>
                </div>
            </main>
        </div>
    </div>

    <aside class="drawer" id="detail-card-drawer" aria-hidden="true" aria-labelledby="detail-drawer-title">
        <button class="drawer-backdrop" id="detail-drawer-backdrop" type="button" tabindex="-1" aria-label="Fechar formulário"></button>
        <div class="drawer-panel" role="dialog" aria-modal="true">
            <div class="drawer-header">
                <div><p class="eyebrow">EDITAR CARTA</p><h2 id="detail-drawer-title">Editar carta</h2></div>
                <button class="icon-button" id="detail-drawer-close" type="button" aria-label="Fechar formulário">×</button>
            </div>
            <form id="detail-card-form" novalidate>
                <div class="drawer-body">
                    <div class="field-group">
                        <label for="detail-field-name-en">Nome em inglês <span class="required-mark">*</span></label>
                        <input id="detail-field-name-en" name="name_en" type="text" maxlength="180" required>
                        <p class="field-message" data-detail-error-for="name_en"></p>
                    </div>
                    <div class="field-group">
                        <label for="detail-field-name-pt">Nome em português</label>
                        <input id="detail-field-name-pt" name="name_pt" type="text" maxlength="180">
                        <p class="field-message" data-detail-error-for="name_pt"></p>
                    </div>
                    <div class="form-grid-two">
                        <div class="field-group">
                            <label for="detail-field-game">Jogo <span class="required-mark">*</span></label>
                            <select id="detail-field-game" name="game" required>
                                <option value="">Escolha o jogo</option>
                                <option value="magic">Magic: The Gathering</option>
                                <option value="pokemon">Pokémon</option>
                                <option value="yugioh">Yu-Gi-Oh!</option>
                            </select>
                            <p class="field-message" data-detail-error-for="game"></p>
                        </div>
                        <div class="field-group">
                            <label for="detail-field-edition">Coleção <span class="required-mark">*</span></label>
                            <select id="detail-field-edition" name="edition_id" disabled required><option value="">Escolha o jogo primeiro</option></select>
                            <p class="field-message" data-detail-error-for="edition_id"></p>
                        </div>
                    </div>
                    <div class="field-group">
                        <label for="detail-field-rarity">Raridade <span class="required-mark">*</span></label>
                        <select id="detail-field-rarity" name="rarity" disabled required><option value="">Escolha o jogo primeiro</option></select>
                        <p class="field-message" data-detail-error-for="rarity"></p>
                    </div>
                    <fieldset class="image-fieldset">
                        <legend>Imagem da carta</legend>
                        <div class="image-source-tabs" role="tablist" aria-label="Origem da imagem">
                            <label class="source-tab is-active" for="detail-source-upload"><input id="detail-source-upload" type="radio" name="image_source" value="upload" checked>Arquivo</label>
                            <label class="source-tab" for="detail-source-url"><input id="detail-source-url" type="radio" name="image_source" value="url">URL</label>
                        </div>
                        <div class="image-source-panel" id="detail-upload-panel">
                            <label class="upload-zone" for="detail-field-image">
                                <span class="upload-symbol" aria-hidden="true">↑</span>
                                <strong id="detail-upload-zone-label">Escolha uma imagem</strong>
                                <small id="detail-upload-zone-help">JPG, PNG ou WebP. Até 5 MB.</small>
                                <input id="detail-field-image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
                            </label>
                        </div>
                        <div class="image-source-panel" id="detail-url-panel" hidden>
                            <label class="visually-hidden" for="detail-field-image-url">URL da imagem</label>
                            <input id="detail-field-image-url" name="image_value" type="url" placeholder="https://exemplo.com/carta.jpg">
                            <p class="field-help">Use uma URL HTTP ou HTTPS pública.</p>
                        </div>
                        <p class="field-message" data-detail-error-for="image"></p>
                        <p class="field-message" data-detail-error-for="image_value"></p>
                    </fieldset>
                </div>
                <div class="drawer-footer">
                    <button class="button button-secondary" type="button" id="detail-form-cancel">Cancelar</button>
                    <button class="button button-primary" type="submit" id="detail-form-submit">Salvar carta</button>
                </div>
            </form>
        </div>
    </aside>

    <dialog class="confirm-dialog" id="detail-delete-dialog" aria-labelledby="detail-delete-title" aria-describedby="detail-delete-description">
        <div class="dialog-header"><div class="danger-symbol" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M12 7.5v5M12 16.5h.01" /></svg></div><button class="icon-button" id="detail-delete-close" type="button" aria-label="Fechar confirmação">×</button></div>
        <div class="dialog-body"><p class="eyebrow">EXCLUIR CARTA</p><h2 id="detail-delete-title">Excluir esta carta?</h2><p id="detail-delete-description">Tem certeza de que deseja excluir <strong id="detail-delete-card-name"></strong>? Essa ação não pode ser desfeita.</p></div>
        <div class="dialog-footer"><button class="button button-secondary" id="detail-delete-cancel" type="button">Cancelar</button><button class="button button-destructive" id="detail-delete-confirm" type="button"><span class="button-label">Excluir carta</span><span class="button-spinner" aria-hidden="true"></span></button></div>
    </dialog>

    <script type="module" src="/assets/js/detail.js"></script>
</body>
</html>
