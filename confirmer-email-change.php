
<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$message = 'Lien invalide ou expiré.';

$token = $_GET['token'] ?? '';

if (is_string($token) && preg_match('/^[a-f0-9]{64}$/', $token)) {
    $tokens = storage_read('email_tokens', []);
    $users = storage_read('users', []);
    $hash = hash('sha256', $token);

    foreach ($tokens as $key => $record) {
        if (
            ($record['purpose'] ?? '') === 'email_change' &&
            isset($record['token_hash'], $record['user_id'], $record['new_email']) &&
            hash_equals($record['token_hash'], $hash) &&
            (int) ($record['expires_at'] ?? 0) >= time()
        ) {
            $newEmail = strtolower($record['new_email']);
            $valid = filter_var($newEmail, FILTER_VALIDATE_EMAIL);
            $userIndex = null;
            $emailUsed = false;

            foreach ($users as $index => $user) {
                if (($user['id'] ?? '') === $record['user_id']) {
                    $userIndex = $index;
                } elseif (strtolower($user['email'] ?? '') === $newEmail) {
                    $emailUsed = true;
                }
            }

            if ($valid && $userIndex !== null && !$emailUsed) {
                $users[$userIndex]['email'] = $newEmail;
                $users[$userIndex]['email_verified'] = true;

                storage_write('users', $users);

                $message = 'Ton adresse e-mail a été modifiée avec succès.';
                add_log('profile.email_changed');

                unset($tokens[$key]);
                storage_write('email_tokens', array_values($tokens));
            } else {
                $message = 'Impossible de modifier cette adresse. Elle est peut-être déjà utilisée.';
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
<title>Confirmation e-mail - EUROLINK V5</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<main class="auth-container">
<div class="auth-card">
<h1>Confirmation de ton adresse</h1>
<p><?= e($message) ?></p>
<a class="button" href="profil.php">Retour au profil</a>
</div>
</main>
</body>
</html>
