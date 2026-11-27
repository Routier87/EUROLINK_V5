
<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email = strtolower(trim($_POST['email'] ?? ''));
    $users = storage_read('users', []);

    foreach ($users as $user) {
        if (
            strtolower($user['email'] ?? '') === $email &&
            !empty($user['email_verified']) &&
            ($user['status'] ?? '') === 'active'
        ) {
            $token = bin2hex(random_bytes(32));

            $tokens = storage_read('reset_tokens', []);
            $tokens = array_values(array_filter(
                $tokens,
                fn($item) => ($item['user_id'] ?? '') !== $user['id']
            ));

            $tokens[] = [
                'user_id' => $user['id'],
                'token_hash' => hash('sha256', $token),
                'expires_at' => time() + 3600
            ];

            storage_write('reset_tokens', $tokens);

            $link = SITE_URL . '/reinitialiser-mot-de-passe.php?token=' . urlencode($token);

            send_site_email(
                $email,
                'Réinitialisation de ton mot de passe EUROLINK V5',
                "Bonjour,\n\n"
                . "Pour choisir un nouveau mot de passe, ouvre ce lien :\n\n"
                . $link . "\n\nCe lien expire dans une heure.\n"
                . "Si tu n'es pas à l'origine de cette demande, ignore ce message."
            );

            break;
        }
    }

    // Même message, que l'adresse existe ou non.
    $message = 'Si un compte vérifié correspond à cette adresse, un e-mail de réinitialisation sera envoyé.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mot de passe oublié - EUROLINK V5</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<main class="auth-container">
<div class="auth-card">
<h1>Mot de passe oublié ?</h1>
<p>Indique ton adresse e-mail pour recevoir un lien de réinitialisation.</p>

<?php if ($message): ?>
<div class="alert success"><?= e($message) ?></div>
<?php endif; ?>

<form method="post">
<?= csrf_field() ?>
<label>Adresse e-mail</label>
<input type="email" name="email" autocomplete="email" required>
<button type="submit">Envoyer le lien</button>
</form>

<p><a href="connexion.php">Retour à la connexion</a></p>
</div>
</main>
</body>
</html>
