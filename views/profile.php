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
    <meta name="description" content="Edite suas informações de perfil no Cardmancer.">
    <title>Cardmancer</title>
    <link rel="icon" href="/assets/images/card-games.png?v=2" type="image/png">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="app-page management-page profile-page" data-page="profile" data-csrf-token="<?= htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
    <a class="skip-link" href="#main-content">Pular para o conteúdo</a>

    <div class="management-shell">
        <aside class="management-sidebar" aria-label="Navegação principal">
            <a class="sidebar-brand" href="/cards" aria-label="Cardmancer, gerenciamento da coleção">
                <img class="brand-logo-image" src="/assets/images/card-games.png" alt="">
                <span><strong>CARDMANCER</strong><small>Gerencie sua coleção</small></span>
            </a>

            <nav class="sidebar-navigation">
                <a class="sidebar-nav-item" href="/cards">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="4" y="4" width="6" height="6" rx="1"></rect><rect x="14" y="4" width="6" height="6" rx="1"></rect><rect x="4" y="14" width="6" height="6" rx="1"></rect><rect x="14" y="14" width="6" height="6" rx="1"></rect></svg>
                    <span>Painel</span>
                </a>
            </nav>

            <div class="sidebar-account">
                <a class="sidebar-profile" href="/profile" aria-current="page" aria-label="Abrir meu perfil">
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
                <span>Meu perfil</span>
            </header>

            <main id="main-content" class="management-content profile-content">
                <div class="management-inner profile-inner">
                    <a class="profile-back" href="/cards"><span aria-hidden="true">←</span> Voltar para o catálogo</a>

                    <section class="profile-layout" aria-labelledby="profile-title">
                        <aside class="profile-summary">
                            <div class="profile-summary-avatar" aria-hidden="true"><?= htmlspecialchars($initials !== '' ? $initials : 'AD', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                            <h1 id="profile-summary-name"><?= htmlspecialchars($userName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h1>
                            <p id="profile-summary-email"><?= htmlspecialchars($userEmail !== '' ? $userEmail : 'Sem e-mail', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                            <div class="profile-summary-status">
                                <span class="profile-status-dot" aria-hidden="true"></span>
                                <span>Perfil ativo</span>
                            </div>
                            <div class="profile-summary-role">
                                <small>FUNÇÃO</small>
                                <strong>Administrador</strong>
                            </div>
                            <div class="profile-summary-note">
                                <small>ACESSO</small>
                                <span>Conta protegida por senha e sessão segura.</span>
                            </div>
                        </aside>

                        <section class="profile-form-panel">
                            <header class="profile-form-header">
                                <h2 id="profile-title">Editar informações do usuário</h2>
                                <p>Atualize seus dados pessoais usados no catálogo administrativo.</p>
                            </header>

                            <form id="profile-form" novalidate>
                                <section class="profile-form-section" aria-labelledby="personal-data-title">
                                    <h3 id="personal-data-title">Dados pessoais</h3>
                                    <div class="profile-form-grid">
                                        <div class="profile-field">
                                            <label for="profile-name">Nome completo</label>
                                            <input id="profile-name" name="name" type="text" maxlength="120" value="<?= htmlspecialchars($userName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" autocomplete="name" required>
                                            <p class="profile-field-message" data-error-for="name"></p>
                                        </div>
                                        <div class="profile-field">
                                            <label for="profile-email">E-mail</label>
                                            <input id="profile-email" name="email" type="email" maxlength="190" value="<?= htmlspecialchars($userEmail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" autocomplete="email" required>
                                            <p class="profile-field-message" data-error-for="email"></p>
                                        </div>
                                    </div>
                                </section>

                                <section class="profile-form-section" aria-labelledby="password-title">
                                    <h3 id="password-title">Nova senha</h3>
                                    <p class="profile-section-help">Deixe os campos em branco para manter a senha atual.</p>
                                    <div class="profile-form-grid">
                                        <div class="profile-field">
                                            <label for="profile-new-password">Nova senha</label>
                                            <div class="profile-password-control">
                                                <input id="profile-new-password" name="new_password" type="password" minlength="8" autocomplete="new-password">
                                                <button class="profile-password-toggle" type="button" data-password-toggle="profile-new-password" aria-label="Mostrar nova senha" aria-pressed="false">
                                                    <svg data-password-icon="open" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M2.5 12s3.4-5 9.5-5 9.5 5 9.5 5-3.4 5-9.5 5-9.5-5-9.5-5Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>
                                                    <svg data-password-icon="closed" viewBox="0 0 24 24" aria-hidden="true" focusable="false" hidden><path d="m3 3 18 18"></path><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path><path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.1 0 9.5 5 9.5 5a16.8 16.8 0 0 1-3.4 3.7M6.7 6.7C4.1 8.4 2.5 10.5 2.5 10.5S5.9 15.5 12 15.5c.8 0 1.5-.1 2.2-.3"></path></svg>
                                                </button>
                                            </div>
                                            <p class="profile-field-message" data-error-for="new_password"></p>
                                        </div>
                                        <div class="profile-field">
                                            <label for="profile-confirm-password">Confirmar senha</label>
                                            <div class="profile-password-control">
                                                <input id="profile-confirm-password" name="confirm_password" type="password" minlength="8" autocomplete="new-password">
                                                <button class="profile-password-toggle" type="button" data-password-toggle="profile-confirm-password" aria-label="Mostrar confirmação de senha" aria-pressed="false">
                                                    <svg data-password-icon="open" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M2.5 12s3.4-5 9.5-5 9.5 5 9.5 5-3.4 5-9.5 5-9.5-5-9.5-5Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>
                                                    <svg data-password-icon="closed" viewBox="0 0 24 24" aria-hidden="true" focusable="false" hidden><path d="m3 3 18 18"></path><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path><path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.1 0 9.5 5 9.5 5a16.8 16.8 0 0 1-3.4 3.7M6.7 6.7C4.1 8.4 2.5 10.5 2.5 10.5S5.9 15.5 12 15.5c.8 0 1.5-.1 2.2-.3"></path></svg>
                                                </button>
                                            </div>
                                            <p class="profile-field-message" data-error-for="confirm_password"></p>
                                        </div>
                                    </div>
                                </section>

                                <div class="profile-form-actions">
                                    <button class="button button-secondary" type="reset">Cancelar</button>
                                    <button class="button profile-save-button" id="profile-submit" type="submit"><span class="button-label">Salvar alterações</span><span class="button-spinner" aria-hidden="true"></span></button>
                                </div>
                                <p class="profile-feedback" id="profile-feedback" role="status" aria-live="polite"></p>
                            </form>
                        </section>
                    </section>
                </div>
            </main>
        </div>
    </div>

    <script type="module" src="/assets/js/profile.js"></script>
</body>
</html>
