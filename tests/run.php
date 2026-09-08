<?php

declare(strict_types=1);

use Cardmancer\Core\Application;
use Cardmancer\Core\Config;
use Cardmancer\Core\Database;
use Cardmancer\Core\HttpException;
use Cardmancer\Core\Request;
use Cardmancer\Core\Response;
use Cardmancer\Repositories\CardRepository;
use Cardmancer\Repositories\UserRepository;
use Cardmancer\Security\AuthSession;
use Cardmancer\Services\CatalogService;
use Cardmancer\Services\UploadService;
use Cardmancer\Validation\CardValidator;

putenv('APP_ENV=testing');
ob_start();

$basePath = dirname(__DIR__);
require $basePath . '/src/bootstrap.php';
$config = Config::load($basePath);
$database = new Database($config);
$connection = $database->connection();
$users = new UserRepository($connection);
$users->ensureSeedCredentials();
$cards = new CardRepository($connection);
$catalog = new CatalogService($config);
$validator = new CardValidator($catalog);
$app = Application::create($config);

$passed = 0;
$failed = 0;

function test(string $name, callable $callback): void
{
    global $passed, $failed;

    try {
        $callback();
        $passed++;
        echo "PASS  {$name}\n";
    } catch (Throwable $exception) {
        $failed++;
        echo "FAIL  {$name}: {$exception->getMessage()}\n";
    }
}

function assertTrue(bool $condition, string $message = 'Condicao falsa'): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function assertSameValue(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message !== '' ? $message : sprintf('Esperado %s, recebido %s', var_export($expected, true), var_export($actual, true)));
    }
}

function assertThrows(callable $callback, string $class = HttpException::class): Throwable
{
    try {
        $callback();
    } catch (Throwable $exception) {
        assertTrue($exception instanceof $class, 'Excecao inesperada: ' . $exception::class);

        return $exception;
    }

    throw new RuntimeException('A excecao esperada nao foi lancada.');
}

function responseData(string $body): array
{
    $payload = json_decode($body, true);

    return is_array($payload) ? $payload : [];
}

function dispatch(Application $app, Request $request): Response
{
    try {
        return $app->handle($request);
    } catch (HttpException $exception) {
        return Response::error($exception);
    }
}

test('catalogos retornam jogos, edicoes e raridades', function () use ($catalog): void {
    assertSameValue(['magic', 'pokemon', 'yugioh'], $catalog->games());
    assertTrue($catalog->isValidEdition('magic', 'dominaria'));
    assertTrue($catalog->isValidRarity('pokemon', 'Ultra Rare'));
    assertTrue(!$catalog->isValidRarity('magic', 'Ultra Rare'));
});

test('validador rejeita jogo, edicao e raridade incompatíveis', function () use ($validator): void {
    $exception = assertThrows(static fn (): array => $validator->validate([
        'name_en' => 'Carta inválida',
        'game' => 'magic',
        'edition_id' => 'scarlet-violet',
        'rarity' => 'Ultra Rare',
        'image_source' => 'url',
        'image_value' => 'https://example.com/card.png',
    ]));
    assertSameValue('validation_error', $exception->errorCode());
    assertTrue(isset($exception->fields()['edition_id']));
    assertTrue(isset($exception->fields()['rarity']));
});

test('login válido e inválido', function () use ($app): void {
    $invalid = dispatch($app, new Request('POST', '/api/auth/login', [], ['email' => 'admin@indigoplateau.local', 'password' => 'errada'], [], '', []));
    assertSameValue(401, $invalid->status());

    $valid = dispatch($app, new Request('POST', '/api/auth/login', [], ['email' => 'admin@indigoplateau.local', 'password' => 'Indigo@123'], [], '', []));
    assertSameValue(200, $valid->status());
    $payload = responseData($valid->body());
    assertTrue(($payload['data']['csrf_token'] ?? '') !== '');
});

test('perfil valida e atualiza nova senha', function () use ($app, $users): void {
    $session = new AuthSession(Config::load(dirname(__DIR__)));
    $headers = ['x-csrf-token' => $session->csrfToken(), 'content-type' => 'application/json'];
    $invalid = dispatch($app, new Request('POST', '/api/profile', [], [], [], json_encode([
        'name' => 'Administração',
        'email' => 'admin@indigoplateau.local',
        'new_password' => 'NovaSenha123',
        'confirm_password' => 'OutraSenha123',
    ], JSON_THROW_ON_ERROR), $headers));
    assertSameValue(422, $invalid->status());

    $updated = dispatch($app, new Request('POST', '/api/profile', [], [], [], json_encode([
        'name' => 'Administração',
        'email' => 'admin@indigoplateau.local',
        'new_password' => 'NovaSenha123',
        'confirm_password' => 'NovaSenha123',
    ], JSON_THROW_ON_ERROR), $headers));
    assertSameValue(200, $updated->status());
    assertTrue(password_verify('NovaSenha123', (string) ($users->findByEmail('admin@indigoplateau.local')['password_hash'] ?? '')));

    $user = $users->findByEmail('admin@indigoplateau.local');
    $users->updateProfile((int) $user['id'], 'Administração', 'admin@indigoplateau.local', password_hash('Indigo@123', PASSWORD_DEFAULT));
});

