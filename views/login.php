<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Acesso ao catálogo administrativo de cartas do Cardmancer.">
    <title>Cardmancer</title>
    <link rel="icon" href="/assets/images/card-games.png?v=2" type="image/png">
    <link rel="stylesheet" href="/assets/css/app.css?v=2">
</head>
<body class="login-page" data-page="login">
    <main class="login-layout">
        <section class="login-brand-panel" aria-labelledby="brand-title">
            <a class="brand-lockup brand-lockup-large" href="/login" aria-label="Cardmancer">
                <img class="brand-logo-image" src="/assets/images/card-games.png" alt="">
                <span>
                    <strong id="brand-title">CARDMANCER</strong>
                    <small>Gerencie sua coleção</small>
                </span>
            </a>

            <div class="login-brand-copy">
                <h2>Seu catálogo<br><em>sob controle.</em></h2>
                <p>Organize nomes, edições, raridades e imagens em um só lugar.</p>
                <span class="brand-rule" aria-hidden="true"></span>
            </div>
        </section>

        <section class="login-form-panel" aria-labelledby="login-title">
            <div class="login-orbit login-orbit-large" aria-hidden="true"></div>
            <div class="login-orbit login-orbit-small" aria-hidden="true"></div>
            <div class="login-form-wrap">
                <div class="login-eyebrow-row">
                    <p class="eyebrow">PORTAL ADMINISTRATIVO</p>
                    <span aria-hidden="true"></span>
                </div>
                <h1 id="login-title">Acesse o portal</h1>
                <p class="lead">Use suas credenciais administrativas para continuar.</p>

                <form id="login-form" novalidate>
                    <div class="field-group login-field-group">
                        <label for="email">E-mail</label>
                        <div class="login-field-control">
                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 6h16v12H4z"></path><path d="m4 7 8 6 8-6"></path></svg>
                            <input id="email" name="email" type="email" inputmode="email" autocomplete="username" placeholder="Email" required>
                        </div>
                        <p class="field-message" data-error-for="email"></p>
                    </div>

                    <div class="field-group login-field-group">
                        <label for="password">Senha</label>
                        <div class="login-field-control">
                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
                            <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Senha" required>
                            <button class="password-eye" id="password-toggle" type="button" aria-label="Mostrar senha" aria-pressed="false">
                                <svg data-password-icon="open" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M2.5 12s3.4-5 9.5-5 9.5 5 9.5 5-3.4 5-9.5 5-9.5-5-9.5-5Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>
                                <svg data-password-icon="closed" viewBox="0 0 24 24" aria-hidden="true" focusable="false" hidden><path d="m3 3 18 18"></path><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path><path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.1 0 9.5 5 9.5 5a16.8 16.8 0 0 1-3.4 3.7M6.7 6.7C4.1 8.4 2.5 10.5 2.5 10.5S5.9 15.5 12 15.5c.8 0 1.5-.1 2.2-.3"></path></svg>
                            </button>
                        </div>
                        <p class="field-message" data-error-for="password"></p>
                    </div>

                    <div class="login-options">
                        <label class="checkbox-control" for="remember">
                            <input id="remember" name="remember" type="checkbox">
                            <span>Manter acesso neste dispositivo</span>
                        </label>
                        <a class="forgot-link" href="#login-form">Esqueceu sua senha?</a>
                    </div>

                    <button class="button button-primary button-block" type="submit" id="login-submit">
                        <span class="button-label">Entrar no portal <span aria-hidden="true">→</span></span>
                        <span class="button-spinner" aria-hidden="true"></span>
                    </button>

                    <div class="form-feedback" id="login-feedback" role="alert" aria-live="polite"></div>
                </form>

                <p class="login-note"><span class="login-note-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg></span> <span>Acesso protegido. O portal é responsável pelo catálogo do Cardmancer.</span></p>
            </div>
        </section>
    </main>
    <script type="module" src="/assets/js/login.js?v=2"></script>
</body>
</html>
