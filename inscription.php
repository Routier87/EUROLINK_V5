
<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (!site_setting('registration_enabled', true)) {
        $error = "Les inscriptions sont actuellement désactivées.";
    } else {
        $name = trim($_POST['display_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirmation'] ?? '';

        if ($name === '' || strlen($name) > 60) {
            $error = 'Nom invalide (60 caractères maximum).';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Adresse e-mail invalide.';
        } elseif (strlen($password) < 12) {
            $error = 'Le mot de passe doit contenir au moins 12 caractères.';
        } elseif ($password !== $confirm) {
            $error = 'Les mots de passe ne correspondent pas.';
        } else {
            $users = storage_read('users', []);
            $exists = false;

            foreach ($users as $user) {
                if (strtolower($user['email'] ?? '') === $email) {
                    $exists = true;
                    break;
                }
            }

            if ($exists) {
                $error = 'Cette adresse e-mail possède déjà un compte.';
            } else {
                $userId = bin2hex(random_bytes(16));
                $token = bin2hex(random_bytes(32));

                $users[] = [
                    'id' => $userId,
                    'username' => 'membre_' . substr($userId, 0, 8),
                    'display_name' => $name,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'role_id' => 'member',
                    'status' => 'active',
                    'email_verified' => false,
                    'must_change_password' => false,
                    'created_at' => date('c')
                ];

                $tokens = storage_read('email_tokens', []);
                $tokens[] = [
                    'user_id' => $userId,
                    'token_hash' => hash('sha256', $token),
                    'expires_at' => time() + 86400
                ];

                storage_write('users', $users);
                storage_write('email_tokens', $tokens);

                $link = SITE_URL . '/verifier-email.php?token=' . urlencode($token);

                $sent = send_site_email(
                    $email,
                    'Vérification de ton compte EUROLINK V5',
                    "Bonjour {$name},\n\n"
                    . "Merci pour ton inscription sur EUROLINK V5.\n"
                    . "Clique sur ce lien pour confirmer ton adresse e-mail :\n\n"
                    . $link . "\n\n"
                    . "Ce lien expire dans 24 heures."
                );

                if ($sent) {
                    $success = 'Ton compte a été créé. Consulte ta boîte e-mail pour le vérifier.';
                } else {
                    $success = "Ton compte a été créé, mais l'envoi de l'e-mail a échoué. Contacte le Fondateur pour faire vérifier ton adresse.";
                }

                add_log('member.register', 'Inscription de ' . $email);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Inscription - EUROLINK V5</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<main class="auth-container">
<div class="auth-card">
<h1>Créer un compte</h1>
<p>Rejoins la communauté EUROLINK V5.</p>

<?php if ($error): ?>
<div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert success"><?= e($success) ?></div>
<?php else: ?>
<form method="post">
<?= csrf_field() ?>

<label>Nom affiché</label>
<input type="text" name="display_name" maxlength="60" required>

<label>Adresse e-mail</label>
<input type="email" name="email" autocomplete="email" required>

<label>Mot de passe</label>
<input type="password" name="password" minlength="12" autocomplete="new-password" required>

<label>Confirmer le mot de passe</label>
<input type="password" name="confirmation" minlength="12" autocomplete="new-password" required>

<button type="submit">Créer mon compte</button>
</form>
<?php endif; ?>

<p><a href="connexion.php">Déjà inscrit ? Se connecter</a></p>
<a href="index.php">Retour à l'accueil</a>
</div>
</main>
</body>
</html>