test('rota protegida e CSRF', function () use ($app): void {
    $list = dispatch($app, new Request('GET', '/api/cards', [], [], [], '', []));
    assertSameValue(200, $list->status());
    $missingCsrf = dispatch($app, new Request('POST', '/api/cards', [], ['name_en' => 'Sem CSRF'], [], '', []));
    assertSameValue(419, $missingCsrf->status());
});

test('CRUD de carta e paginação', function () use ($app, $cards, $catalog): void {
    $session = new AuthSession(Config::load(dirname(__DIR__)));
    $token = $session->csrfToken();
    $headers = ['x-csrf-token' => $token, 'content-type' => 'application/json'];
    $createdResponse = dispatch($app, new Request('POST', '/api/cards', [], [], [], json_encode([
        'name_en' => 'Test Card Runner',
        'name_pt' => 'Carta de teste',
        'game' => 'magic',
        'edition_id' => 'dominaria',
        'rarity' => 'Common',
        'image_source' => 'url',
        'image_value' => 'https://example.com/test-card.png',
    ], JSON_THROW_ON_ERROR), $headers));
    assertSameValue(201, $createdResponse->status());
    $created = responseData($createdResponse->body())['data']['item'] ?? [];
    $id = (int) ($created['id'] ?? 0);
    assertTrue($id > 0);

    $found = $cards->find($id);
    assertSameValue('Test Card Runner', $found['name_en'] ?? null);
    assertSameValue('Dominaria', $catalog->editionLabel('magic', (string) $found['edition_id']));

    $duplicateResponse = dispatch($app, new Request('POST', '/api/cards', [], [], [], json_encode([
        'name_en' => 'Test Card Runner',
        'name_pt' => '',
        'game' => 'magic',
        'edition_id' => 'dominaria',
        'rarity' => 'Common',
        'image_source' => 'url',
        'image_value' => 'https://example.com/duplicate.png',
    ], JSON_THROW_ON_ERROR), $headers));
    assertSameValue(422, $duplicateResponse->status());

    $updatedResponse = dispatch($app, new Request('POST', '/api/cards/' . $id, [], [
        '_method' => 'PUT',
        'name_en' => 'Test Card Updated',
        'name_pt' => '',
        'game' => 'magic',
        'edition_id' => 'dominaria',
        'rarity' => 'Rare',
        'image_source' => 'url',
        'image_value' => 'https://example.com/updated.png',
    ], [], '', $headers));
    assertSameValue(200, $updatedResponse->status());

    $page = $cards->paginate(['q' => 'Test Card Updated', 'page' => 1, 'per_page' => 2, 'sort' => 'name', 'direction' => 'ASC']);
    assertSameValue(1, $page['total']);
    assertSameValue('Test Card Updated', $page['items'][0]['name_en']);

    $deletedResponse = dispatch($app, new Request('DELETE', '/api/cards/' . $id, [], [], [], '', $headers));
    assertSameValue(200, $deletedResponse->status());
    assertSameValue(null, $cards->find($id));
});

test('upload rejeita arquivo acima do limite', function () use ($config): void {
    $path = tempnam(sys_get_temp_dir(), 'cardmancer-test-');
    file_put_contents($path, str_repeat('x', 5 * 1024 * 1024 + 1));
    $upload = new UploadService($config);
    $exception = assertThrows(static fn (): string => $upload->store([
        'error' => UPLOAD_ERR_OK,
        'size' => 5 * 1024 * 1024 + 1,
        'tmp_name' => $path,
    ]));
    @unlink($path);
    assertSameValue('validation_error', $exception->errorCode());
});

test('upload válido recebe nome aleatório e pode ser removido', function () use ($config): void {
    $path = tempnam(sys_get_temp_dir(), 'cardmancer-image-');
    file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true));
    $upload = new UploadService($config);
    $stored = $upload->store([
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($path),
        'tmp_name' => $path,
    ]);
    assertTrue($upload->isManagedPath($stored));
    assertTrue(is_file(dirname(__DIR__) . '/public/' . str_replace('/', DIRECTORY_SEPARATOR, $stored)));
    $upload->delete($stored);
    assertTrue(!is_file(dirname(__DIR__) . '/public/' . str_replace('/', DIRECTORY_SEPARATOR, $stored)));
});

if ($failed > 0) {
    echo "\n{$passed} testes passaram; {$failed} falharam.\n";
    exit(1);
}

echo "\n{$passed} testes passaram.\n";
ob_end_flush();
