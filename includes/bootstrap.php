
<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

if (!is_dir(DATA_PATH) && !mkdir(DATA_PATH, 0750, true) && !is_dir(DATA_PATH)) {
    http_response_code(500);
    exit('Impossible de créer le dossier data.');
}

if (!is_dir(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0750, true);
}

foreach (['site', 'applications', 'avatars'] as $folder) {
    $path = UPLOAD_PATH . '/' . $folder;

    if (!is_dir($path)) {
        mkdir($path, 0750, true);
    }
}

require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/mailer.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_name(SESSION_NAME);

    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

if (empty($_SESSION['last_activity'])) {
    $_SESSION['last_activity'] = time();
}

if (time() - (int) $_SESSION['last_activity'] > SESSION_LIFETIME) {
    $_SESSION = [];
    session_regenerate_id(true);
    $_SESSION['last_activity'] = time();
} else {
    $_SESSION['last_activity'] = time();
}
