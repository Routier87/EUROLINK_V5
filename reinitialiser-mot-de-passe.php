
<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$error = '';
$success = false;
$tokenIndex = null;
$userIndex = null;

function find_reset_token(string $token): array
{
    $tokens = storage_read('reset_tokens', []);
    $users = storage_read('users', []);
    $hash = hash('sha256', $token);

    foreach ($tokens as $index => $record) {
        if (
            isset($record['token_hash'], $record['user_id']) &&
            hash_equals($record['token_hash'], $hash) &&
            (int) ($record['expires_at'] ?? 0) >= time()
        ) {
            foreach ($users as $uIndex => $user) {
                if (($user['id'] ?? '') === $record['user_id']) {
                    return [$index, $uIndex];
                }
            }
        }
    }

    return [null, null];
}

if (is_string($token) && preg_match('/^[a-f0-9]{64}$/', $token)) {
    [$tokenIndex, $userIndex] = find_reset_token($token);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $password = $_POST['password'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';

    if ($tokenIndex === null || $userIndex === null) {
        $error = 'Ce lien est invalide ou a expiré.';
    } elseif (strlen($password) < 12) {
        $error = 'Le mot de passe doit contenir au moins 12 caractères.';
    } elseif ($password !== $confirmation) {
        $error = 'Les mots de passe ne correspondent pas.';
    } else {
        $users = storage_read('users', []);
        $tokens = storage_read('reset_tokens', []);

        $users[$userIndex]['password'] = password_hash($password, PASSWORD_DEFAULT);
        $users[$userIndex]['must_change_password'] = false;

        unset($tokens[$tokenIndex]);

        storage_write('users', array_values($users));
        storage_write('reset_tokens', array_values($tokens));

        add_log('account.password_reset');
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Réinitialiser le mot de passe - EUROLINK V5</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<main class="auth-container">
<div class="auth-card">
<h1>Nouveau mot de passe</h1>

<?php if ($success): ?>
<div class="alert success">Ton mot de passe a été modifié.</div>
<a class="button" href="connexion.php">Se connecter</a>
<?php elseif ($error): ?>
<div class="alert error"><?= e($error) ?></div>
<a href="mot-de-passe-oublie.php">Demander un nouveau lien</a>
<?php elseif ($tokenIndex !== null): ?>
<form method="post">
<?= csrf_field() ?>
<input type="hidden" name="token" value="<?= e($token) ?>">
<label>Nouveau mot de passe</label>
<input type="password" name="password" minlength="12" required>
<label>Confirmer le mot de passe</label>
<input type="password" name="confirmation" minlength="12" required>
<button type="submit">Enregistrer le nouveau mot de passe</button>
</form>
<?php else: ?>
<div class="alert error">Ce lien est invalide ou a expiré.</div>
<a href="mot-de-passe-oublie.php">Demander un nouveau lien</a>
<?php endif; ?>

</div>
</main>
</body>
</html>
