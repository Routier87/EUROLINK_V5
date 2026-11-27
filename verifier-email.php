
<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$token = $_GET['token'] ?? '';
$message = 'Lien de vérification invalide ou expiré.';

if (is_string($token) && preg_match('/^[a-f0-9]{64}$/', $token)) {
    $tokens = storage_read('email_tokens', []);
    $users = storage_read('users', []);
    $hash = hash('sha256', $token);
    $found = false;

    foreach ($tokens as $key => $record) {
        if (
            isset($record['token_hash'], $record['user_id']) &&
            hash_equals($record['token_hash'], $hash) &&
            (int) ($record['expires_at'] ?? 0) >= time() &&
            in_array($record['purpose'] ?? 'registration', ['registration'], true)
        ) {
            foreach ($users as &$user) {
                if (($user['id'] ?? '') === $record['user_id']) {
                    $user['email_verified'] = true;
                    $found = true;
                    break;
                }
            }
            unset($user);

            if ($found) {
                unset($tokens[$key]);
                storage_write('users', array_values($users));
                storage_write('email_tokens', array_values($tokens));
                add_log('email.verified', 'Adresse e-mail vérifiée');
                $message = 'Ton adresse e-mail est maintenant vérifiée. Tu peux te connecter.';
            }
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Vérification e-mail - EUROLINK V5</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<main class="auth-container">
<div class="auth-card">
<h1>Vérification de ton adresse e-mail</h1>
<p><?= e($message) ?></p>
<a class="button" href="connexion.php">Aller à la connexion</a>
</div>
</main>
</body>
</html>
