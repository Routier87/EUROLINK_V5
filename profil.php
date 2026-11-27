
<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$user = require_login();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $action = $_POST['action'] ?? '';
    $users = storage_read('users', []);
    $userIndex = null;

    foreach ($users as $index => $item) {
        if (($item['id'] ?? '') === $user['id']) {
            $userIndex = $index;
            break;
        }
    }

    if ($userIndex === null) {
        http_response_code(404);
        exit('Compte introuvable.');
    }

    if ($action === 'display_name') {
        $name = trim($_POST['display_name'] ?? '');

        if ($name === '' || strlen($name) > 60) {
            $error = 'Le nom doit contenir entre 1 et 60 caractères.';
        } else {
            $users[$userIndex]['display_name'] = $name;
            storage_write('users', $users);
            $success = 'Nom affiché modifié.';
        }
    }

    elseif (in_array($action, ['password', 'email'], true)) {
        $currentPassword = $_POST['current_password'] ?? '';

        if (!password_verify($currentPassword, $users[$userIndex]['password'])) {
            $error = 'Mot de passe actuel incorrect.';
        }

        elseif ($action === 'password') {
            $newPassword = $_POST['new_password'] ?? '';
            $confirmation = $_POST['confirmation'] ?? '';

            if (strlen($newPassword) < 12) {
                $error = 'Le nouveau mot de passe doit contenir au moins 12 caractères.';
            } elseif ($newPassword !== $confirmation) {
                $error = 'La confirmation ne correspond pas.';
            } elseif (password_verify($newPassword, $users[$userIndex]['password'])) {
                $error = 'Choisis un mot de passe différent de l’ancien.';
            } else {
                $users[$userIndex]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                $users[$userIndex]['must_change_password'] = false;
                storage_write('users', $users);
                session_regenerate_id(true);
                $success = 'Mot de passe modifié avec succès.';
                add_log('profile.password_changed');
            }
        }

        elseif ($action === 'email') {
            $newEmail = strtolower(trim($_POST['new_email'] ?? ''));
            $confirmation = strtolower(trim($_POST['confirm_email'] ?? ''));

            if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                $error = 'Nouvelle adresse e-mail invalide.';
            } elseif ($newEmail !== $confirmation) {
                $error = 'Les deux adresses e-mail ne correspondent pas.';
            } elseif ($newEmail === strtolower($users[$userIndex]['email'])) {
                $error = 'Cette adresse est déjà utilisée par ton compte.';
            } else {
                foreach ($users as $other) {
                    if (
                        ($other['id'] ?? '') !== $user['id'] &&
                        strtolower($other['email'] ?? '') === $newEmail
                    ) {
                        $error = 'Cette adresse e-mail est déjà utilisée.';
                        break;
                    }
                }

                if ($error === '') {
                    $tokens = storage_read('email_tokens', []);

                    // Une seule demande de changement d'adresse à la fois.
                    $tokens = array_values(array_filter(
                        $tokens,
                        fn($item) => !(
                            ($item['purpose'] ?? '') === 'email_change' &&
                            ($item['user_id'] ?? '') === $user['id']
                        )
                    ));

                    $token = bin2hex(random_bytes(32));

                    $tokens[] = [
                        'purpose' => 'email_change',
                        'user_id' => $user['id'],
                        'new_email' => $newEmail,
                        'token_hash' => hash('sha256', $token),
                        'expires_at' => time() + 3600
                    ];

                    storage_write('email_tokens', $tokens);

                    $link = SITE_URL . '/confirmer-email-change.php?token=' . urlencode($token);

                    $sent = send_site_email(
                        $newEmail,
                        'Confirme ta nouvelle adresse EUROLINK V5',
                        "Pour confirmer ta nouvelle adresse e-mail, ouvre ce lien :\n\n"
                        . $link . "\n\nCe lien expire dans une heure."
                    );

                    if ($sent) {
                        $success = 'Un lien de confirmation a été envoyé à ta nouvelle adresse.';
                    } else {
                        $error = "L'envoi de l'e-mail a échoué. Vérifie la configuration de messagerie de l'hébergement et recommence.";
                    }
                }
            }
        }
    }

    else {
        $error = 'Action inconnue.';
    }

    $user = current_user() ?? $user;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mon profil - EUROLINK V5</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php require __DIR__ . '/includes/header.php'; ?>

<section class="section">
<h1>Mon profil</h1>

<?php if ($error): ?>
<div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert success"><?= e($success) ?></div>
<?php endif; ?>

<div class="cards">
<div class="admin-card">
<h2>Informations personnelles</h2>
<p>Adresse actuelle : <?= e($user['email']) ?></p>
<p>Rôle : <?= e(user_role_name($user)) ?></p>

<form method="post">
<?= csrf_field() ?>
<input type="hidden" name="action" value="display_name">
<label>Nom affiché</label>
<input name="display_name" maxlength="60" value="<?= e($user['display_name']) ?>" required>
<button type="submit">Modifier mon nom</button>
</form>
</div>

<div class="admin-card">
<h2>Changer mon mot de passe</h2>
<form method="post">
<?= csrf_field() ?>
<input type="hidden" name="action" value="password">
<label>Mot de passe actuel</label>
<input type="password" name="current_password" autocomplete="current-password" required>
<label>Nouveau mot de passe</label>
<input type="password" name="new_password" minlength="12" autocomplete="new-password" required>
<label>Confirmer le nouveau mot de passe</label>
<input type="password" name="confirmation" minlength="12" autocomplete="new-password" required>
<button type="submit">Changer le mot de passe</button>
</form>
</div>

<div class="admin-card">
<h2>Changer mon adresse e-mail</h2>
<form method="post">
<?= csrf_field() ?>
<input type="hidden" name="action" value="email">
<label>Mot de passe actuel</label>
<input type="password" name="current_password" autocomplete="current-password" required>
<label>Nouvelle adresse e-mail</label>
<input type="email" name="new_email" required>
<label>Confirmer la nouvelle adresse</label>
<input type="email" name="confirm_email" required>
<button type="submit">Demander le changement</button>
</form>
<p>Ton adresse actuelle ne changera qu'après confirmation du lien reçu sur la nouvelle adresse.</p>
</div>
</div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
